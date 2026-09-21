<?php

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "Connecting to MySQL sandouk_db for settings migration...\n";

// Default Menu Items
$defaultMenu = [
    [
        'id' => 'home',
        'title_ar' => 'الرئيسية',
        'title_en' => 'Home',
        'url' => '/',
        'target' => '_self',
        'enabled' => true,
    ],
    [
        'id' => 'features',
        'title_ar' => 'المميزات',
        'title_en' => 'Features',
        'url' => '/#features',
        'target' => '_self',
        'enabled' => true,
    ],
    [
        'id' => 'calculator',
        'title_ar' => 'حاسبة القروض',
        'title_en' => 'Calculator',
        'url' => '/#calculator',
        'target' => '_self',
        'enabled' => true,
    ],
    [
        'id' => 'charter',
        'title_ar' => 'ميثاق الصندوق',
        'title_en' => 'Charter',
        'url' => '/#charter',
        'target' => '_self',
        'enabled' => true,
    ],
    [
        'id' => 'about',
        'title_ar' => 'من نحن',
        'title_en' => 'About Us',
        'url' => '/page/about',
        'target' => '_self',
        'enabled' => true,
    ],
    [
        'id' => 'terms',
        'title_ar' => 'شروط الاستخدام',
        'title_en' => 'Terms',
        'url' => '/page/terms',
        'target' => '_self',
        'enabled' => true,
    ],
    [
        'id' => 'contact',
        'title_ar' => 'تواصل معنا',
        'title_en' => 'Contact Us',
        'url' => '/page/contact',
        'target' => '_self',
        'enabled' => true,
    ],
];

$defaultSettings = [
    // Branding & Identity
    'site_name' => 'صندوق عائلي',
    'site_name_en' => 'Family Solidarity Fund',
    'site_slogan' => 'المنظومة المالية والتكافلية للأسرة',
    'site_slogan_en' => 'Family Financial & Solidarity Ecosystem',
    'site_logo' => '',
    'logo_icon' => 'safe2-fill',

    // Contact & Social
    'official_phone' => '+966500000000',
    'official_email' => 'info@sandouk.local',
    'official_whatsapp' => '+966500000000',
    'official_address' => 'المملكة العربية السعودية - الرياض',
    'official_address_en' => 'Riyadh, Kingdom of Saudi Arabia',
    'social_twitter' => 'https://x.com',
    'social_instagram' => 'https://instagram.com',
    'social_telegram' => 'https://t.me',

    // Homepage Section Visibility (1 = visible, 0 = hidden)
    'section_hero_enabled' => '1',
    'section_stats_enabled' => '1',
    'section_features_enabled' => '1',
    'section_calculator_enabled' => '1',
    'section_charter_enabled' => '1',
    'section_hadith_enabled' => '1',
    'section_cta_enabled' => '1',

    // Navigation Menu JSON
    'navigation_menu_json' => json_encode($defaultMenu, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),

    // SEO & Webmaster Suite
    'seo_meta_title' => 'صندوق العائلة التكافلي - البوابة الرسمية',
    'seo_meta_title_en' => 'Family Solidarity Fund - Official Portal',
    'seo_meta_description' => 'نظام إلكتروني متكامل لإدارة اشتراكات الأسهم والقروض الحسنة لأبناء العائلة بشفافية وأمان تام.',
    'seo_meta_description_en' => 'An integrated digital platform for family shares, monthly subscriptions, and 0% interest benevolent loans.',
    'seo_meta_keywords' => 'صندوق عائلي, تكافل, قروض حسنة, أسهم, عائلة, تمويل ميسر',
    'seo_meta_keywords_en' => 'family fund, solidarity, benevolent loans, shares, family finance',
    'seo_og_image' => '',
    'seo_canonical_url' => '',
    'seo_google_analytics' => '',
    'seo_custom_header_scripts' => '',
    'seo_custom_footer_scripts' => '',

    // Language Controls
    'site_language_mode' => 'multi', // 'multi' or 'single'
    'site_default_language' => 'ar', // 'ar' or 'en'

    // Financial Bylaws
    'share_value' => '1000',
    'founding_fee_per_share' => '500',
    'loan_admin_fee_percent' => '2',
    'max_loan_ratio' => '10',
    'subscription_due_day' => '10',
];

$stmt = $pdo->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = IF(value IS NULL OR value = "", VALUES(value), value)');
foreach ($defaultSettings as $k => $v) {
    $stmt->execute([$k, $v]);
}

// Make sure upload directory exists
$uploadDir = __DIR__ . '/../public/uploads/branding';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

echo "All settings successfully migrated and initialized!\n";
