<?php

require_once __DIR__ . '/../app/bootstrap.php';

$pdo = \App\Core\Database::connection();

echo "Checking content_pages table schema...\n";

$cols = $pdo->query('DESCRIBE content_pages')->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('form_enabled', $cols)) {
    $pdo->exec('ALTER TABLE content_pages ADD COLUMN form_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER sections_json');
    echo "- Added form_enabled column.\n";
} else {
    echo "- form_enabled already exists.\n";
}

if (!in_array('form_config_json', $cols)) {
    $pdo->exec('ALTER TABLE content_pages ADD COLUMN form_config_json LONGTEXT NULL AFTER form_enabled');
    echo "- Added form_config_json column.\n";
} else {
    echo "- form_config_json already exists.\n";
}

// Ensure default contact form configuration exists on 'contact' page
$defaultContactForm = json_encode([
    'enabled' => true,
    'type' => 'contact',
    'title_ar' => 'نموذج التواصل والاستفسارات الرسمية',
    'title_en' => 'Official Inquiries & Contact Form',
    'desc_ar' => 'نسعد باستقبال استفساراتكم وملاحظاتكم وسيقوم فريق أمانة الصندوق بمتابعتها والرد عليكم في أقرب وقت.',
    'desc_en' => 'We welcome your inquiries, feedback, and questions. The fund committee will get back to you promptly.',
    'require_email' => true,
    'require_subject' => true,
    'success_msg_ar' => 'تم إرسال رسالتكم بنجاح وسيتواصل معكم فريق إدارة الصندوق.',
    'success_msg_en' => 'Your message has been received successfully. We will follow up shortly.'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$stmt = $pdo->prepare("UPDATE content_pages SET form_enabled = 1, form_config_json = ? WHERE slug = 'contact'");
$stmt->execute([$defaultContactForm]);
echo "- Default contact form configured for /page/contact.\n";

echo "Migration finished successfully.\n";
