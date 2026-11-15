-- Read-only audit of the dashboard totals, for a host WITHOUT a terminal (same checks as `php database/audit_totals.php`).
--
--   phpMyAdmin > pick the site's database > "SQL" tab > paste this whole file > Go.
--
-- It changes NOTHING: it only reads. It returns ONE table with the columns  n | section | item | value | status | note:
--   section 1  the dashboard figures (bank balance, loans outstanding, commissions, total fund), recomputed from the rows
--   section 2  the fund's worth worked out a second, independent way (what members paid + commissions + expenses)
--   section 3  if the books and that independent figure differ, which stored figure is out of step and by how much
--   section 4  for information: the dashboard total is bank + loans outstanding + commissions RECEIVED + expenses net, and the
--              commissions received and the expenses net are already inside the bank balance, so that sum counts them twice
--   section 5  every consistency check (status OK, or ERROR / WARN with how many rows and which ones)
-- Row 16 compares the books with the independent figure (without the double count): it must say OK.
-- Send me the rows whose status is ERROR or WARN.
--
-- Run database/check_missing_columns.sql first: this audit needs every column of the current schema to exist.

SET SESSION group_concat_max_len = 8192;

SELECT r.n, r.section, r.item, r.value, r.status, r.note FROM (

-- ============================================================================================================
-- 1) the dashboard figures, worked out the way the dashboard does
-- ============================================================================================================
SELECT k.n AS n, k.section AS section, k.item AS item,
       CASE k.n
            WHEN 1  THEN d.collected
            WHEN 2  THEN d.disbursed
            WHEN 3  THEN d.exp_w
            WHEN 4  THEN d.exp_d
            WHEN 5  THEN d.exp_d - d.exp_w
            WHEN 6  THEN d.collected - d.disbursed + d.exp_d - d.exp_w
            WHEN 7  THEN d.remaining
            WHEN 8  THEN d.fees_paid
            WHEN 9  THEN d.fees_due
            WHEN 10 THEN d.collected - d.disbursed + d.exp_d - d.exp_w + d.remaining + d.fees_paid + (d.exp_d - d.exp_w)
            WHEN 11 THEN d.c_sub
            WHEN 12 THEN d.c_found
            WHEN 13 THEN d.fees_paid + d.fees_due
            WHEN 14 THEN d.exp_d - d.exp_w
            WHEN 15 THEN d.c_sub + d.c_found + d.fees_paid + d.fees_due + d.exp_d - d.exp_w
            WHEN 16 THEN (d.collected - d.disbursed + d.exp_d - d.exp_w + d.remaining + d.fees_due)
                       - (d.c_sub + d.c_found + d.fees_paid + d.fees_due + d.exp_d - d.exp_w)
            WHEN 17 THEN d.i_tx - d.p_loans
            WHEN 18 THEN d.f_tx - d.fees_paid
            WHEN 19 THEN d.remaining - (d.l_loans - d.p_loans)
            WHEN 20 THEN -(d.disbursed - d.l_loans)
            WHEN 21 THEN (d.i_tx - d.p_loans) + (d.f_tx - d.fees_paid) + (d.remaining - (d.l_loans - d.p_loans)) - (d.disbursed - d.l_loans)
            WHEN 22 THEN d.fees_paid + (d.exp_d - d.exp_w)
            WHEN 23 THEN d.collected - d.disbursed + d.exp_d - d.exp_w + d.remaining + d.fees_due
       END AS value,
       CASE
            WHEN k.n IN (16, 17, 18, 19, 20) THEN IF(ABS(
                 CASE k.n
                      WHEN 16 THEN (d.collected - d.disbursed + d.exp_d - d.exp_w + d.remaining + d.fees_due)
                                 - (d.c_sub + d.c_found + d.fees_paid + d.fees_due + d.exp_d - d.exp_w)
                      WHEN 17 THEN d.i_tx - d.p_loans
                      WHEN 18 THEN d.f_tx - d.fees_paid
                      WHEN 19 THEN d.remaining - (d.l_loans - d.p_loans)
                      WHEN 20 THEN d.disbursed - d.l_loans
                 END) < 0.01, 'OK', 'ERROR')
            ELSE 'INFO'
       END AS status,
       k.note AS note
