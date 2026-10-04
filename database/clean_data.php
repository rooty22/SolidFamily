<?php

/**
 * Script to wipe all dummy / fake data for production deployment.
 * Keeps:
 *  - `admins` (admin accounts)
 *  - `settings` (site & financial configuration)
 *  - `content_pages` (static CMS pages)
 */

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
        $db['username'],
        $db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "========================================\n";
    echo "  صندوق عائلي - تنظيف البيانات الوهمية  \n";
    echo "========================================\n\n";

    // Disable foreign key checks for clean truncation and resetting auto-increments
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    $tablesToTruncate = [
        'contact_messages',
        'founding_payments',
        'founding_amounts',
        'loan_installments',
        'loans',
        'loan_requests',
        'monthly_subscriptions',
        'share_lots',
        'share_requests',
        'transactions',
        'notifications',
        'otp_codes',
        'rate_limits',
        'members',
    ];

    foreach ($tablesToTruncate as $table) {
        $pdo->exec("TRUNCATE TABLE `{$table}`;");
        echo "✓ تم مسح جدول: {$table}\n";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "\n----------------------------------------\n";
    echo "حالة الجداول بعد التنظيف:\n";
    echo "----------------------------------------\n";

    $allTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($allTables as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        echo str_pad($t, 30) . " : " . $count . " سجل\n";
    }

    echo "\n✓ تم الإبقاء على حساب الأدمن والإعدادات وصفحات المحتوى بنجاح.\n";
    echo "✓ جاهز للرفع على الإنتاج (Production Ready)!\n";

} catch (Exception $e) {
    echo "حدث خطأ: " . $e->getMessage() . "\n";
}
