-- Quick check of the dashboard totals, for a host WITHOUT a terminal (one row, read-only).
--   phpMyAdmin > pick the site's database > "SQL" tab > paste this whole file > Go.
-- The column "difference" must be 0. For the full audit use database/audit_totals.sql.

SELECT
    x.bank                                                   AS `الرصيد الفعلي بالبنك`,
    x.remaining                                              AS `القروض القائمة`,
    x.fees_paid                                              AS `العمولات المسددة`,
    x.net                                                    AS `صافي المصاريف`,
    x.bank + x.remaining + x.fees_paid + x.net               AS `الرصيد الإجمالي (كما في الداشبورد)`,
    x.fees_due                                               AS `عمولات مستحقة (غير داخلة في الإجمالي)`,
    x.bank + x.remaining + x.fees_due                        AS `الإجمالي بدون تكرار`,
    x.c_sub + x.c_found + x.fees_paid + x.fees_due + x.net   AS `القيمة المستقلة من المدفوعات`,
    ROUND((x.bank + x.remaining + x.fees_due) - (x.c_sub + x.c_found + x.fees_paid + x.fees_due + x.net), 2) AS `الفرق (لازم صفر)`
FROM (
    SELECT
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE category IN ('subscription', 'founding', 'loan_installment', 'loan_admin_fee'))
      - (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE category = 'loan_disbursement')
      + (SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) FROM expenses) AS bank,
        (SELECT COALESCE(SUM(amount_remaining), 0) FROM loans WHERE status IN ('active', 'partial')) AS remaining,
        (SELECT COALESCE(SUM(CASE WHEN fee_status = 'paid' THEN admin_fee_amount ELSE 0 END), 0) FROM loans) AS fees_paid,
        (SELECT COALESCE(SUM(CASE WHEN fee_status = 'paid' THEN 0 ELSE admin_fee_amount END), 0) FROM loans) AS fees_due,
        (SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) FROM expenses) AS net,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE category = 'subscription') AS c_sub,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE category = 'founding') AS c_found
) AS x;