FROM (
    SELECT 1 AS n, '1) أرقام الداشبورد' AS section, 'الوارد: اشتراكات + تأسيس + أقساط قروض + عمولات مستلمة' AS item, 'من دفتر المعاملات' AS note
    UNION ALL SELECT 2,  '1) أرقام الداشبورد', 'القروض المصروفة', 'من دفتر المعاملات (صرف قرض)'
    UNION ALL SELECT 3,  '1) أرقام الداشبورد', 'المصاريف الإدارية والبنكية: صرف', 'بوكس المصاريف: صرف'
    UNION ALL SELECT 4,  '1) أرقام الداشبورد', 'المصاريف الإدارية والبنكية: إيداع', 'بوكس المصاريف: إيداع'
    UNION ALL SELECT 5,  '1) أرقام الداشبورد', 'صافي المصاريف (إيداع - صرف)', 'بوكس: المصاريف الإدارية والبنكية (الصافي)'
    UNION ALL SELECT 6,  '1) أرقام الداشبورد', 'الرصيد الفعلي بالبنك = الوارد - القروض المصروفة + صافي المصاريف', 'بوكس: الرصيد الفعلي بالبنك'
    UNION ALL SELECT 7,  '1) أرقام الداشبورد', 'القروض القائمة (المتبقي من أصل القروض)', 'بوكس: القروض القائمة (المتبقي)'
    UNION ALL SELECT 8,  '1) أرقام الداشبورد', 'عمولات القروض المستلمة (داخلة أصلا في رقم البنك)', 'بوكس العمولات: مسددة'
    UNION ALL SELECT 9,  '1) أرقام الداشبورد', 'عمولات القروض المستحقة (لم تُستلم بعد)', 'بوكس العمولات: مستحقة'
    UNION ALL SELECT 10, '1) أرقام الداشبورد', 'الرصيد الإجمالي للصندوق = البنك + القروض القائمة + العمولات المسددة + صافي المصاريف', 'بوكس: الرصيد الإجمالي للصندوق (قارنه بالداشبورد)'
    UNION ALL SELECT 11, '2) قيمة الصندوق من مصدر مستقل', 'اشتراكات مستلمة (دفتر المعاملات)', 'ما دفعه المشتركون'
    UNION ALL SELECT 12, '2) قيمة الصندوق من مصدر مستقل', 'مبالغ تأسيس مستلمة (دفتر المعاملات)', 'ما دفعه المشتركون'
    UNION ALL SELECT 13, '2) قيمة الصندوق من مصدر مستقل', 'كل عمولات القروض (مستلمة + مستحقة)', 'من جدول القروض'
    UNION ALL SELECT 14, '2) قيمة الصندوق من مصدر مستقل', 'صافي المصاريف', 'من سجل المصاريف'
    UNION ALL SELECT 15, '2) قيمة الصندوق من مصدر مستقل', 'القيمة المستقلة = اشتراكات + تأسيس + كل العمولات + صافي المصاريف', 'القروض لا تغيّر القيمة: نقدية تخرج وتصير دين على المشترك'
    UNION ALL SELECT 16, '2) قيمة الصندوق من مصدر مستقل', 'الفرق = (البنك + القروض القائمة + العمولات المستحقة) - رقم 15 (لازم صفر)', 'لو مش صفر فالدفاتر غير متسقة والإجمالي في الداشبورد متأثر، والأسباب في القسم 3'
    UNION ALL SELECT 17, '3) أين الفرق؟', 'أقساط القروض في الدفتر - ما سُدّد فعلا من القروض', 'لازم صفر'
    UNION ALL SELECT 18, '3) أين الفرق؟', 'عمولات الدفتر - عمولات القروض المعلّمة مدفوعة', 'لازم صفر'
    UNION ALL SELECT 19, '3) أين الفرق؟', 'القروض القائمة المخزّنة - (إجمالي القروض - المسدد منها)', 'لازم صفر'
    UNION ALL SELECT 20, '3) أين الفرق؟', '(القروض المصروفة في الدفتر - إجمالي مبالغ القروض) بإشارة سالبة', 'لازم صفر'
    UNION ALL SELECT 21, '3) أين الفرق؟', 'مجموع الأربعة السابقة (يساوي الفرق في رقم 16)', 'للتأكد فقط'
    UNION ALL SELECT 22, '4) للعلم: التكرار في الإجمالي', 'المحسوب مرتين في رقم 10 = العمولات المسددة + صافي المصاريف', 'داخلين أصلا في رقم البنك (رقم 6)'
    UNION ALL SELECT 23, '4) للعلم: التكرار في الإجمالي', 'الإجمالي بدون تكرار = البنك + القروض القائمة + العمولات المستحقة', 'للمقارنة فقط، وهو رقم 10 ناقص رقم 22 زائد المستحقة'
) AS k
CROSS JOIN (
    SELECT
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE category IN ('subscription', 'founding', 'loan_installment', 'loan_admin_fee')) AS collected,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE category = 'loan_disbursement') AS disbursed,
        (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE type = 'withdraw') AS exp_w,
        (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE type = 'deposit') AS exp_d,
        (SELECT COALESCE(SUM(amount_remaining), 0) FROM loans WHERE status IN ('active', 'partial')) AS remaining,
        (SELECT COALESCE(SUM(CASE WHEN fee_status = 'paid' THEN admin_fee_amount ELSE 0 END), 0) FROM loans) AS fees_paid,
        (SELECT COALESCE(SUM(CASE WHEN fee_status = 'paid' THEN 0 ELSE admin_fee_amount END), 0) FROM loans) AS fees_due,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE category = 'subscription') AS c_sub,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE category = 'founding') AS c_found,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE category = 'loan_installment') AS i_tx,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE category = 'loan_admin_fee') AS f_tx,
        (SELECT COALESCE(SUM(amount_paid), 0) FROM loans) AS p_loans,
        (SELECT COALESCE(SUM(amount), 0) FROM loans) AS l_loans
) AS d

