<?php

require __DIR__ . '/../app/bootstrap.php';

$pdo = \App\Core\Database::connection();

$email = $argv[1] ?? 'admin@sandouk.local';
$password = $argv[2] ?? 'Admin@12345';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('SELECT id, name FROM admins WHERE email = ?');
$stmt->execute([$email]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    $update = $pdo->prepare('UPDATE admins SET password = ? WHERE id = ?');
    $update->execute([$hash, $admin['id']]);
    echo "\n=============================================\n";
    echo " تم إعادة تعيين كلمة مرور الأدمن بنجاح!\n";
    echo " البريد: {$email}\n";
    echo " كلمة المرور الجديدة: {$password}\n";
    echo "=============================================\n";
} else {
    $insert = $pdo->prepare('INSERT INTO admins (name, email, password) VALUES (?, ?, ?)');
    $insert->execute(['مدير النظام', $email, $hash]);
    echo "\n=============================================\n";
    echo " تم إنشاء حساب الأدمن الجديد بنجاح!\n";
    echo " البريد: {$email}\n";
    echo " كلمة المرور: {$password}\n";
    echo "=============================================\n";
}

// Clear any active rate limit blocks
$pdo->exec('TRUNCATE TABLE rate_limits');
echo " تم فك أي حظر مؤقت بنجاح (rate_limits cleared).\n\n";

echo "قائمة حسابات الأدمن المسجلة في قاعدة البيانات:\n";
$all = $pdo->query('SELECT id, name, email FROM admins')->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $a) {
    echo " - [#{$a['id']}] {$a['name']} ({$a['email']})\n";
}
echo "\nيمكنك تسجيل الدخول الآن مباشرة عبر المتصفح.\n";
