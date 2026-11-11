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
    avatar VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    permissions TEXT NULL COMMENT 'JSON {section: view|manage}; ignored for main admins',
    is_super TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = main admin: full access and manages the sub-admins',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Members (Customers)
-- =========================================================
CREATE TABLE members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    serial_no INT UNSIGNED NULL COMMENT 'fixed registration-order number, never reused or changed (NULL while a registration is pending)',
    uid VARCHAR(16) NULL COMMENT 'independent User ID, never changed',
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
    status ENUM('active','inactive','pending','rejected') NOT NULL DEFAULT 'active' COMMENT 'pending/rejected: a self-registration waiting for / refused by an admin; neither can log in',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_members_serial_no (serial_no),
    UNIQUE KEY uniq_members_uid (uid)
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
    type ENUM('add','merge','cancel','split') NOT NULL,
    shares_count INT UNSIGNED NULL,
    lot_ids VARCHAR(255) NULL COMMENT 'merge requests: comma-separated share_lots ids to merge, NULL = every active lot; split requests: the merged lot id to split',
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
    status ENUM('active','merged','cancelled') NOT NULL DEFAULT 'active' COMMENT 'merged = folded into another lot (or retired by a split); cancelled = given up, its unpaid dues cleared',
    source_request_id INT UNSIGNED NULL COMMENT 'the share_request that created/merged this lot, for traceability',
    merged_into_id INT UNSIGNED NULL COMMENT 'for a lot folded away by a merge: the merged lot it became, so the merge can be split back',
    admin_note VARCHAR(500) NULL COMMENT 'note the admin keeps about this share (the member can open it with the View note button)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lots_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Share merge / split log: shares taken off a member by a merge (the folded-away shares) and
-- given back by a split, with the monthly amount they were worth at the time (feeds the dashboard).
-- =========================================================
CREATE TABLE share_merge_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    lot_id INT UNSIGNED NULL COMMENT 'the merged lot that was created (merge) or retired (split)',
    action ENUM('merge','split') NOT NULL,
    shares INT UNSIGNED NOT NULL COMMENT 'shares deducted by the merge / restored by the split',
    share_value DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'shares x share_value',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_merge_log_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
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
    grace_until DATE NULL COMMENT 'short grace for a lot billed after its due day, counts as late only after this date',
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
    due_day TINYINT UNSIGNED NULL COMMENT 'the founding amount own due day (1-28), pinned the day it first becomes owed, independent of the member subscription due day',
    plan_months TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'payment plan chosen by the member, 1 = one payment, N = N monthly installments',
    plan_start DATE NULL COMMENT 'first day of the month the first installment falls due, NULL until a plan is chosen',
    plan_schedule TEXT NULL COMMENT 'JSON list with the amount of every installment, frozen so paid installments never change when shares change',
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

-- Which share (lot) each founding payment was paid for. A payment can cover more than one share, so this is separate
-- from founding_payments; the amount paid for a share is never spread over the member's other shares.
CREATE TABLE founding_payment_allocations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id INT UNSIGNED NOT NULL COMMENT 'the founding payment (or the part of it) this row covers',
    lot_id INT UNSIGNED NOT NULL COMMENT 'the share the money was paid for',
    amount DECIMAL(12,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alloc_lot (lot_id),
    INDEX idx_alloc_payment (payment_id),
    CONSTRAINT fk_alloc_payment FOREIGN KEY (payment_id) REFERENCES founding_payments(id) ON DELETE CASCADE,
    CONSTRAINT fk_alloc_lot FOREIGN KEY (lot_id) REFERENCES share_lots(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Loan requests
-- =========================================================
CREATE TABLE loan_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    lot_id INT UNSIGNED NULL,
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
    CONSTRAINT fk_loan_requests_lot FOREIGN KEY (lot_id) REFERENCES share_lots(id) ON DELETE SET NULL,
    CONSTRAINT fk_loan_requests_admin FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Loans
-- =========================================================
CREATE TABLE loans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    loan_request_id INT UNSIGNED NULL,
    lot_id INT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL,
    reason ENUM('personal','educational','marriage','other') NOT NULL,
    reason_other_text VARCHAR(500) NULL,
    loan_date DATE NOT NULL,
    installments_count INT UNSIGNED NOT NULL,
    installment_value DECIMAL(12,2) NOT NULL,
    admin_fee_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    admin_fee_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    fee_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid' COMMENT 'the commission is paid only once an admin confirms it',
    fee_paid_at DATE NULL COMMENT 'date the commission was received',
    fee_confirmed_by INT UNSIGNED NULL COMMENT 'admin who last confirmed / reverted the commission payment',
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_remaining DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('active','partial','paid','closed') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    CONSTRAINT fk_loans_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    CONSTRAINT fk_loans_request FOREIGN KEY (loan_request_id) REFERENCES loan_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_loans_lot FOREIGN KEY (lot_id) REFERENCES share_lots(id) ON DELETE SET NULL,
    CONSTRAINT fk_loans_fee_admin FOREIGN KEY (fee_confirmed_by) REFERENCES admins(id) ON DELETE SET NULL
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
-- Administrative and bank expenses log (main admin only)
-- =========================================================
CREATE TABLE expenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    expense_date DATE NOT NULL,
    member_id INT UNSIGNED NULL COMMENT 'optional: the member this expense belongs to',
    notes TEXT NULL COMMENT 'optional: free notes on this expense',
    created_by INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_expenses_date (expense_date),
    KEY idx_expenses_member (member_id),
    CONSTRAINT fk_expenses_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
    CONSTRAINT fk_expenses_admin FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
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