-- ============================================================================================================
-- 5) consistency checks: a stored figure that disagrees with the rows it is made of
-- ============================================================================================================
UNION ALL SELECT 30, '5) فحوصات الاتساق', 'قرض: المسدد المخزّن لا يساوي مجموع أقساطه', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('قرض #', l.id, ' مخزّن ', l.amount_paid, ' مقابل أقساط ', COALESCE(SUM(i.amount_paid), 0)) AS d
    FROM loans l LEFT JOIN loan_installments i ON i.loan_id = l.id
    GROUP BY l.id, l.amount_paid
    HAVING ABS(l.amount_paid - COALESCE(SUM(i.amount_paid), 0)) > 0.009) o) t

UNION ALL SELECT 31, '5) فحوصات الاتساق', 'قرض: المتبقي لا يساوي (المبلغ - المسدد)', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('قرض #', id, ' مبلغ ', amount, ' مسدد ', amount_paid, ' متبقي ', amount_remaining) AS d
    FROM loans WHERE ABS(amount_remaining - GREATEST(amount - amount_paid, 0)) > 0.009) o) t

UNION ALL SELECT 32, '5) فحوصات الاتساق', 'قرض: مجموع الأقساط لا يساوي مبلغ القرض أو عددها غير صحيح', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('قرض #', l.id, ' مبلغ ', l.amount, ' أقساط ', COALESCE(SUM(i.amount), 0), ' (', COUNT(i.id), '/', l.installments_count, ')') AS d
    FROM loans l LEFT JOIN loan_installments i ON i.loan_id = l.id
    GROUP BY l.id, l.amount, l.installments_count
    HAVING ABS(l.amount - COALESCE(SUM(i.amount), 0)) > 0.009 OR COUNT(i.id) <> l.installments_count) o) t

UNION ALL SELECT 33, '5) فحوصات الاتساق', 'قرض: حالته لا تطابق ما سُدّد منه', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('قرض #', id, ' حالة ', status, ' مسدد ', amount_paid, ' من ', amount) AS d
    FROM loans
    WHERE (status = 'active' AND amount_paid > 0.009)
       OR (status = 'partial' AND (amount_paid <= 0.009 OR amount_paid >= amount - 0.009))
       OR (status = 'paid' AND amount_paid < amount - 0.009)
       OR (status = 'closed' AND amount_paid < amount - 0.009)) o) t

UNION ALL SELECT 34, '5) فحوصات الاتساق', 'قسط: حالته لا تطابق المسدد منه', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('قسط #', id, ' (قرض ', loan_id, ') ', status, ' مسدد ', amount_paid, ' من ', amount) AS d
    FROM loan_installments
    WHERE (status = 'unpaid' AND amount_paid > 0.009)
       OR (status = 'partial' AND (amount_paid <= 0.009 OR amount_paid >= amount - 0.009))
       OR (status = 'paid' AND amount_paid < amount - 0.009)) o) t

