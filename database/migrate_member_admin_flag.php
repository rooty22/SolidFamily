<?php

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// 1. Add is_admin column to members if it doesn't exist
$cols = $pdo->query("SHOW COLUMNS FROM members LIKE 'is_admin'")->fetchAll();
if (empty($cols)) {
    $pdo->exec("ALTER TABLE members ADD COLUMN is_admin TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER subscription_due_day");
    echo "تمت إضافة عمود is_admin إلى جدول members.\n";
} else {
    echo "عمود is_admin موجود بالفعل في جدول members.\n";
}

// 2. Fetch all admin emails to link matching members
$adminEmails = $pdo->query("SELECT email FROM admins")->fetchAll(PDO::FETCH_COLUMN);
$adminEmails[] = 'admin@sandouk.local';
$adminEmails = array_values(array_unique(array_filter(array_map('trim', $adminEmails))));

// 3. Mark matching members as is_admin = 1
$markedCount = 0;
foreach ($adminEmails as $email) {
    $stmt = $pdo->prepare("UPDATE members SET is_admin = 1 WHERE email = ? OR national_id = ?");
    $stmt->execute([$email, $email]);
    $markedCount += $stmt->rowCount();
}
echo "تم تحديث {$markedCount} من سجلات المشتركين ووسمها كحسابات إدارية.\n";

// 4. Cancel any loan requests belonging to admin members
$adminMemberIds = $pdo->query("SELECT id FROM members WHERE is_admin = 1")->fetchAll(PDO::FETCH_COLUMN);
if (!empty($adminMemberIds)) {
    $in = implode(',', array_map('intval', $adminMemberIds));
    $stmt = $pdo->prepare("UPDATE loan_requests SET status = 'rejected', admin_note = 'ملغي تلقائياً: حساب إداري غير مؤهل للحصول على قروض' WHERE member_id IN ($in) AND status != 'rejected'");
    $stmt->execute();
    $cancelled = $stmt->rowCount();
    echo "تم إلغاء {$cancelled} طلب قرض مرتبط بحسابات إدارية.\n";
}

echo "اكتملت عملية التحديث بنجاح.\n";
