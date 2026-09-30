<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Validator;

class Member extends Model
{
    protected static string $table = 'members';

    private static ?array $cachedAdminEmails = null;

    public static function getAdminEmails(): array
    {
        if (self::$cachedAdminEmails === null) {
            try {
                $db = \App\Core\Database::connection();
                $rows = $db->query("SELECT email FROM admins")->fetchAll(\PDO::FETCH_COLUMN);
                $list = array_map(fn($e) => mb_strtolower(trim($e)), $rows ?: []);
                $list[] = 'admin@sandouk.local';
                self::$cachedAdminEmails = array_values(array_unique(array_filter($list)));
            } catch (\Throwable $e) {
                self::$cachedAdminEmails = ['admin@sandouk.local'];
            }
        }
        return self::$cachedAdminEmails;
    }

    /**
     * Check if a member record or member ID represents an administrative account.
     * Admin accounts are exempt from loans, cannot take loans, and have no loan installments.
     */
    public static function isAdmin(array|int|null $member): bool
    {
        if ($member === null) {
            return false;
        }

        if (is_numeric($member)) {
            $member = self::find((int) $member);
            if (!$member) {
                return false;
            }
        }

        if (!empty($member['is_admin'])) {
            return true;
        }

        $email = mb_strtolower(trim((string) ($member['email'] ?? '')));
        $natId = mb_strtolower(trim((string) ($member['national_id'] ?? '')));

        $adminEmails = self::getAdminEmails();
        if (($email !== '' && in_array($email, $adminEmails, true)) ||
            ($natId !== '' && in_array($natId, $adminEmails, true))) {
            return true;
        }

        return false;
    }

    public static function search(string $term): array
    {
        $sql = 'SELECT * FROM members WHERE mobile LIKE ? OR national_id LIKE ? OR name LIKE ? ORDER BY created_at DESC';
        $like = "%{$term}%";
        return self::raw($sql, [$like, $like, $like]);
    }