UNION ALL SELECT 35, '5) فحوصات الاتساق', 'دفتر المعاملات: أقساط القرض المسجلة لا تساوي ما يقوله القرض أنه سُدّد', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('قرض #', x.id, ' مسدد ', x.amount_paid, ' مقابل الدفتر ', x.ledger) AS d FROM (
        SELECT l.id, l.amount_paid,
               COALESCE((SELECT SUM(tr.amount) FROM transactions tr JOIN loan_installments i ON i.id = tr.related_id
                         WHERE tr.category = 'loan_installment' AND i.loan_id = l.id), 0) AS ledger
        FROM loans l) x
    WHERE ABS(x.amount_paid - x.ledger) > 0.009) o) t

UNION ALL SELECT 36, '5) فحوصات الاتساق', 'قرض ليس له قيد صرف واحد بمبلغه بالضبط في الدفتر', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('قرض #', x.id, ' مبلغ ', x.amount, ' قيود الصرف ', x.n, ' بمجموع ', x.s) AS d FROM (
        SELECT l.id, l.amount,
               (SELECT COUNT(*) FROM transactions tr WHERE tr.category = 'loan_disbursement' AND tr.related_id = l.id) AS n,
               (SELECT COALESCE(SUM(tr.amount), 0) FROM transactions tr WHERE tr.category = 'loan_disbursement' AND tr.related_id = l.id) AS s
        FROM loans l) x
    WHERE x.n <> 1 OR ABS(x.s - x.amount) > 0.009) o) t

UNION ALL SELECT 37, '5) فحوصات الاتساق', 'حركات في الدفتر تشير إلى قرض أو قسط أو دفعة تأسيس لم تعد موجودة', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('حركة #', tr.id, ' ', tr.category, ' ', tr.amount) AS d FROM transactions tr
    WHERE (tr.category IN ('loan_disbursement', 'loan_admin_fee') AND NOT EXISTS (SELECT 1 FROM loans l WHERE l.id = tr.related_id))
       OR (tr.category = 'loan_installment' AND NOT EXISTS (SELECT 1 FROM loan_installments i WHERE i.id = tr.related_id))
       OR (tr.category = 'founding' AND NOT EXISTS (SELECT 1 FROM founding_payments p WHERE p.id = tr.related_id))) o) t

UNION ALL SELECT 38, '5) فحوصات الاتساق', 'حركات في الدفتر لأعضاء لم يعودوا موجودين (تدخل في الداشبورد ولا تظهر في صفحة المعاملات)', t.k, IF(t.k = 0, 'OK', 'WARN'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('حركة #', tr.id, ' ', tr.category, ' ', tr.amount) AS d FROM transactions tr
    WHERE NOT EXISTS (SELECT 1 FROM members m WHERE m.id = tr.member_id)) o) t

UNION ALL SELECT 40, '5) فحوصات الاتساق', 'عمولة القرض لا تساوي (المبلغ × النسبة)', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('قرض #', id, ' مبلغ ', amount, ' نسبة ', admin_fee_percent, ' عمولة ', admin_fee_amount) AS d
    FROM loans WHERE ABS(admin_fee_amount - ROUND(amount * admin_fee_percent / 100, 2)) > 0.009) o) t

UNION ALL SELECT 41, '5) فحوصات الاتساق', 'عمولة معلّمة مدفوعة وليس لها قيد واحد في الدفتر بمبلغها وتاريخها', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('قرض #', x.id, ' عمولة ', x.admin_fee_amount, ' قيود الدفتر ', x.n, ' بمجموع ', x.s) AS d FROM (
        SELECT l.id, l.admin_fee_amount, l.fee_paid_at,
               (SELECT COUNT(*) FROM transactions tr WHERE tr.category = 'loan_admin_fee' AND tr.related_id = l.id) AS n,
               (SELECT COALESCE(SUM(tr.amount), 0) FROM transactions tr WHERE tr.category = 'loan_admin_fee' AND tr.related_id = l.id) AS s,
               (SELECT MAX(tr.transaction_date) FROM transactions tr WHERE tr.category = 'loan_admin_fee' AND tr.related_id = l.id) AS dt
        FROM loans l WHERE l.fee_status = 'paid') x
    WHERE x.n <> 1 OR ABS(x.s - x.admin_fee_amount) > 0.009 OR x.fee_paid_at IS NULL OR x.dt <> x.fee_paid_at) o) t

