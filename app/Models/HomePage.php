<?php

namespace App\Models;

use App\Core\Lang;

/**
 * The public home page as an ordered list of sections, stored as JSON in the setting "homepage_sections_json".
 *
 * Built-in sections (hero, features, calculator, charter, hadith, cta) exist at most once each; the admin can add any
 * number of custom ones (image_text, text, cards, image). Every text field is stored twice: "<field>_ar" and
 * "<field>_en". While the setting is empty the page falls back to defaults() — the texts the page always had.
 */
class HomePage
{
    public const SETTING = 'homepage_sections_json';

    public const MAX_SECTIONS = 40;
    public const MAX_ITEMS = 24;

    /**
     * Per type: its bilingual text fields (key => max length; long fields are > 300), whether it holds an image,
     * a background choice, a link, and a list of items (null, or 'cards' with icon+color, or 'list' without).
     */
    public const TYPES = [
        'hero'       => ['builtin' => true,  'label' => ['ar' => 'الهيرو والبنر الترحيبي', 'en' => 'Hero banner'], 'icon' => 'image',
                         'text' => ['badge' => 200, 'title' => 300, 'title_2' => 300, 'desc' => 2000, 'stat_shares' => 100, 'stat_share_val' => 100, 'stat_members' => 100, 'stat_transparency' => 100],
                         'image' => true, 'position' => false, 'bg' => false, 'link' => false, 'items' => null],
        'features'   => ['builtin' => true,  'label' => ['ar' => 'مزايا وركائز الصندوق', 'en' => 'Features / pillars'], 'icon' => 'stars',
                         'text' => ['kicker' => 200, 'title' => 300, 'desc' => 2000],
                         'image' => false, 'position' => false, 'bg' => false, 'link' => false, 'items' => 'cards'],
        'calculator' => ['builtin' => true,  'label' => ['ar' => 'حاسبة القروض والاشتراكات', 'en' => 'Loan calculator'], 'icon' => 'calculator',
                         'text' => ['kicker' => 200, 'title' => 300, 'desc' => 2000, 'disclaimer' => 2000],
                         'image' => false, 'position' => false, 'bg' => false, 'link' => false, 'items' => null],
        'charter'    => ['builtin' => true,  'label' => ['ar' => 'ميثاق ولائحة الصندوق', 'en' => 'Fund charter'], 'icon' => 'file-earmark-ruled',
                         'text' => ['kicker' => 200, 'title' => 300, 'desc' => 2000, 'button_text' => 100],
                         'image' => false, 'position' => false, 'bg' => false, 'link' => true, 'items' => 'list'],
        'hadith'     => ['builtin' => true,  'label' => ['ar' => 'كرت الاقتباس / الحديث', 'en' => 'Quote card'], 'icon' => 'quote',
                         'text' => ['title' => 300, 'quote' => 3000, 'source' => 300],
                         'image' => false, 'position' => false, 'bg' => false, 'link' => false, 'items' => null],
        'cta'        => ['builtin' => true,  'label' => ['ar' => 'بانر الدعوة للانضمام', 'en' => 'Join call-to-action'], 'icon' => 'megaphone',
                         'text' => ['badge' => 200, 'title' => 300, 'desc' => 2000],
                         'image' => false, 'position' => false, 'bg' => false, 'link' => false, 'items' => null],
        'image_text' => ['builtin' => false, 'label' => ['ar' => 'صورة + نص', 'en' => 'Image + text'], 'icon' => 'layout-split',
                         'text' => ['kicker' => 200, 'title' => 300, 'body' => 10000, 'button_text' => 100],
                         'image' => true, 'position' => true, 'bg' => true, 'link' => true, 'items' => null],
        'text'       => ['builtin' => false, 'label' => ['ar' => 'نص فقط', 'en' => 'Text block'], 'icon' => 'text-paragraph',
                         'text' => ['kicker' => 200, 'title' => 300, 'body' => 10000, 'button_text' => 100],
                         'image' => false, 'position' => false, 'bg' => true, 'link' => true, 'items' => null],
        'cards'      => ['builtin' => false, 'label' => ['ar' => 'شبكة بطاقات', 'en' => 'Cards grid'], 'icon' => 'grid-3x2-gap',
                         'text' => ['kicker' => 200, 'title' => 300, 'desc' => 2000],
                         'image' => false, 'position' => false, 'bg' => true, 'link' => false, 'items' => 'cards'],
        'image'      => ['builtin' => false, 'label' => ['ar' => 'صورة عريضة', 'en' => 'Full-width image'], 'icon' => 'card-image',
                         'text' => ['caption' => 300],
                         'image' => true, 'position' => false, 'bg' => true, 'link' => true, 'items' => null],
    ];