    /**
     * Get maps and counts of members who are overdue for monthly subscriptions and/or loans.
     *
     * @param bool $activeOnly Whether to restrict calculation to active members only.
     */
    public static function getLateStatusInfo(bool $activeOnly = false): array
    {
        $db = \App\Core\Database::connection();
        $today = date('Y-m-d');
        $currentMonth = date('Y-m');
        $dueDay = (int) Setting::get('subscription_due_day', 10);

        $statusClause = $activeOnly ? "AND m.status = 'active'" : "";

        // 1. Members with overdue monthly subscriptions
        $subStmt = $db->prepare("SELECT DISTINCT m.id
            FROM members m
            LEFT JOIN monthly_subscriptions cur ON cur.member_id = m.id AND cur.month = :cm
            WHERE 1=1 {$statusClause} AND (
                EXISTS (
                    SELECT 1 FROM monthly_subscriptions s
                    WHERE s.member_id = m.id
                      AND s.amount_due > s.amount_paid
                      AND COALESCE(s.grace_until, s.due_date) < :today
                )
                OR (
                    m.shares_count > 0 AND cur.id IS NULL
                    AND CONCAT(:cm2, '-', LPAD(LEAST(COALESCE(m.subscription_due_day, :dd), DAY(LAST_DAY(CONCAT(:cm3, '-01')))), 2, '0')) < :today2
                )
            )");
        $subStmt->execute([
            'cm' => $currentMonth,
            'cm2' => $currentMonth,
            'cm3' => $currentMonth,
            'today' => $today,
            'today2' => $today,
            'dd' => $dueDay
        ]);
        $lateSubIds = array_map('intval', $subStmt->fetchAll(\PDO::FETCH_COLUMN));
        $lateSubMap = array_fill_keys($lateSubIds, true);

        // 2. Members with overdue loan installments (Admin accounts are exempt from loans)
        $loanStmt = $db->prepare("SELECT DISTINCT l.member_id
            FROM loans l
            JOIN loan_installments li ON li.loan_id = l.id
            JOIN members m ON m.id = l.member_id
            WHERE 1=1 {$statusClause}
              AND (m.is_admin IS NULL OR m.is_admin = 0)
              AND l.status IN ('active', 'partial')
              AND li.amount_paid < li.amount
              AND li.due_date < :today");
        $loanStmt->execute(['today' => $today]);
        $lateLoanIds = array_map('intval', $loanStmt->fetchAll(\PDO::FETCH_COLUMN));
        $lateLoanIds = array_values(array_filter($lateLoanIds, fn($id) => !self::isAdmin($id)));
        $lateLoanMap = array_fill_keys($lateLoanIds, true);

        $allLateIds = array_values(array_unique(array_merge($lateSubIds, $lateLoanIds)));

        return [
            'lateSubMap' => $lateSubMap,
            'lateLoanMap' => $lateLoanMap,
            'allLateIds' => $allLateIds,
            'totalLateCount' => count($allLateIds),
            'lateSubCount' => count($lateSubIds),
            'lateLoanCount' => count($lateLoanIds),
        ];
    }

    /**
     * Get detailed overdue stats for a specific member.
     */
    public static function getMemberOverdueDetails(int $memberId): array
    {
        $db = \App\Core\Database::connection();
        $today = date('Y-m-d');
        $currentMonth = date('Y-m');
        $member = self::find($memberId);
        if (!$member) {
            return ['is_late' => false, 'late_sub' => false, 'late_loan' => false, 'is_admin' => false];
        }

        $isAdmin = self::isAdmin($member);
        $dueDay = (int) ($member['subscription_due_day'] ?? Setting::get('subscription_due_day', 10));

        // Subscriptions
        $subStmt = $db->prepare("SELECT
            COUNT(*) as late_months,
            COALESCE(SUM(amount_due - amount_paid), 0) as amount_overdue
            FROM monthly_subscriptions
            WHERE member_id = :mid
              AND amount_due > amount_paid
              AND COALESCE(grace_until, due_date) < :today");
        $subStmt->execute(['mid' => $memberId, 'today' => $today]);
        $subRow = $subStmt->fetch();

        $lateSubMonths = (int) ($subRow['late_months'] ?? 0);
        $overdueSubAmount = (float) ($subRow['amount_overdue'] ?? 0);

        // Check if current month is missing and overdue
        $curRow = MonthlySubscription::where(['member_id' => $memberId, 'month' => $currentMonth]);
        if (empty($curRow) && (int) $member['shares_count'] > 0) {
            $curDueDate = month_due_date($currentMonth, $dueDay);
            if ($curDueDate < $today) {
                $lateSubMonths++;
                $shareVal = (float) Setting::get('share_value', 0);
                $overdueSubAmount += ((int) $member['shares_count'] * $shareVal);
            }
        }

        // Loans (Administrative accounts are completely exempt from loans and installments)
        if ($isAdmin) {
            $lateInstallments = 0;
            $overdueLoanAmount = 0.0;
        } else {
            $loanStmt = $db->prepare("SELECT
                COUNT(*) as late_installments,
                COALESCE(SUM(li.amount - li.amount_paid), 0) as amount_overdue
                FROM loan_installments li
                JOIN loans l ON l.id = li.loan_id
                WHERE l.member_id = :mid
                  AND l.status IN ('active', 'partial')
                  AND li.amount_paid < li.amount
                  AND li.due_date < :today");
            $loanStmt->execute(['mid' => $memberId, 'today' => $today]);
            $loanRow = $loanStmt->fetch();

            $lateInstallments = (int) ($loanRow['late_installments'] ?? 0);
            $overdueLoanAmount = (float) ($loanRow['amount_overdue'] ?? 0);
        }

        $lateSub = $lateSubMonths > 0;
        $lateLoan = $lateInstallments > 0;
        $isLate = $lateSub || $lateLoan;

        return [
            'is_late' => $isLate,
            'late_sub' => $lateSub,
            'late_loan' => $lateLoan,
            'is_admin' => $isAdmin,
            'late_sub_months' => $lateSubMonths,
            'overdue_sub_amount' => $overdueSubAmount,
            'late_loan_installments' => $lateInstallments,
            'overdue_loan_amount' => $overdueLoanAmount,
            'total_overdue_amount' => round($overdueSubAmount + $overdueLoanAmount, 2),
        ];
    }

    /**
     * Bring member input to its canonical stored form: +966 mobile, lower-case email,
     * upper-case IBAN and account numbers without spaces/dashes.
     */
    public static function normalize(array $data): array
    {
        if (isset($data['mobile'])) {
            $data['mobile'] = normalize_mobile((string) $data['mobile']);
        }
        if (isset($data['email'])) {
            $data['email'] = mb_strtolower(trim((string) $data['email']));
        }
        if (isset($data['iban'])) {
            $data['iban'] = strtoupper(preg_replace('/[\s\-]/', '', (string) $data['iban']));
        }
        if (isset($data['bank_account_number'])) {
            $data['bank_account_number'] = strtoupper(preg_replace('/[\s\-]/', '', (string) $data['bank_account_number']));
        }
        return $data;
    }

    /**
     * Validate only the requested member fields. $data must already be passed through normalize().
     * Allowed $fields: name, mobile, email, national_id, birth_date, national_address, bank_account_number,
     * iban, bank_name, password (+ password_confirmation when present), shares_count, subscription_due_day.
     */
    public static function validate(array $data, array $fields, ?int $ignoreId = null, bool $passwordRequired = true, bool $requireAll = false): Validator
    {
        $v = Validator::make($data);

        foreach ($fields as $field) {
            switch ($field) {
                case 'name':
                    $v->required('name', 'الاسم')->min('name', 2, 'الاسم')->max('name', 150, 'الاسم')->personName('name', 'الاسم');
                    break;
                case 'mobile':
                    $v->required('mobile', 'رقم الجوال')->mobile('mobile', 'رقم الجوال')
                        ->unique('mobile', 'members', 'mobile', 'رقم الجوال', $ignoreId);
                    break;
                case 'email':
                    $v->required('email', 'البريد الإلكتروني')->email('email', 'البريد الإلكتروني')
                        ->unique('email', 'members', 'email', 'البريد الإلكتروني', $ignoreId);
                    break;
                case 'national_id':
                    $v->required('national_id', 'رقم الهوية الوطنية')->nationalId('national_id', 'رقم الهوية الوطنية')
                        ->unique('national_id', 'members', 'national_id', 'رقم الهوية الوطنية', $ignoreId);
                    break;
                case 'birth_date':
                    $requireAll && $v->required('birth_date', 'تاريخ الميلاد');
                    $v->date('birth_date', 'الميلاد');
                    break;
                case 'national_address':
                    $requireAll && $v->required('national_address', 'العنوان الوطني');
                    $v->max('national_address', 255, 'العنوان الوطني');
                    break;
                case 'bank_account_number':
                    $requireAll && $v->required('bank_account_number', 'رقم الحساب البنكي');
                    $v->regex('bank_account_number', '/^[A-Z0-9]{5,34}$/', 'رقم الحساب البنكي يجب أن يكون من 5 إلى 34 حرفاً/رقماً.');
                    break;
                case 'iban':
                    $requireAll && $v->required('iban', 'رقم الآيبان');
                    $v->iban('iban', 'رقم الآيبان');
                    break;
                case 'bank_name':
                    $requireAll && $v->required('bank_name', 'اسم البنك');
                    $v->max('bank_name', 100, 'اسم البنك')
                        ->regex('bank_name', "/^[\\p{L}\\p{M}\\p{N}\\s.&'\\-]+$/u", 'اسم البنك يحتوي على رموز غير مسموحة.');
                    break;
                case 'password':
                    if ($passwordRequired) {
                        $v->required('password', 'كلمة المرور');
                    }
                    $v->min('password', 8, 'كلمة المرور')->max('password', 72, 'كلمة المرور');
                    if (array_key_exists('password_confirmation', $data)) {
                        $v->matches('password_confirmation', 'password', 'تأكيد كلمة المرور');
                    }
                    break;
                case 'shares_count':
                    $v->integer('shares_count', 'عدد الأسهم', 0, 10000);
                    break;
                case 'subscription_due_day':
                    // Blank keeps following the site-wide default; a lot's own due day (set at share approval) still wins over this.
                    $v->integer('subscription_due_day', 'يوم استحقاق الاشتراك الخاص بالمشترك', 1, 28);
                    break;
                case 'is_admin':
                    $v->integer('is_admin', 'الصفة الإدارية', 0, 1);
                    break;
            }
        }

        return $v;
    }
}
