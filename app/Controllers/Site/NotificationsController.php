<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\LoanInstallment;
use App\Models\MonthlySubscription;
use App\Models\Notification;

class NotificationsController extends Controller
{
    public function index(): void
    {
        $member = Auth::member();
        $notifications = Notification::forMember($member['id']);

        // What the member has to pay next: shown next to the notifications so the page is useful even when it is empty.
        // A member can have several unmerged share lots at once, each billed separately this month.
        $subscription = null;
        if ((int) $member['shares_count'] > 0) {
            $rows = array_filter(
                MonthlySubscription::ensureMonthExistsForMember((int) $member['id'], date('Y-m')),
                fn($r) => (float) $r['amount_due'] - (float) $r['amount_paid'] > 0
            );
            if (!empty($rows)) {
                usort($rows, fn($a, $b) => $a['due_date'] <=> $b['due_date']);
                $subscription = $rows[0];
                $subscription['amount_due'] = array_sum(array_column($rows, 'amount_due'));
                $subscription['amount_paid'] = array_sum(array_column($rows, 'amount_paid'));
            }
        }
        $nextInstallment = LoanInstallment::rawOne(
            "SELECT li.*, l.id AS loan_number FROM loan_installments li
             JOIN loans l ON l.id = li.loan_id
             WHERE l.member_id = ? AND l.status IN ('active', 'partial') AND li.status <> 'paid'
             ORDER BY li.due_date ASC, li.installment_number ASC LIMIT 1",
            [$member['id']]
        );

        $this->view('site/notifications/index', [
            'pageTitle' => __('notifications'),
            'notifications' => $notifications,
            'subscription' => $subscription,
            'nextInstallment' => $nextInstallment,
        ], 'site/layout');
    }
}
