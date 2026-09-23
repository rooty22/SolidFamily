<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\LiveTranslate\Dictionary;
use App\LiveTranslate\Json;
use App\LiveTranslate\Languages;
use App\LiveTranslate\LiveTranslate;
use App\LiveTranslate\Resolver;
use App\LiveTranslate\Text;
use App\LiveTranslate\Writer;
use App\Models\Setting;

/**
 * Live Translate.
 *
 *  resolve / save   JSON endpoints of the Translate mode (called from the site and from the dashboard).
 *                   They do their own checks and answer JSON errors, instead of the redirects of the middlewares.
 *  index ...        the dashboard page: on/off switch, site-wide translations (list, delete, export, import).
 */
class LiveTranslateController extends Controller
{
    /* ------------------------------------------------------------------ JSON endpoints */

    private function fail(string $message, int $status, array $extra = []): void
    {
        $this->json(['success' => false, 'data' => ['message' => $message] + $extra], $status);
    }

    private function guard(): void
    {
        if (!LiveTranslate::enabled() || !Auth::adminCheck()) {
            $this->fail('forbidden', 403);
        }
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
        if (!Session::verifyCsrf(is_string($token) ? $token : '')) {
            $this->fail('csrf', 419);
        }

        // An AJAX request has no language of its own: the labels of the popup follow the page being translated.
        $lang = $_POST['lang'] ?? '';
        Languages::pin(is_string($lang) ? $lang : '');
    }

    private function posted(string $key): string
    {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? str_replace(chr(0), '', $value) : '';
    }

    private function postedText(): string
    {
        return mb_substr(trim($this->posted('text')), 0, 4000);
    }

    private function postedPath(): string
    {
        return mb_substr($this->posted('path'), 0, 500);
    }

    /** @return string[] */
    private function postedList(string $key): array
    {
        $value = $_POST[$key] ?? [];
        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    public function resolve(): void
    {
        $this->guard();
        $text = $this->postedText();
        if (Text::norm($text) === '') {
            $this->fail('empty', 400);
        }

        $entry = Dictionary::instance()->find($text);
        $this->json(['success' => true, 'data' => [
            'candidates' => Resolver::find($text, $this->postedPath()),
            'dictionary' => $entry ? ['values' => (object)($entry['values'] ?? [])] : null,
            'lang' => Languages::current(),
        ]]);
    }

    public function save(): void
    {
        $this->guard();
        $text = $this->postedText();
        $norm = Text::norm($text);
        $path = $this->postedPath();

        $posted = is_array($_POST['values'] ?? null) ? $_POST['values'] : [];
        $values = [];
        foreach (Languages::codes() as $code) {
            $values[$code] = Writer::clean($posted[$code] ?? '');
        }

        $targets = $this->postedList('targets');
        $dictionary = $this->posted('dictionary') !== '' && $this->posted('dictionary') !== '0';

        if ($norm === '' || !array_filter($values, 'strlen') || (!$targets && !$dictionary)) {
            $this->fail('nothing-to-save', 400);
        }

        // Only what the resolver finds for this exact text can be written: refs sent by the browser are not trusted.
        $allowed = [];
        foreach (Resolver::find($text, $path) as $candidate) {
            $allowed[$candidate['ref']] = true;
        }

        $results = [];
        $replacement = null;
        foreach ($targets as $ref) {
            if (!isset($allowed[$ref])) {
                $results[] = ['ref' => $ref, 'ok' => false, 'message' => 'The text is no longer stored there. Reload the page.'];
                continue;
            }
            $result = Writer::write($ref, $values, $norm);
            $results[] = ['ref' => $ref, 'ok' => $result['ok'], 'message' => $result['message']];
            if ($result['ok'] && $replacement === null && ($values[$result['matched']] ?? '') !== '') {
                $replacement = $values[$result['matched']];
            }
        }

        $runtime = null;
        if ($dictionary) {
            $lang = Languages::current();
            try {
                $store = Dictionary::instance();
                $store->save($text, array_filter($values, 'strlen'));
                $results[] = ['ref' => 'dictionary', 'ok' => true, 'message' => 'ok'];
                if ($replacement === null && $values[$lang] !== '') {
                    $replacement = $values[$lang];
                }
                $runtime = (object)$store->runtimeMap($lang);
            } catch (\RuntimeException $e) {
                $results[] = ['ref' => 'dictionary', 'ok' => false, 'message' => $e->getMessage()];
            }
        }

        $ok = (bool)array_filter($results, static fn(array $r): bool => $r['ok']);
        if (!$ok) {
            $this->fail('failed', 422, ['results' => $results]);
        }
        $this->json(['success' => true, 'data' => ['results' => $results, 'replacement' => $replacement, 'runtime' => $runtime]]);
    }

    /* ------------------------------------------------------------------ dashboard page */

    public function index(): void
    {
        $this->view('admin/live-translate/index', [
            'pageTitle' => is_rtl() ? 'الترجمة الفورية' : 'Live Translate',
            'enabled' => LiveTranslate::enabled(),
            'entries' => Dictionary::instance()->entries(),
            'languages' => Languages::describe(),
        ], 'admin/layout');
    }

    public function toggle(): void
    {
        $this->verifyCsrf();
        $on = ($_POST['lt_enabled'] ?? '') === '1';
        Setting::set('lt_enabled', $on ? '1' : '0');
        Session::flash('success', is_rtl()
            ? ($on ? 'تم تفعيل الترجمة الفورية.' : 'تم إيقاف الترجمة الفورية.')
            : ($on ? 'Live Translate is on.' : 'Live Translate is off.'));
        $this->redirect('admin/live-translate');
    }

    public function deleteEntry(string $id): void
    {
        $this->verifyCsrf();
        Dictionary::instance()->delete($id);
        Session::flash('success', is_rtl() ? 'تم حذف الترجمة العامة.' : 'Site-wide translation deleted.');
        $this->redirect('admin/live-translate');
    }

    public function export(): void
    {
        $json = json_encode(array_values(Dictionary::instance()->entries()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="live-translate-' . date('Y-m-d') . '.json"');
        echo $json;
        exit;
    }

    public function import(): void
    {
        $this->verifyCsrf();
        $raw = '';
        $file = $_FILES['file'] ?? null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file((string)$file['tmp_name'])) {
            $raw = (string)@file_get_contents($file['tmp_name']);
        }

        $data = Json::decode($raw);
        if ($data === null) {
            Session::flash('error', is_rtl()
                ? 'الملف غير صالح: يجب أن يكون ملف JSON تم تصديره من هذه الصفحة.'
                : 'Invalid file: it has to be a JSON file exported from this page.');
            $this->redirect('admin/live-translate');
        }

        try {
            $count = Dictionary::instance()->import($data);
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('admin/live-translate');
        }
        Session::flash('success', $count
            ? (is_rtl() ? "تم استيراد {$count} ترجمة." : "{$count} translations imported.")
            : (is_rtl() ? 'لم يتم استيراد أي ترجمة (الملف فارغ أو أقدم مما هو موجود).' : 'Nothing imported (the file is empty or older than what is here).'));
        $this->redirect('admin/live-translate');
    }
}
