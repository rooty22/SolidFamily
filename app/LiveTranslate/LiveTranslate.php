<?php

namespace App\LiveTranslate;

use App\Core\Auth;
use App\Core\Lang;
use App\Core\Session;

/**
 * Entry point used by the layouts and the controller: is the module on, who may use it, and the tags to print.
 *
 *  lt-runtime.js  printed for everybody as soon as the dictionary has entries (it translates the texts that are
 *                 typed straight into the views) and for the administrators (the editor reuses it).
 *  lt-editor.js   only for logged-in administrators: Translate mode, hover, popup.
 */
final class LiveTranslate
{
    public const ASSET_DIR = 'live-translate';

    /** Master switch, set from the dashboard page "Live translate". On unless it was turned off. */
    public static function enabled(): bool
    {
        try {
            return (string)site_setting('lt_enabled', '1') !== '0';
        } catch (\Throwable $e) {
            return false; // no database yet (before the migrations)
        }
    }

    public static function canUse(): bool
    {
        return self::enabled() && Auth::adminCheck();
    }

    private static function json(mixed $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    /** Public URL of a file of public/assets/live-translate (asset() adds the version). */
    private static function assetUrl(string $file): string
    {
        return asset(self::ASSET_DIR . '/' . $file);
    }

    /** Button of the dashboard top bar that turns Translate mode on and off (the top bar prints it). */
    public static function adminButton(): string
    {
        if (!self::canUse()) {
            return '';
        }
        $lang = Languages::current();
        $label = htmlspecialchars(Strings::get('topbar', $lang), ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars(Strings::get('title', $lang), ENT_QUOTES, 'UTF-8');
        $class = htmlspecialchars((string)(Languages::config()['button_class'] ?? ''), ENT_QUOTES, 'UTF-8');

        return '<button type="button" id="lt-admin-toggle" data-lt-skip title="' . $title . '" class="' . $class . '">'
            . '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>'
            . '<span>' . $label . '</span></button>';
    }

    /** Scripts to print right before </body>. */
    public static function scripts(): string
    {
        if (!self::enabled()) {
            return '';
        }

        $lang = Languages::current();
        $map = Dictionary::instance()->runtimeMap($lang);
        $can = self::canUse();
        if (!$map && !$can) {
            return '';
        }

        $html = '<script>window.LT_RUNTIME=' . self::json(['map' => (object)$map, 'lang' => $lang]) . ';</script>' . "\n"
            . '<script src="' . self::assetUrl('lt-runtime.js') . '"></script>' . "\n";

        if ($can) {
            $html .= '<script>window.LT_EDITOR=' . self::json([
                'endpoint' => url('admin/live-translate'),
                'csrf' => Session::csrfToken(),
                'lang' => $lang,
                'defaultLang' => Languages::defaultCode(),
                'languages' => Languages::describe(),
                'isAdmin' => Lang::isAdminContext(),
                'rtl' => Languages::isRtl($lang),
                'strings' => Strings::all($lang),
            ]) . ';</script>' . "\n"
                . '<script src="' . self::assetUrl('lt-editor.js') . '"></script>' . "\n";
        }
        return $html;
    }
}