    public const COLORS = ['emerald', 'amber', 'blue', 'purple', 'rose', 'sky', 'teal', 'indigo', 'slate'];
    public const BACKGROUNDS = ['white', 'light', 'dark', 'brand'];

    /** Tailwind classes of a section background: [section classes, heading color, body color, kicker color]. */
    public static function bgClasses(string $bg): array
    {
        return [
            'white' => ['bg-white border-b border-slate-200', 'text-slate-900', 'text-slate-600', 'text-brand-600'],
            'light' => ['bg-slate-50 border-b border-slate-200', 'text-slate-900', 'text-slate-600', 'text-brand-600'],
            'dark'  => ['bg-slate-900 text-white', 'text-white', 'text-slate-300', 'text-gold-400'],
            'brand' => ['bg-gradient-to-r from-slate-950 via-brand-950 to-slate-950 text-white', 'text-white', 'text-slate-300', 'text-brand-300'],
        ][$bg] ?? ['bg-white border-b border-slate-200', 'text-slate-900', 'text-slate-600', 'text-brand-600'];
    }

    /** Sections to render/edit: the saved list, or the defaults while nothing was saved yet. */
    public static function sections(): array
    {
        $json = (string) site_setting(self::SETTING, '');
        if ($json !== '') {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return self::sanitize($decoded);
            }
        }
        return self::defaults();
    }

    /** Text of a bilingual field in the visitor's language (Arabic when the English one is empty). */
    public static function t(array $section, string $field): string
    {
        if (is_en() && ($section[$field . '_en'] ?? '') !== '') {
            return $section[$field . '_en'];
        }
        return (string) ($section[$field . '_ar'] ?? '');
    }

    /** Public URL of a stored image: uploads resolve to the site root, external links pass through. */
    public static function imageUrl(string $image): string
    {
        if ($image === '') {
            return '';
        }
        return preg_match('#^https?://#i', $image) ? $image : url($image);
    }

    /**
     * Rebuilds a submitted list keeping only known types and keys; built-in types are kept once.
     * Missing fields of a built-in section are filled from its defaults.
     */
    public static function sanitize(array $raw): array
    {
        $defaults = [];
        foreach (self::defaults() as $d) {
            $defaults[$d['type']] = $d;
        }

        $out = [];
        $seen = [];
        foreach (array_slice($raw, 0, self::MAX_SECTIONS) as $sec) {
            $type = is_array($sec) ? ($sec['type'] ?? '') : '';
            if (!is_string($type) || !isset(self::TYPES[$type])) {
                continue;
            }
            $schema = self::TYPES[$type];
            if ($schema['builtin']) {
                if (isset($seen[$type])) {
                    continue;
                }
                $seen[$type] = true;
            }
            $base = $defaults[$type] ?? self::blank($type);

            $id = $schema['builtin'] ? $type : preg_replace('/[^A-Za-z0-9_\-]/', '', (string) ($sec['id'] ?? ''));
            $clean = ['id' => $id !== '' ? $id : 'sec_' . bin2hex(random_bytes(4)), 'type' => $type, 'enabled' => !empty($sec['enabled'])];

            foreach ($schema['text'] as $field => $max) {
                foreach (['_ar', '_en'] as $suffix) {
                    $clean[$field . $suffix] = array_key_exists($field . $suffix, $sec)
                        ? self::str($sec[$field . $suffix], $max)
                        : ($base[$field . $suffix] ?? '');
                }
            }
            if ($schema['image']) {
                $clean['image'] = array_key_exists('image', $sec) ? self::image($sec['image']) : ($base['image'] ?? '');
            }
            if ($schema['position']) {
                $clean['image_position'] = ($sec['image_position'] ?? '') === 'end' ? 'end' : 'start';
            }
            if ($schema['bg']) {
                $clean['bg'] = in_array($sec['bg'] ?? '', self::BACKGROUNDS, true) ? $sec['bg'] : ($base['bg'] ?? 'white');
            }
            if ($schema['link']) {
                $link = array_key_exists('button_url', $sec) ? self::str($sec['button_url'], 255) : ($base['button_url'] ?? '');
                $clean['button_url'] = is_safe_link($link) ? $link : '';
            }
            if ($type === 'hero') {
                $clean['show_stats'] = array_key_exists('show_stats', $sec) ? !empty($sec['show_stats']) : ($base['show_stats'] ?? true);
            }
            if ($schema['items'] !== null) {
                $items = array_key_exists('items', $sec) && is_array($sec['items']) ? $sec['items'] : ($base['items'] ?? []);
                $clean['items'] = [];
                foreach (array_slice($items, 0, self::MAX_ITEMS) as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $cleanItem = [
                        'title_ar' => self::str($item['title_ar'] ?? '', 300),
                        'title_en' => self::str($item['title_en'] ?? '', 300),
                        'body_ar' => self::str($item['body_ar'] ?? '', 3000),
                        'body_en' => self::str($item['body_en'] ?? '', 3000),
                    ];
                    if ($schema['items'] === 'cards') {
                        $cleanItem['icon'] = self::icon($item['icon'] ?? '');
                        $cleanItem['color'] = in_array($item['color'] ?? '', self::COLORS, true) ? $item['color'] : 'emerald';
                    }
                    $clean['items'][] = $cleanItem;
                }
            }
            $out[] = $clean;
        }
        return $out;
    }

    /** An empty section of a type, as the builder adds it. */
    public static function blank(string $type): array
    {
        $schema = self::TYPES[$type];
        $sec = ['id' => $type, 'type' => $type, 'enabled' => true];
        foreach (array_keys($schema['text']) as $field) {
            $sec[$field . '_ar'] = '';
            $sec[$field . '_en'] = '';
        }
        if ($schema['image']) {
            $sec['image'] = '';
        }
        if ($schema['position']) {
            $sec['image_position'] = 'start';
        }
        if ($schema['bg']) {
            $sec['bg'] = $type === 'image' ? 'white' : 'light';
        }
        if ($schema['link']) {
            $sec['button_url'] = '';
        }
        if ($schema['items'] !== null) {
            $sec['items'] = [];
        }
        return $sec;
    }

    /** The home page as it was before it became editable (texts from the language files / Live Translate). */
    public static function defaults(): array
    {
        $l = fn(string $key): array => ['ar' => Lang::inLocale('ar', $key), 'en' => Lang::inLocale('en', $key)];
        $pair = fn(string $field, array $v): array => [$field . '_ar' => $v['ar'], $field . '_en' => $v['en']];
        $on = fn(string $name): bool => in_array(site_setting('section_' . $name . '_enabled', '1'), ['1', 'true'], true);

        $features = [];
        foreach ([1 => ['pie-chart-fill', 'emerald'], 2 => ['cash-coin', 'amber'], 3 => ['bank2', 'blue'], 4 => ['shield-check', 'purple']] as $n => [$icon, $color]) {
            $features[] = ['icon' => $icon, 'color' => $color] + $pair('title', $l("pillar_{$n}_title")) + $pair('body', $l("pillar_{$n}_desc"));
        }

        return [
            ['id' => 'hero', 'type' => 'hero', 'enabled' => $on('hero'), 'image' => '', 'show_stats' => $on('stats')]
                + $pair('badge', $l('hero_badge')) + $pair('title', $l('hero_title_1')) + $pair('title_2', $l('hero_title_2'))
                + $pair('desc', $l('hero_desc')) + $pair('stat_shares', $l('stat_shares')) + $pair('stat_share_val', $l('stat_share_val'))
                + $pair('stat_members', $l('stat_members')) + $pair('stat_transparency', $l('stat_transparency')),
            ['id' => 'features', 'type' => 'features', 'enabled' => $on('features'), 'items' => $features]
                + $pair('kicker', $l('pillars_title')) + $pair('title', $l('pillars_sub')) + $pair('desc', $l('pillars_desc')),
            ['id' => 'calculator', 'type' => 'calculator', 'enabled' => $on('calculator')]
                + $pair('kicker', $l('sim_badge')) + $pair('title', $l('sim_title')) + $pair('desc', $l('sim_desc'))
                + $pair('disclaimer', $l('sim_disclaimer')),
            [
                'id' => 'charter', 'type' => 'charter', 'enabled' => $on('charter'), 'button_url' => '/page/terms',
                'items' => [
                    ['title_ar' => 'لا فوائد ربوية على الإطلاق', 'title_en' => 'Zero Interest Financing',
                     'body_ar' => 'جميع القروض ميسرة لوجه الله وصلة للرحم، مع مصاريف إدارية رمزية مقطوعة لتغطية التشغيل.',
                     'body_en' => 'All loans are completely interest-free with nominal fixed administrative expenses.'],
                    ['title_ar' => 'حفظ الحقوق وتوثيقها', 'title_en' => 'Documented Financial Rights',
                     'body_ar' => 'كل سهم ومبلغ سداد موثق بسجل مالي مركزي يحمي حقوق كل فرد وورثته مستقبلاً.',
                     'body_en' => 'Every share and payment is permanently registered in an audited central ledger.'],
                    ['title_ar' => 'لجنة إشرافية مستقلة', 'title_en' => 'Elected Supervisory Committee',
                     'body_ar' => 'لجنة من أعيان وخبراء العائلة تتولى إدارة الطلبات ومراجعة الميزانيات واعتماد التقارير السنوية.',
                     'body_en' => 'A dedicated family council manages requests, audits budgets, and oversees disbursements.'],
                ],
            ] + $pair('kicker', $l('charter'))
                + ['title_ar' => 'تنظيم عادل يحفظ صلة الرحم ويديم البركة', 'title_en' => 'Fair Bylaws Protecting Kinship & Lasting Prosperity',
                   'desc_ar' => 'يقوم الصندوق على مبادئ التكافل الإسلامي النقي، حيث يُقرض المحتاج دون اشتراط أي زيادة، وتُستثمر المدخرات في منافذ آمنة، ليكون سداً منيعاً يحمي الأسرة من نوائب الدهر.',
                   'desc_en' => 'The Fund is built upon pure mutual solidarity, offering benevolent interest-free loans and securing family savings through structured, transparent governance.']
                + $pair('button_text', $l('terms')),
            [
                'id' => 'hadith', 'type' => 'hadith', 'enabled' => $on('hadith'),
                'title_ar' => 'صلة الرحم والبركة', 'title_en' => 'Family Ties & Blessing',
                'quote_ar' => 'قال رسول الله صلى الله عليه وسلم: "مَن سَرَّهُ أَنْ يُبْسَطَ لَهُ فِي رِزْقِهِ، وَأَنْ يُنْسَأَ لَهُ فِي أَثَرِهِ، فَلْيَصِلْ رَحِمَهُ."',
                'quote_en' => '"Whoever would like his provision to be abundant and his lifespan to be extended, let him maintain the ties of kinship."',
                'source_ar' => 'صحيح البخاري ومسلم', 'source_en' => 'Sahih Al-Bukhari & Muslim',
            ],
            [
                'id' => 'cta', 'type' => 'cta', 'enabled' => $on('cta'),
                'badge_ar' => 'بوابة العائلة الرقمية', 'badge_en' => 'Digital Family Gateway',
                'title_ar' => 'انضم اليوم وابدأ في بناء مستقبلك المالي التكافلي', 'title_en' => 'Join Today & Build Your Mutual Savings Future',
                'desc_ar' => 'سجل عضويتك للمشاركة في الاكتتاب، الاستفادة من القروض الحسنة بدون فوائد، ومتابعة حسابك بكل شفافية.',
                'desc_en' => 'Register now to participate in shares, benefit from 0% loans, and track your equity with transparency.',
            ],
        ];
    }

    private static function str($value, int $max): string
    {
        if (!is_scalar($value)) {
            return '';
        }
        return mb_substr(trim(str_replace(chr(0), '', (string) $value)), 0, $max);
    }

    private static function icon($value): string
    {
        $icon = is_string($value) ? strtolower(trim(preg_replace('/^bi-/', '', trim($value)))) : '';
        return preg_match('/^[a-z0-9\-]{1,50}$/', $icon) ? $icon : 'star';
    }

    /** An uploaded home image (uploads/home/...) or an external https/http image URL; anything else is dropped. */
    private static function image($value): string
    {
        $image = self::str($value, 500);
        if (preg_match('#^uploads/home/[A-Za-z0-9_\-]+\.(png|jpe?g|webp|gif)$#i', $image)) {
            return $image;
        }
        if (preg_match('#^https?://[^\s<>"\'`]+$#i', $image) && filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }
        return '';
    }
}
