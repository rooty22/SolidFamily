<?php

namespace App\Models;

use App\Core\Model;

class ContentPage extends Model
{
    protected static string $table = 'content_pages';

    public static function bySlug(string $slug): ?array
    {
        return self::findBy('slug', $slug);
    }

    public static function getTitle(array $page): string
    {
        if (function_exists('is_en') && is_en() && !empty($page['title_en'])) {
            return $page['title_en'];
        }
        return $page['title'] ?? '';
    }

    public static function getContent(array $page): string
    {
        if (function_exists('is_en') && is_en() && !empty($page['content_en'])) {
            return $page['content_en'];
        }
        return $page['content'] ?? '';
    }

    public static function getSections(array $page): array
    {
        if (empty($page['sections_json'])) {
            return [];
        }
        $decoded = json_decode($page['sections_json'], true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function isFormEnabled(array $page): bool
    {
        if (!empty($page['form_enabled']) && (int)$page['form_enabled'] === 1) {
            return true;
        }
        // Always enable on contact page by default
        return ($page['slug'] ?? '') === 'contact';
    }

    public static function getFormConfig(array $page): array
    {
        $default = [
            'enabled' => self::isFormEnabled($page),
            'type' => 'contact',
            'title_ar' => 'نموذج التواصل والاستفسارات',
            'title_en' => 'Inquiries & Contact Form',
            'desc_ar' => 'نسعد باستقبال استفساراتكم وملاحظاتكم وسيقوم فريق الصندوق بالرد عليكم.',
            'desc_en' => 'We welcome your questions and suggestions.',
            'require_email' => true,
            'require_subject' => true,
            'success_msg_ar' => 'تم إرسال رسالتكم بنجاح وسيتواصل معكم فريق الصندوق.',
            'success_msg_en' => 'Your message has been sent successfully.',
        ];

        if (empty($page['form_config_json'])) {
            return $default;
        }

        $decoded = json_decode($page['form_config_json'], true);
        if (!is_array($decoded)) {
            return $default;
        }

        return array_merge($default, $decoded);
    }
}
