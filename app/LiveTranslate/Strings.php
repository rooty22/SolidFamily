<?php

namespace App\LiveTranslate;

/**
 * Interface strings of the popup, in Arabic and English (English is the fallback for every other language).
 */
final class Strings
{
    public static function all(string $lang): array
    {
        $en = [
            'toggle' => 'Translate',
            'topbar' => 'Live translate',
            'toggleOn' => 'Translate mode is on. Click any text. Hold Ctrl to follow links.',
            'toggleOff' => 'Translate mode is off.',
            'title' => 'Translate text',
            'original' => 'Text on the page',
            'languages' => 'Translations',
            'where' => 'Where it will be saved',
            'selectAll' => 'Select all',
            'openEditor' => 'Open in editor',
            'noSource' => 'This text is not stored in the database (it is typed in the page template), it will be saved as a site-wide translation.',
            'alsoDictionary' => 'Also save as a site-wide translation for this exact text',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'saving' => 'Saving…',
            'saved' => 'Saved.',
            'loading' => 'Looking for the text…',
            'error' => 'Something went wrong. Please try again.',
            'nothingToSave' => 'Select at least one place to save, or fill in a translation.',
            'hint' => 'Ctrl + Enter to save · Esc to close',
            'clickToTranslate' => 'Click to translate',
            'empty' => 'empty = keeps what is stored',
            'type_field' => 'Content',
            'type_line' => 'List item / paragraph',
            'type_setting' => 'Setting',
            'type_lang' => 'Static text',
        ];

        $ar = [
            'toggle' => 'ترجمة',
            'topbar' => 'ترجمة فورية',
            'toggleOn' => 'وضع الترجمة مفعّل. اضغط على أي نص. اضغط Ctrl مع الرابط للتنقل.',
            'toggleOff' => 'تم إيقاف وضع الترجمة.',
            'title' => 'ترجمة النص',
            'original' => 'النص في الصفحة',
            'languages' => 'الترجمات',
            'where' => 'مكان الحفظ',
            'selectAll' => 'تحديد الكل',
            'openEditor' => 'فتح في المحرر',
            'noSource' => 'هذا النص غير مخزّن في قاعدة البيانات (مكتوب داخل قالب الصفحة)، وسيُحفظ كترجمة عامة للموقع.',
            'alsoDictionary' => 'احفظه أيضًا كترجمة عامة لهذا النص بالتحديد',
            'save' => 'حفظ',
            'cancel' => 'إلغاء',
            'saving' => 'جارٍ الحفظ…',
            'saved' => 'تم الحفظ.',
            'loading' => 'جارٍ البحث عن النص…',
            'error' => 'حدث خطأ. حاول مرة أخرى.',
            'nothingToSave' => 'اختر مكانًا واحدًا للحفظ على الأقل، أو اكتب ترجمة.',
            'hint' => 'Ctrl + Enter للحفظ · Esc للإغلاق',
            'clickToTranslate' => 'اضغط للترجمة',
            'empty' => 'فارغ = يبقى المخزّن كما هو',
            'type_field' => 'محتوى',
            'type_line' => 'بند / فقرة',
            'type_setting' => 'إعداد',
            'type_lang' => 'نص ثابت',
        ];

        return strtolower(substr($lang, 0, 2)) === 'ar' ? $ar : $en;
    }

    public static function get(string $key, string $lang): string
    {
        return self::all($lang)[$key] ?? $key;
    }
}
