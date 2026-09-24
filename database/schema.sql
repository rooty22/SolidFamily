SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS sandouk_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sandouk_db;

-- =========================================================
-- Admins
-- =========================================================
CREATE TABLE admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Members (Customers)
-- =========================================================
CREATE TABLE members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    mobile VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    birth_date DATE NULL,
    national_address VARCHAR(255) NULL,
    national_id VARCHAR(30) NOT NULL UNIQUE,
    bank_account_number VARCHAR(50) NULL,
    iban VARCHAR(50) NULL,
    bank_name VARCHAR(100) NULL,
    password VARCHAR(255) NOT NULL,
    shares_count INT UNSIGNED NOT NULL DEFAULT 0,
    subscription_due_day TINYINT UNSIGNED NULL COMMENT 'per-member override of the global subscription due day (1-28), NULL falls back to the site setting',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- OTP codes (register / password reset for members & admins)
-- =========================================================
CREATE TABLE otp_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(150) NOT NULL,
    code VARCHAR(10) NOT NULL,
    purpose ENUM('member_register','member_reset','admin_reset') NOT NULL,
    payload TEXT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_purpose (identifier, purpose)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Share change requests
-- =========================================================
CREATE TABLE share_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    type ENUM('add','merge','cancel') NOT NULL,
    shares_count INT UNSIGNED NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_note VARCHAR(500) NULL,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_share_requests_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    CONSTRAINT fk_share_requests_admin FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Share lots: each approved "add" request stays its own lot (own due day) until
-- a "merge" request is approved and combines every active lot into one.
-- =========================================================
CREATE TABLE share_lots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    shares_count INT UNSIGNED NOT NULL,
    subscription_due_day TINYINT UNSIGNED NULL COMMENT 'NULL falls back to the site default',
    status ENUM('active','merged') NOT NULL DEFAULT 'active',
    source_request_id INT UNSIGNED NULL COMMENT 'the share_request that created/merged this lot, for traceability',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lots_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Monthly subscriptions: one row per active share lot per month (or one lot-less
-- row for a member with no shares yet), so unmerged lots bill on their own due dates.
-- =========================================================
CREATE TABLE monthly_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    lot_id INT UNSIGNED NULL,
    month CHAR(7) NOT NULL COMMENT 'YYYY-MM',
    shares_count_snapshot INT UNSIGNED NOT NULL,
    share_value_snapshot DECIMAL(12,2) NOT NULL,
    amount_due DECIMAL(12,2) NOT NULL,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    due_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_member_month_lot (member_id, month, lot_id),
    CONSTRAINT fk_subscriptions_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscriptions_lot FOREIGN KEY (lot_id) REFERENCES share_lots(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Founding amounts
-- =========================================================
CREATE TABLE founding_amounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL UNIQUE,
    shares_count_linked INT UNSIGNED NOT NULL DEFAULT 0,
    total_required DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    plan_months TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'payment plan chosen by the member, 1 = one payment, N = N monthly installments',
    plan_start DATE NULL COMMENT 'first day of the month the first installment falls due, NULL until a plan is chosen',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_founding_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE founding_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    founding_amount_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_date DATE NOT NULL,
    recorded_by INT UNSIGNED NULL,
    notes VARCHAR(500) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_founding_payments_founding FOREIGN KEY (founding_amount_id) REFERENCES founding_amounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_founding_payments_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    CONSTRAINT fk_founding_payments_admin FOREIGN KEY (recorded_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Loan requests
-- =========================================================
CREATE TABLE loan_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    amount_requested DECIMAL(12,2) NOT NULL,
    reason ENUM('personal','educational','marriage','other') NOT NULL,
    reason_other_text VARCHAR(500) NULL,
    installments_months INT UNSIGNED NOT NULL DEFAULT 6,
    agreed_terms TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_note VARCHAR(500) NULL,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_loan_requests_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    CONSTRAINT fk_loan_requests_admin FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Loans
-- =========================================================
CREATE TABLE loans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    loan_request_id INT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL,
    reason ENUM('personal','educational','marriage','other') NOT NULL,
    reason_other_text VARCHAR(500) NULL,
    loan_date DATE NOT NULL,
    installments_count INT UNSIGNED NOT NULL,
    installment_value DECIMAL(12,2) NOT NULL,
    admin_fee_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    admin_fee_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_remaining DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('active','partial','paid','closed') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    CONSTRAINT fk_loans_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    CONSTRAINT fk_loans_request FOREIGN KEY (loan_request_id) REFERENCES loan_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE loan_installments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id INT UNSIGNED NOT NULL,
    installment_number INT UNSIGNED NOT NULL,
    due_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    paid_at DATETIME NULL,
    CONSTRAINT fk_installments_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Transactions (central financial ledger / payments log)
-- =========================================================
CREATE TABLE transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    category ENUM('subscription','founding','loan_disbursement','loan_installment','loan_admin_fee') NOT NULL,
    related_id INT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL,
    transaction_date DATE NOT NULL,
    status ENUM('completed') NOT NULL DEFAULT 'completed',
    recorded_by INT UNSIGNED NULL,
    notes VARCHAR(500) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    CONSTRAINT fk_transactions_admin FOREIGN KEY (recorded_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Notifications
-- =========================================================
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    target_type ENUM('all','specific') NOT NULL DEFAULT 'all',
    target_member_id INT UNSIGNED NULL,
    category ENUM('system','manual') NOT NULL DEFAULT 'manual',
    status ENUM('sent','failed') NOT NULL DEFAULT 'sent',
    created_by INT UNSIGNED NULL,
    dedupe_key VARCHAR(80) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_notif_dedupe (target_member_id, dedupe_key),
    CONSTRAINT fk_notifications_member FOREIGN KEY (target_member_id) REFERENCES members(id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_admin FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Contact / support messages
-- =========================================================
CREATE TABLE contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NULL,
    message TEXT NOT NULL,
    status ENUM('new','read') NOT NULL DEFAULT 'new',
    reply_text TEXT NULL,
    replied_at DATETIME NULL,
    replied_by INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contact_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Content pages
-- =========================================================
CREATE TABLE content_pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    content LONGTEXT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Settings (key/value)
-- =========================================================
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    value TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Rate limits (login / OTP / public form throttling)
-- =========================================================
CREATE TABLE rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rkey CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_rkey_created (rkey, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
