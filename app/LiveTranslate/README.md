# Live Translate (الترجمة الفورية)

Turn on **Live translate**, click any text on the site or in the dashboard, write it in every language in a popup and
save it in place. Ported from the plugin *Saint Live Translate* to this project's own storage.

## Turn it on

The code is already part of the project and is **on by default**. One optional step for an existing database:

```
php database/migrate_live_translate.php
```

It makes `settings.value` a LONGTEXT (site-wide translations can grow past the 64 KB of a TEXT column; until then the
module refuses to save instead of truncating) and stores `lt_enabled = 1`. Safe to run more than once.

1. Log in to the dashboard (`/admin`).
2. Click **ترجمة فورية / Live translate** in the top bar. Translate mode is now on.
3. Open the public site: the logged-in administrator gets a floating **ترجمة / Translate** button.
4. Hover any text (blue frame), click it, fill the languages, **Save** (or `Ctrl + Enter`). `Esc` leaves the mode,
   `Ctrl + click` follows a link.

Dashboard page: **الترجمة الفورية** in the sidebar (`/admin/live-translate`): master on/off switch, the list of
site-wide translations (delete / export JSON / import JSON).

**The dashboard button also controls the site button.** Close it in the dashboard and the floating button disappears
from the site in every open tab and after reloads; open it and it comes back (`localStorage` key `lt-button`).
The master switch on the dashboard page turns the whole module off for everybody.

## Where a text is saved

When you click a text the popup lists every place where that exact text is stored, and you choose where to save:

| Kind in the popup | Where it lives | Saved to |
|---|---|---|
| Content / list item | bilingual columns of `content_pages`: `title` + `title_en`, `content` + `content_en` (each paragraph is a line) | those columns, only the clicked line |
| Page section / form text | JSON columns `sections_json`, `form_config_json`: `title_ar` + `title_en`, `badge` + `badge_en` ... at any depth | the same JSON, only those keys |
| Setting | `site_name` + `site_name_en`, `official_address` + `official_address_en` ... | the `settings` table |
| Setting (JSON) | `navigation_menu_json` (menu items) | the same JSON, only that item |
| Static text | `resources/lang/ar.php` + `en.php` (read with `__('key')`) | a `trans_<lang>_<key>` setting; `Lang::get()` reads it before the file, the files are never touched |
| Site-wide translation | text typed straight into a view, stored nowhere | `settings.lt_dictionary` (JSON), applied in the browser by `lt-runtime.js` in both directions (ar <-> en) |

A field is any group of names that differ only by the language suffix, and the default language may have no suffix
(`title` / `title_en`) or all of them may (`title_ar` / `title_en`). Only one unambiguous place (or the row of the
page you are on) is preselected; when a text lives in several places you choose. Nothing is matched by substring,
only whole texts.

Safety: every write re-reads the stored value and refuses if it changed meanwhile; only refs the resolver itself finds
for that text are accepted; an empty language in the popup never blanks a column; tables have to be listed in
`config/live_translate.php`; JSON is rewritten with the same flags the admin forms use and only the keys of the
clicked field change; the endpoints need a logged-in administrator and the CSRF token and answer JSON errors.

## Files

```
app/LiveTranslate/                 Languages, Fields, Text, Store, Json, Dictionary, Resolver, Writer, Strings, LiveTranslate
app/Controllers/Admin/LiveTranslateController.php
app/Views/admin/live-translate/index.php
config/live_translate.php          languages, tables that can be edited in place, button style
public/assets/live-translate/      lt-runtime.js (everybody), lt-editor.js (administrators)
database/migrate_live_translate.php
```

Touched existing files (all additive): `routes/web.php` (2 JSON routes + 5 dashboard routes),
`app/Core/Lang.php` (`Lang::get()` reads the `trans_*` override first), `app/Views/admin/layout.php`,
`app/Views/admin/partials/topbar.php` (the button), `app/Views/admin/partials/sidebar.php` (the link),
`app/Views/site/layout.php` and `app/Views/site/landing-layout.php` (one `LiveTranslate::scripts()` line each).

## Add another table

Add it to `config/live_translate.php` (`tables`): any table with an integer `id` and bilingual columns; list its JSON
columns under `json`. Another language: add it under `languages` (its columns use the `_<code>` suffix).

## Notes

- Site-wide translations are applied in the browser, so they are not in the HTML source (search engines that do not
  run JavaScript see the original text). Texts stored in the database or in `resources/lang` are translated on the
  server as always, and that is what the popup offers first.
- The member area (`site/layout.php`) has no administrator session, so it only gets the site-wide translations.
- A value is saved HTML-encoded only when the values already stored in that field are.
