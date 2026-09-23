<?php

/**
 * Live Translate.
 *
 * Turn on "Live translate" in the dashboard, click any text on the site or in the dashboard, write it in every
 * language in a popup and save it where it is stored. See app/LiveTranslate/README.md.
 */
return [
    /** Languages of the popup, in this order. The default one is stored WITHOUT a suffix (title / title_en). */
    'languages' => [
        'ar' => ['name' => 'العربية', 'flag' => '🇸🇦', 'rtl' => true],
        'en' => ['name' => 'English', 'flag' => '🇬🇧', 'rtl' => false],
    ],
    'default' => 'ar',

    /**
     * Tables whose bilingual columns can be edited in place. A field is a group of columns of the same base name:
     * "title" + "title_en" (default language without suffix) or "title_ar" + "title_en" (all suffixed).
     * Every table needs an integer `id` primary key.
     *
     * label: name shown in the popup, per language (English is the fallback).
     * edit:  link to the full editor of the row, {id} and {slug} are replaced.
     * name:  fields tried, in this order, to show the row's name in the popup.
     * json:  columns holding JSON with bilingual keys ("title_ar" / "title_en", "badge" / "badge_en") at any depth.
     */
    'tables' => [
        'content_pages' => [
            'label' => ['ar' => 'صفحة', 'en' => 'Page'],
            'edit' => '/admin/content/{slug}/edit',
            'name' => ['title'],
            'json' => ['sections_json', 'form_config_json'],
        ],
    ],

    /** Where the bilingual settings (site_name + site_name_en, navigation_menu_json, ...) are edited. */
    'settings_edit' => '/admin/settings',

    /** Classes of the dashboard button (same look as the language switcher of the top bar). */
    'button_class' => 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-xs',

    /** Most candidates listed in the popup for one text. */
    'limit' => 40,
];
