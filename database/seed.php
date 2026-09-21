<?php

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ---- Default admin ----
$adminEmail = 'admin@sandouk.local';
$adminPassword = 'Admin@12345';
$stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
$stmt->execute([$adminEmail]);
if (!$stmt->fetch()) {
    $pdo->prepare('INSERT INTO admins (name, email, password) VALUES (?, ?, ?)')
        ->execute(['مدير النظام', $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT)]);
    echo "تم إنشاء حساب الأدمن الافتراضي:\n  البريد: {$adminEmail}\n  كلمة المرور: {$adminPassword}\n\n";
} else {
    echo "حساب الأدمن موجود بالفعل ({$adminEmail}).\n\n";
}

// ---- Default settings ----
$settings = [
    'site_name' => 'صندوق عائلي',
    'share_value' => '1000',
    'founding_fee_per_share' => '500',
    'loan_admin_fee_percent' => '2',
    'subscription_due_day' => '10',
    'official_phone' => '+966500000000',
    'official_email' => 'info@sandouk.local',
];
$insertSetting = $pdo->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = value');
foreach ($settings as $key => $value) {
    $insertSetting->execute([$key, $value]);
}
echo "تم ضبط الإعدادات الافتراضية.\n\n";

// ---- Default content pages ----
$pages = [
    'about' => ['title' => 'من نحن', 'content' => 'صندوق عائلي هو نظام إلكتروني لإدارة الاشتراكات والأسهم والقروض الخاصة بأفراد العائلة بطريقة منظمة وشفافة.'],
    'contact' => ['title' => 'اتصل بنا', 'content' => 'يمكنكم التواصل معنا عبر نموذج الدعم الفني داخل حسابكم أو عبر البريد الإلكتروني الرسمي.'],
    'privacy' => ['title' => 'سياسة الخصوصية', 'content' => 'نلتزم بالحفاظ على خصوصية بيانات المشتركين وعدم مشاركتها مع أي جهة خارجية دون إذن.'],
    'terms' => ['title' => 'شروط الاستخدام', 'content' => 'باستخدامك لهذا النظام فإنك توافق على الالتزام بالسداد في المواعيد المحددة وبسياسات الصندوق العائلي.'],
    'loan_commitment' => ['title' => 'تعهد سداد القرض', 'content' => 'أتعهد أنا المشترك بسداد قيمة القرض على الأقساط المحددة في مواعيدها، وأوافق على جميع شروط وأحكام السداد الخاصة بصندوق العائلة.'],
];
$insertPage = $pdo->prepare('INSERT INTO content_pages (slug, title, content) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE slug = slug');
foreach ($pages as $slug => $page) {
    $insertPage->execute([$slug, $page['title'], $page['content']]);
}
echo "تم إنشاء صفحات المحتوى الافتراضية.\n\n";

echo "اكتمل تجهيز البيانات الأولية بنجاح.\n";