UNION ALL SELECT 42, '5) فحوصات الاتساق', 'عمولة معلّمة غير مدفوعة ولها قيد في الدفتر يعدّها مستلمة', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('قرض #', l.id, ' عمولة ', l.admin_fee_amount) AS d FROM loans l
    WHERE l.fee_status = 'unpaid' AND EXISTS (SELECT 1 FROM transactions tr WHERE tr.category = 'loan_admin_fee' AND tr.related_id = l.id)) o) t

UNION ALL SELECT 50, '5) فحوصات الاتساق', 'مبلغ التأسيس: المسدد المخزّن لا يساوي مجموع دفعاته', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('عضو #', f.member_id, ' مخزّن ', f.amount_paid, ' مقابل دفعات ', COALESCE(SUM(p.amount), 0)) AS d
    FROM founding_amounts f LEFT JOIN founding_payments p ON p.founding_amount_id = f.id
    GROUP BY f.id, f.member_id, f.amount_paid
    HAVING ABS(f.amount_paid - COALESCE(SUM(p.amount), 0)) > 0.009) o) t

UNION ALL SELECT 51, '5) فحوصات الاتساق', 'مبلغ التأسيس: دفعات التأسيس لا تساوي ما في الدفتر (لكل عضو)', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('عضو #', x.member_id, ' دفعات ', x.payments, ' مقابل الدفتر ', x.ledger) AS d FROM (
        SELECT p.member_id, SUM(p.amount) AS payments,
               COALESCE((SELECT SUM(tr.amount) FROM transactions tr WHERE tr.member_id = p.member_id AND tr.category = 'founding'), 0) AS ledger
        FROM founding_payments p GROUP BY p.member_id) x
    WHERE ABS(x.payments - x.ledger) > 0.009) o) t

UNION ALL SELECT 52, '5) فحوصات الاتساق', 'مبلغ التأسيس: حالته لا تطابق المسدد والمطلوب', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('عضو #', member_id, ' حالة ', status, ' مسدد ', amount_paid, ' من ', total_required) AS d
    FROM founding_amounts
    WHERE (status = 'paid' AND amount_paid < total_required - 0.009 AND total_required > 0)
       OR (status = 'partial' AND (amount_paid <= 0.009 OR amount_paid >= total_required - 0.009))
       OR (status = 'unpaid' AND (amount_paid > 0.009 OR total_required <= 0))) o) t

UNION ALL SELECT 60, '5) فحوصات الاتساق', 'اشتراك شهري: حالته لا تطابق المسدد والمستحق', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('عضو #', member_id, ' شهر ', month, ' مستحق ', amount_due, ' مسدد ', amount_paid, ' حالة ', status) AS d
    FROM monthly_subscriptions
    WHERE (status = 'paid' AND amount_paid < amount_due - 0.009)
       OR (status = 'partial' AND (amount_paid <= 0.009 OR amount_paid >= amount_due - 0.009))
       OR (status = 'unpaid' AND amount_paid > 0.009)) o) t

UNION ALL SELECT 61, '5) فحوصات الاتساق', 'حركات في الدفتر بتاريخ في المستقبل (لا تغيّر الإجمالي، لكن التاريخ غالبا خطأ)', t.k, IF(t.k = 0, 'OK', 'WARN'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('حركة #', id, ' ', category, ' ', amount, ' بتاريخ ', transaction_date) AS d FROM transactions WHERE transaction_date > CURDATE()) o) t

UNION ALL SELECT 70, '5) فحوصات الاتساق', 'سجل المصاريف: مبلغ غير موجب', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('مصروف #', id, ' مبلغ ', amount) AS d FROM expenses WHERE amount <= 0) o) t

UNION ALL SELECT 71, '5) فحوصات الاتساق', 'سجل المصاريف: يشير إلى عضو غير موجود', t.k, IF(t.k = 0, 'OK', 'ERROR'), t.ids
FROM (SELECT COUNT(*) AS k, GROUP_CONCAT(o.d SEPARATOR '; ') AS ids FROM (
    SELECT CONCAT('مصروف #', e.id, ' عضو #', e.member_id) AS d FROM expenses e
    WHERE e.member_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM members m WHERE m.id = e.member_id)) o) t

) AS r
ORDER BY r.n
LIMIT 1000;   -- explicit, so phpMyAdmin does not add its own LIMIT 25 and hide rows
