<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\MonthlySubscription;
use App\Models\FoundingAmount;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index(): void
    {
        $member = Auth::member();
        $shareValue = (float) Setting::get('share_value', 0);
        $monthlyAmount = $member['shares_count'] * $shareValue;

        MonthlySubscription::ensureMonthExistsForMember($member['id'], date('Y-m'));
        $subscriptions = MonthlySubscription::forMember($member['id']);
        // Several unmerged share lots can each have a row this month: summarise by what is due vs what is paid.
        $currentRows = array_values(array_filter($subscriptions, fn($s) => $s['month'] === date('Y-m')));
        $currentSub = $currentRows ? MonthlySubscription::summarize($currentRows) : null;

        // Count MONTHS (a month with two lots is one month), paid only when the whole month is collected.
        $byMonth = [];
        foreach ($subscriptions as $s) {
            $byMonth[$s['month']][] = $s;
        }
        $paidMonths = 0;
        $lateMonths = 0;
        foreach ($byMonth as $rows) {
            $sum = MonthlySubscription::summarize($rows);
            if ((float) $sum['amount_due'] > 0 && $sum['status'] === 'paid') {
                $paidMonths++;
            }
            foreach ($rows as $r) {
                if ((float) $r['amount_due'] > (float) $r['amount_paid'] && strtotime($r['due_date']) < time()) {
                    $lateMonths++;
                    break;
                }
            }
        }

        $founding = FoundingAmount::ensureForMember($member['id']);
        $loans = Loan::forMember($member['id']);
        $activeLoans = array_filter($loans, fn($l) => in_array($l['status'], ['active', 'partial']));
        $activeLoansRemaining = array_sum(array_column($activeLoans, 'amount_remaining'));
        $remainingInstallments = (int) Loan::rawOne(
            "SELECT COUNT(*) AS c FROM loan_installments li JOIN loans l ON l.id = li.loan_id
             WHERE l.member_id = ? AND l.status IN ('active', 'partial') AND li.status <> 'paid'",
            [$member['id']]
        )['c'];

        $totalSubsPaid = array_sum(array_column($subscriptions, 'amount_paid'));
        $balance = $totalSubsPaid + (float) $founding['amount_paid'] - $activeLoansRemaining;

        $transactions = Transaction::withMember(['member_id' => $member['id']]);

        $this->view('site/home/index', [
            'pageTitle' => __('home'),
            'member' => $member,
            'shareValue' => $shareValue,
            'monthlyAmount' => $monthlyAmount,
            'currentSub' => $currentSub,
            'paidMonths' => $paidMonths,
            'lateMonths' => $lateMonths,
            'founding' => $founding,
            'activeLoansCount' => count($activeLoans),
            'activeLoansRemaining' => $activeLoansRemaining,
            'remainingInstallments' => $remainingInstallments,
            'balance' => $balance,
            'transactions' => array_slice($transactions, 0, 6),
        ], 'site/layout');
    }
}
