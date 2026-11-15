-- Upgrades the `expenses` table of an older database to the current shape (adds the transaction type, the optional member
-- and the optional notes). For a host WITHOUT a terminal:
--   phpMyAdmin > pick the site's database > "SQL" tab > paste this whole file > Go.
-- Run it ONCE: a second run stops with "Duplicate column name" and changes nothing.
-- Every existing row becomes a "withdraw" entry, which is what it was before (it keeps lowering the balance).
-- Same result as:  php database/migrate_expenses.php

ALTER TABLE expenses
    ADD COLUMN type ENUM('withdraw','deposit') NOT NULL DEFAULT 'withdraw' COMMENT 'withdraw = deducted from the fund balance, deposit = added to it' AFTER amount,
    ADD COLUMN member_id INT UNSIGNED NULL COMMENT 'optional: the member this expense belongs to' AFTER expense_date,
    ADD COLUMN notes TEXT NULL COMMENT 'optional: free notes on this expense' AFTER member_id;

ALTER TABLE expenses
    ADD KEY idx_expenses_member (member_id),
    ADD CONSTRAINT fk_expenses_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL;
