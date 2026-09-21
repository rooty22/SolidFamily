<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ContentPage;

class ContentController extends Controller
{
    public function index(): void
    {
        $pages = ContentPage::all('slug ASC');
        $this->view('admin/content/index', [
            'pageTitle' => __('site_content'),
            'pages' => $pages,
        ], 'admin/layout');
    }

    public function edit(string $slug): void
    {
        $page = ContentPage::bySlug($slug);
        if (!$page) {
            $this->redirect('admin/content');
        }
        $this->view('admin/content/edit', [
            'pageTitle' => __('edit_content', ['title' => $page['title']]),
            'page' => $page,
        ], 'admin/layout');
    }

    public function update(string $slug): void
    {
        $this->verifyCsrf();
        $page = ContentPage::bySlug($slug);
        if (!$page) {
            $this->redirect('admin/content');
        }
        $back = 'admin/content/' . $page['slug'] . '/edit';

        $data = $this->all();
        $title = $data['title'] ?? $page['title'];
        $titleEn = $data['title_en'] ?? ($page['title_en'] ?? '');
        $content = $data['content'] ?? $page['content'];
        $contentEn = $data['content_en'] ?? ($page['content_en'] ?? '');

        $validator = Validator::make($data + ['title' => $title, 'title_en' => $titleEn, 'content' => $content, 'content_en' => $contentEn])
            ->required('title', 'العنوان')->max('title', 200, 'العنوان')
            ->max('title_en', 200, 'العنوان بالإنجليزية')
            ->max('content', 100000, 'المحتوى')->max('content_en', 100000, 'المحتوى بالإنجليزية')
            ->in('form_type', ['contact'], 'نوع النموذج')
            ->in('form_enabled', ['0', '1'], 'حالة النموذج')
            ->max('form_title_ar', 200, 'عنوان النموذج')->max('form_title_en', 200, 'عنوان النموذج بالإنجليزية')
            ->max('form_desc_ar', 500, 'وصف النموذج')->max('form_desc_en', 500, 'وصف النموذج بالإنجليزية')
            ->max('form_success_msg_ar', 300, 'رسالة النجاح')->max('form_success_msg_en', 300, 'رسالة النجاح بالإنجليزية');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect($back);
        }

        $sectionsJson = $data['sections_json'] ?? '';
        if ($sectionsJson !== '') {
            $decoded = json_decode($sectionsJson, true);
            $isList = is_array($decoded) && count($decoded) <= 50 && strlen($sectionsJson) <= 200000;
            foreach ((array) $decoded as $section) {
                $isList = $isList && is_array($section);
            }
            if (json_last_error() !== JSON_ERROR_NONE || !$isList) {
                Session::flash('error', 'صيغة أقسام الصفحة (JSON) غير صحيحة، لم يتم حفظ أي تغيير.');
                $this->redirect($back);
            }
            $sectionsJson = json_encode(array_values($decoded), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            $sectionsJson = $page['sections_json'] ?? null;
        }

        $formEnabled = (int) ($data['form_enabled'] ?? 0);
        $formConfig = [
            'enabled' => $formEnabled === 1,
            'type' => 'contact',
            'title_ar' => $data['form_title_ar'] ?? '',
            'title_en' => $data['form_title_en'] ?? '',
            'desc_ar' => $data['form_desc_ar'] ?? '',
            'desc_en' => $data['form_desc_en'] ?? '',
            'require_email' => (bool) ($data['form_require_email'] ?? 1),
            'require_subject' => (bool) ($data['form_require_subject'] ?? 1),
            'success_msg_ar' => $data['form_success_msg_ar'] ?? '',
            'success_msg_en' => $data['form_success_msg_en'] ?? '',
        ];
        $formConfigJson = json_encode($formConfig, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        ContentPage::update($page['id'], [
            'title' => $title,
            'title_en' => $titleEn,
            'content' => $content,
            'content_en' => $contentEn,
            'sections_json' => $sectionsJson,
            'form_enabled' => $formEnabled,
            'form_config_json' => $formConfigJson,
        ]);

        Session::flash('success', 'تم تحديث محتوى الصفحة والنموذج والأقسام بنجاح.');
        $this->redirect($back);
    }
}
