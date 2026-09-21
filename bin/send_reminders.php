<?php

/**
 * Automatic member notifications (run once a day from cron / Windows Task Scheduler):
 *
 *   php bin/send_reminders.php            send the notifications
 *   php bin/send_reminders.php --dry-run  only report what would be sent
 *
 * Per member, at most ONE notification per event (the unique dedupe key makes re-running safe):
 *   - the monthly subscription is due within `reminder_days_before` days (default 3)
 *   - the monthly subscription is overdue (one notice per overdue month)
 *   - a loan installment is due soon / overdue (one notice per installment)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require __DIR__ . '/../app/bootstrap.php';

use App\Models\LoanInstallment;
use App\Models\Member;
use App\Models\MonthlySubscription;
use App\Models\Notification;
use App\Models\Setting;

$dryRun = in_array('--dry-run', $argv ?? [], true);
$today = date('Y-m-d');
$currentMonth = date('Y-m');
$daysBefore = max(0, (int) Setting::get('reminder_days_before', 3));
$horizon = date('Y-m-d', strtotime("+{$daysBefore} days"));

$sent = ['sub_due' => 0, 'sub_late' => 0, 'inst_due' => 0, 'inst_late' => 0];

$notify = function (int $memberId, string $type, string $key, string $title, string $body) use (&$sent, $dryRun): void {
    if ($dryRun) {
        $exists = Notification::first(['target_member_id' => $memberId, 'dedupe_key' => $key]);
        if (!$exists) {
            $sent[$type]++;
            echo "[dry-run] member #{$memberId}: {$title}\n";
        }
        return;
    }
    if (Notification::systemNotify($memberId, $title, $body, $key)) {
        $sent[$type]++;
    }
};

$fmt = fn($n): string => number_format((float) $n, 2) . ' ريال';

// ---------- monthly subscriptions ----------
foreach (Member::where(['status' => 'active']) as $member) {
    if ((int) $member['shares_count'] <= 0) {
        continue;
    }
    $memberId = (int) $member['id'];

    // Make sure this month's row exists (it is what the member is asked to pay).
    $current = MonthlySubscription::ensureMonthExists($memberId, $currentMonth);

    $rows = MonthlySubscription::raw(
        "SELECT * FROM monthly_subscriptions
         WHERE member_id = ? AND status <> 'paid' AND amount_due > 0 AND (due_date < ? OR month = ?)",
        [$memberId, $today, $currentMonth]
    );

    foreach ($rows as $sub) {
        $remaining = round((float) $sub['amount_due'] - (float) $sub['amount_paid'], 2);
        if ($remaining <= 0) {
            continue;
        }
        if ($sub['due_date'] < $today) {
            $notify($memberId, 'sub_late', "sub-late:{$sub['month']}",
                'تنبيه: تأخر في سداد الاشتراك الشهري',
                "لم يتم سداد اشتراك شهر {$sub['month']} (المتبقي " . $fmt($remaining) . ") وكان موعده {$sub['due_date']}. الرجاء السداد في أقرب وقت.");
        } elseif ($sub['due_date'] <= $horizon) {
            $notify($memberId, 'sub_due', "sub-due:{$sub['month']}",
                'تذكير باقتراب موعد الاشتراك الشهري',
                "يستحق اشتراك شهر {$sub['month']} بقيمة " . $fmt($remaining) . " بتاريخ {$sub['due_date']}. الرجاء السداد قبل الموعد.");
        }
    }
}

// ---------- loan installments ----------
$installments = LoanInstallment::raw(
    "SELECT li.*, l.member_id FROM loan_installments li
     JOIN loans l ON l.id = li.loan_id
     JOIN members m ON m.id = l.member_id
     WHERE l.status IN ('active', 'partial') AND li.status <> 'paid' AND li.due_date <= ? AND m.status = 'active'",
    [$horizon]
);
foreach ($installments as $inst) {
    $remaining = round((float) $inst['amount'] - (float) $inst['amount_paid'], 2);
    if ($remaining <= 0) {
        continue;
    }
    $memberId = (int) $inst['member_id'];
    if ($inst['due_date'] < $today) {
        $notify($memberId, 'inst_late', "inst-late:{$inst['id']}",
            'تنبيه: تأخر في سداد قسط القرض',
            "لم يتم سداد القسط رقم {$inst['installment_number']} من القرض رقم {$inst['loan_id']} (المتبقي " . $fmt($remaining) . ") وكان موعده {$inst['due_date']}.");
    } else {
        $notify($memberId, 'inst_due', "inst-due:{$inst['id']}",
            'تذكير باقتراب موعد قسط القرض',
            "يستحق القسط رقم {$inst['installment_number']} من القرض رقم {$inst['loan_id']} بقيمة " . $fmt($remaining) . " بتاريخ {$inst['due_date']}.");
    }
}

echo ($dryRun ? '[dry-run] ' : '') . 'subscription reminders: ' . $sent['sub_due']
    . ' | subscription late notices: ' . $sent['sub_late']
    . ' | installment reminders: ' . $sent['inst_due']
    . ' | installment late notices: ' . $sent['inst_late'] . "\n";
