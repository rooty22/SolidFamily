<?php

/**
 * Migration: Roles & Permissions Package (نظام وصلاحيات لوحة التحكم)
 * 
 * Creates tables:
 * - roles: أدوار النظام وقوالب الصلاحيات
 * - permissions: الصلاحيات الفردية لكافة أقسام النظام
 * - role_permissions: ربط الأدوار بالصلاحيات
 * - admin_roles: ربط المشرفين بالأدوار
 * - admin_permissions: الصلاحيات المباشرة الإضافية للمشرفين
 * - Adds member_id to admins: يربط حساب المشرف بسجل العضو في جدول members
 * 
 * Safe and idempotent (can be executed repeatedly without loss of data).
 * Run with: php database/migrate_permissions.php
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
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: {$e->getMessage()}\n");
}

echo "=== بدء تثبيت باكدج الصلاحيات والأدوار (Roles & Permissions Package) ===\n\n";

// 1. Create `roles` table
$pdo->exec("
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(60) NOT NULL UNIQUE,
    `label_ar` VARCHAR(150) NOT NULL,
    `label_en` VARCHAR(150) NOT NULL,
    `description` VARCHAR(255) NULL,
    `is_system` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "✓ جدول الأدوار (roles) جاهز.\n";

// 2. Create `permissions` table
$pdo->exec("
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(80) NOT NULL UNIQUE,
    `label_ar` VARCHAR(150) NOT NULL,
    `label_en` VARCHAR(150) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `module_label_ar` VARCHAR(100) NOT NULL,
    `module_label_en` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "✓ جدول الصلاحيات (permissions) جاهز.\n";

// 3. Create `role_permissions` pivot table
$pdo->exec("
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_role_perm_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_role_perm_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "✓ جدول ربط الأدوار بالصلاحيات (role_permissions) جاهز.\n";

// 4. Create `admin_roles` pivot table
$pdo->exec("
CREATE TABLE IF NOT EXISTS `admin_roles` (
    `admin_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`admin_id`, `role_id`),
    CONSTRAINT `fk_admin_roles_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_admin_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "✓ جدول ربط المشرفين بالأدوار (admin_roles) جاهز.\n";

// 5. Create `admin_permissions` table (direct permissions for fine-tuning)
$pdo->exec("
CREATE TABLE IF NOT EXISTS `admin_permissions` (
    `admin_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`admin_id`, `permission_id`),
    CONSTRAINT `fk_admin_perm_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_admin_perm_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "✓ جدول الصلاحيات المباشرة (admin_permissions) جاهز.\n";

// 6. Add `member_id` column to `admins` table if not exists
$cols = $pdo->query("SHOW COLUMNS FROM `admins` LIKE 'member_id'")->fetchAll();
if (empty($cols)) {
    $pdo->exec("ALTER TABLE `admins` ADD COLUMN `member_id` INT UNSIGNED NULL UNIQUE AFTER `id`");
    // Add foreign key constraint if members table exists
    try {
        $pdo->exec("ALTER TABLE `admins` ADD CONSTRAINT `fk_admins_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL");
    } catch (\Throwable $e) {
        // FK might exist or fail silently
    }
    echo "✓ تمت إضافة عمود member_id إلى جدول admins بنجاح.\n";
} else {
    echo "✓ عمود member_id موجود مسبقاً في جدول admins.\n";
}

// 7. Seed standard permissions
$permissions = [
    // Dashboard
    [
        'name' => 'dashboard.view',
        'label_ar' => 'عرض لوحة المعلومات والإحصائيات',
        'label_en' => 'View Dashboard & Statistics',
        'module' => 'dashboard',
        'module_label_ar' => 'لوحة التحكم',
        'module_label_en' => 'Dashboard',
    ],
    [
        'name' => 'dashboard.export',
        'label_ar' => 'تصدير التقارير وطباعتها',
        'label_en' => 'Export Reports & Print',
        'module' => 'dashboard',
        'module_label_ar' => 'لوحة التحكم',
        'module_label_en' => 'Dashboard',
    ],

    // Members
    [
        'name' => 'members.view',
        'label_ar' => 'عرض قائمة المشتركين وتفاصيلهم',
        'label_en' => 'View Members List & Details',
        'module' => 'members',
        'module_label_ar' => 'المشتركون',
        'module_label_en' => 'Members',
    ],
    [
        'name' => 'members.create',
        'label_ar' => 'إضافة مشترك جديد',
        'label_en' => 'Create New Member',
        'module' => 'members',
        'module_label_ar' => 'المشتركون',
        'module_label_en' => 'Members',
    ],
    [
        'name' => 'members.edit',
        'label_ar' => 'تعديل بيانات المشتركين وتغيير حالتهم',
        'label_en' => 'Edit Members & Toggle Status',
        'module' => 'members',
        'module_label_ar' => 'المشتركون',
        'module_label_en' => 'Members',
    ],
    [
        'name' => 'members.delete',
        'label_ar' => 'حذف المشتركين',
        'label_en' => 'Delete Members',
        'module' => 'members',
        'module_label_ar' => 'المشتركون',
        'module_label_en' => 'Members',
    ],

    // Shares
    [
        'name' => 'shares.view',
        'label_ar' => 'عرض سجل الأسهم والمساهمين',
        'label_en' => 'View Shares Registry',
        'module' => 'shares',
        'module_label_ar' => 'الأسهم والمساهمات',
        'module_label_en' => 'Shares',
    ],
    [
        'name' => 'shares.manage',
        'label_ar' => 'تعديل قيمة السهم وتحديث حصص الأسهم',
        'label_en' => 'Manage Share Value & Allocations',
        'module' => 'shares',
        'module_label_ar' => 'الأسهم والمساهمات',
        'module_label_en' => 'Shares',
    ],
    [
        'name' => 'share_requests.view',
        'label_ar' => 'عرض طلبات الاكتتاب وشراء الأسهم',
        'label_en' => 'View Share Requests',
        'module' => 'shares',
        'module_label_ar' => 'الأسهم والمساهمات',
        'module_label_en' => 'Shares',
    ],
    [
        'name' => 'share_requests.manage',
        'label_ar' => 'الموافقة على طلبات الأسهم أو رفضها',
        'label_en' => 'Approve / Reject Share Requests',
        'module' => 'shares',
        'module_label_ar' => 'الأسهم والمساهمات',
        'module_label_en' => 'Shares',
    ],

    // Subscriptions
    [
        'name' => 'subscriptions.view',
        'label_ar' => 'عرض الاشتراكات الشهرية والمستحقات',
        'label_en' => 'View Monthly Subscriptions',
        'module' => 'subscriptions',
        'module_label_ar' => 'الاشتراكات الشهرية',
        'module_label_en' => 'Subscriptions',
    ],
    [
        'name' => 'subscriptions.pay',
        'label_ar' => 'تسجيل سداد الاشتراكات الشهرية',
        'label_en' => 'Record Subscription Payments',
        'module' => 'subscriptions',
        'module_label_ar' => 'الاشتراكات الشهرية',
        'module_label_en' => 'Subscriptions',
    ],

    // Founding Capital
    [
        'name' => 'founding.view',
        'label_ar' => 'عرض مبالغ وخطط التأسيس',
        'label_en' => 'View Founding Capital',
        'module' => 'founding',
        'module_label_ar' => 'مبالغ التأسيس',
        'module_label_en' => 'Founding Capital',
    ],
    [
        'name' => 'founding.pay',
        'label_ar' => 'تسجيل دفعات مبالغ التأسيس',
        'label_en' => 'Record Founding Payments',
        'module' => 'founding',
        'module_label_ar' => 'مبالغ التأسيس',
        'module_label_en' => 'Founding Capital',
    ],

    // Loans
    [
        'name' => 'loan_requests.view',
        'label_ar' => 'عرض طلبات القروض ومرفقاتها',
        'label_en' => 'View Loan Requests',
        'module' => 'loans',
        'module_label_ar' => 'القروض والتمويل',
        'module_label_en' => 'Loans Portfolio',
    ],
    [
        'name' => 'loan_requests.manage',
        'label_ar' => 'الموافقة على طلبات القروض أو رفضها',
        'label_en' => 'Approve / Reject Loan Requests',
        'module' => 'loans',
        'module_label_ar' => 'القروض والتمويل',
        'module_label_en' => 'Loans Portfolio',
    ],
    [
        'name' => 'loans.view',
        'label_ar' => 'عرض سجل القروض والأقساط',
        'label_en' => 'View Loans & Installments',
        'module' => 'loans',
        'module_label_ar' => 'القروض والتمويل',
        'module_label_en' => 'Loans Portfolio',
    ],
    [
        'name' => 'loans.create',
        'label_ar' => 'إنشاء قرض جديد لمشترك',
        'label_en' => 'Create New Loan',
        'module' => 'loans',
        'module_label_ar' => 'القروض والتمويل',
        'module_label_en' => 'Loans Portfolio',
    ],
    [
        'name' => 'loans.edit',
        'label_ar' => 'تعديل القروض وتسجيل سداد الأقساط وإغلاقها',
        'label_en' => 'Edit Loans, Record Repayments & Close',
        'module' => 'loans',
        'module_label_ar' => 'القروض والتمويل',
        'module_label_en' => 'Loans Portfolio',
    ],
    [
        'name' => 'loans.delete',
        'label_ar' => 'حذف سجلات القروض',
        'label_en' => 'Delete Loan Records',
        'module' => 'loans',
        'module_label_ar' => 'القروض والتمويل',
        'module_label_en' => 'Loans Portfolio',
    ],

    // Finance & Ledger
    [
        'name' => 'payments.view',
        'label_ar' => 'عرض سجل عمليات السداد وتصديره',
        'label_en' => 'View & Export Payments Log',
        'module' => 'finance',
        'module_label_ar' => 'المالية والسجلات',
        'module_label_en' => 'Finance & Ledger',
    ],
    [
        'name' => 'transactions.view',
        'label_ar' => 'عرض دفتر القيود والمعاملات المالية وتصديره',
        'label_en' => 'View & Export Financial Ledger',
        'module' => 'finance',
        'module_label_ar' => 'المالية والسجلات',
        'module_label_en' => 'Finance & Ledger',
    ],

    // Communications
    [
        'name' => 'notifications.view',
        'label_ar' => 'عرض سجل الإشعارات الصادرة',
        'label_en' => 'View Sent Notifications',
        'module' => 'communications',
        'module_label_ar' => 'التواصل والمراسلات',
        'module_label_en' => 'Communications',
    ],
    [
        'name' => 'notifications.send',
        'label_ar' => 'إرسال إشعارات جديدة للمشتركين وحذفها',
        'label_en' => 'Send & Delete Notifications',
        'module' => 'communications',
        'module_label_ar' => 'التواصل والمراسلات',
        'module_label_en' => 'Communications',
    ],
    [
        'name' => 'messages.view',
        'label_ar' => 'عرض رسائل الدعم والتواصل الواردة',
        'label_en' => 'View Support & Contact Messages',
        'module' => 'communications',
        'module_label_ar' => 'التواصل والمراسلات',
        'module_label_en' => 'Communications',
    ],
    [
        'name' => 'messages.reply',
        'label_ar' => 'الرد على رسائل المشتركين والزوار',
        'label_en' => 'Reply to Contact Messages',
        'module' => 'communications',
        'module_label_ar' => 'التواصل والمراسلات',
        'module_label_en' => 'Communications',
    ],

    // Content & Translation
    [
        'name' => 'content.manage',
        'label_ar' => 'إدارة محتوى الموقع والصفحات وبناء الرئيسية',
        'label_en' => 'Manage Content Pages & Landing Page',
        'module' => 'content',
        'module_label_ar' => 'المحتوى والترجمة',
        'module_label_en' => 'Content & Translations',
    ],
    [
        'name' => 'live_translate.manage',
        'label_ar' => 'إدارة الترجمة الفورية واستيراد وتصدير النصوص',
        'label_en' => 'Manage Live Translate & Multi-language',
        'module' => 'content',
        'module_label_ar' => 'المحتوى والترجمة',
        'module_label_en' => 'Content & Translations',
    ],

    // System Settings
    [
        'name' => 'settings.manage',
        'label_ar' => 'تعديل إعدادات النظام العامة والمالية وSMS',
        'label_en' => 'Manage System, Financial & SMS Settings',
        'module' => 'settings',
        'module_label_ar' => 'إعدادات النظام',
        'module_label_en' => 'System Settings',
    ],

    // Roles & Staff Permissions
    [
        'name' => 'roles.manage',
        'label_ar' => 'إدارة الأدوار والصلاحيات وتعيين صلاحيات الأعضاء والمشرفين',
        'label_en' => 'Manage Roles, Permissions & Member Access',
        'module' => 'roles',
        'module_label_ar' => 'الأدوار والصلاحيات',
        'module_label_en' => 'Roles & Permissions',
    ],
];

$stmtCheck = $pdo->prepare("SELECT id FROM permissions WHERE name = ?");
$stmtInsert = $pdo->prepare("INSERT INTO permissions (name, label_ar, label_en, module, module_label_ar, module_label_en) VALUES (?, ?, ?, ?, ?, ?)");
$stmtUpdate = $pdo->prepare("UPDATE permissions SET label_ar = ?, label_en = ?, module = ?, module_label_ar = ?, module_label_en = ? WHERE name = ?");

$insertedPerms = 0;
foreach ($permissions as $p) {
    $stmtCheck->execute([$p['name']]);
    $existing = $stmtCheck->fetchColumn();
    if (!$existing) {
        $stmtInsert->execute([$p['name'], $p['label_ar'], $p['label_en'], $p['module'], $p['module_label_ar'], $p['module_label_en']]);
        $insertedPerms++;
    } else {
        $stmtUpdate->execute([$p['label_ar'], $p['label_en'], $p['module'], $p['module_label_ar'], $p['module_label_en'], $p['name']]);
    }
}
echo "✓ تم تحديث الصلاحيات الأساسية ({$insertedPerms} صلاحية جديدة أُضيفت، إجمالي " . count($permissions) . " صلاحية).\n";

// Map permission name -> id
$permMap = $pdo->query("SELECT name, id FROM permissions")->fetchAll(PDO::FETCH_KEY_PAIR);

// 8. Seed default roles
$roles = [
    [
        'name' => 'super_admin',
        'label_ar' => 'المدير العام (صلاحيات كاملة)',
        'label_en' => 'Super Administrator (Full Access)',
        'description' => 'صلاحية كاملة ومطلقة على كافة أقسام وإعدادات لوحة التحكم بدون أي قيود.',
        'is_system' => 1,
        'permissions' => array_keys($permMap), // all
    ],
    [
        'name' => 'treasurer',
        'label_ar' => 'أمين الصندوق (المسؤول المالي)',
        'label_en' => 'Treasurer (Financial Officer)',
        'description' => 'إدارة الاشتراكات الشهرية، مبالغ التأسيس، سداد الأقساط، دفتر القيود، والمعاملات المالية.',
        'is_system' => 1,
        'permissions' => [
            'dashboard.view', 'dashboard.export',
            'members.view',
            'shares.view',
            'subscriptions.view', 'subscriptions.pay',
            'founding.view', 'founding.pay',
            'loans.view', 'loans.edit',
            'payments.view', 'transactions.view',
        ],
    ],
    [
        'name' => 'loans_committee',
        'label_ar' => 'لجنة القروض والتمويل',
        'label_en' => 'Loans Committee',
        'description' => 'مراجعة طلبات القروض المقدمة من المشتركين، اتخاذ قرار القبول أو الرفض، ومتابعة سجل القروض.',
        'is_system' => 1,
        'permissions' => [
            'dashboard.view',
            'members.view',
            'loan_requests.view', 'loan_requests.manage',
            'loans.view', 'loans.create', 'loans.edit',
        ],
    ],
    [
        'name' => 'membership_officer',
        'label_ar' => 'مسؤول شؤون المشتركين والأسهم',
        'label_en' => 'Membership & Shares Officer',
        'description' => 'إدارة بيانات المشتركين، إضافة الأعضاء، تعديل السجلات، وإدارة طلبات وتوزيع الأسهم.',
        'is_system' => 1,
        'permissions' => [
            'dashboard.view',
            'members.view', 'members.create', 'members.edit',
            'shares.view', 'shares.manage',
            'share_requests.view', 'share_requests.manage',
        ],
    ],
    [
        'name' => 'support_officer',
        'label_ar' => 'مشرف المحتوى والتواصل',
        'label_en' => 'Content & Communications Officer',
        'description' => 'الرد على رسائل المشتركين، إرسال الإشعارات، إدارة محتوى الموقع والصفحات التعريفية.',
        'is_system' => 1,
        'permissions' => [
            'dashboard.view',
            'notifications.view', 'notifications.send',
            'messages.view', 'messages.reply',
            'content.manage', 'live_translate.manage',
        ],
    ],
    [
        'name' => 'auditor',
        'label_ar' => 'مراقب ومراجع مالي (للاطلاع والتدقيق)',
        'label_en' => 'Financial Auditor (Read Only)',
        'description' => 'صلاحيات اطلاع وتصدير تقارير لكافة السجلات المالية والأسهم والمشتركين دون إمكانية التعديل.',
        'is_system' => 1,
        'permissions' => [
            'dashboard.view', 'dashboard.export',
            'members.view',
            'shares.view',
            'subscriptions.view',
            'founding.view',
            'loans.view',
            'payments.view', 'transactions.view',
        ],
    ],
];

$roleCheck = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
$roleInsert = $pdo->prepare("INSERT INTO roles (name, label_ar, label_en, description, is_system) VALUES (?, ?, ?, ?, ?)");
$roleUpdate = $pdo->prepare("UPDATE roles SET label_ar = ?, label_en = ?, description = ?, is_system = ? WHERE name = ?");
$permRoleInsert = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");

foreach ($roles as $r) {
    $roleCheck->execute([$r['name']]);
    $roleId = $roleCheck->fetchColumn();
    if (!$roleId) {
        $roleInsert->execute([$r['name'], $r['label_ar'], $r['label_en'], $r['description'], $r['is_system']]);
        $roleId = (int) $pdo->lastInsertId();
        echo "✓ تم إنشاء الدور الافتراضي: {$r['label_ar']}\n";
    } else {
        $roleUpdate->execute([$r['label_ar'], $r['label_en'], $r['description'], $r['is_system'], $r['name']]);
    }

    // Attach permissions
    foreach ($r['permissions'] as $pName) {
        if (isset($permMap[$pName])) {
            $permRoleInsert->execute([$roleId, $permMap[$pName]]);
        }
    }
}

// 9. Assign `super_admin` role to existing primary admin (admin ID 1 or admin@sandouk.local)
$superRoleId = $pdo->query("SELECT id FROM roles WHERE name = 'super_admin'")->fetchColumn();
if ($superRoleId) {
    $adminIds = $pdo->query("SELECT id FROM admins WHERE id = 1 OR email = 'admin@sandouk.local'")->fetchAll(PDO::FETCH_COLUMN);
    $assignStmt = $pdo->prepare("INSERT IGNORE INTO admin_roles (admin_id, role_id) VALUES (?, ?)");
    foreach ($adminIds as $aId) {
        $assignStmt->execute([$aId, $superRoleId]);
    }
    echo "✓ تم تعيين دور المدير العام (super_admin) لحساب الإدارة الأساسي.\n";
}

echo "\n=== اكتملت عملية الترقية وتثبيت باكدج الصلاحيات بنجاح! ===\n";
