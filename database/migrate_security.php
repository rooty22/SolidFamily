<?php

// Idempotent, additive migration for the security hardening (login/OTP/form throttling).
// Safe to run on an existing database: php database/migrate_security.php

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "Connecting to MySQL {$db['database']} for security migration...\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rkey CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_rkey_created (rkey, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "- rate_limits table ready.\n";

// Fix the malformed phone number that older versions of seed.php inserted ("966500000000+").
$fixed = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = 'official_phone' AND value = ?");
$fixed->execute(['+966500000000', '966500000000+']);
if ($fixed->rowCount() > 0) {
    echo "- Fixed malformed official_phone setting.\n";
}

echo "Security migration finished successfully.\n";
