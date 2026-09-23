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
        // A member can have several unmerged share lots at once now, each with its own row this
        // month: show the worst status among them rather than an arbitrary single one.
        $currentSub = null;
        foreach ($subscriptions as $s) {
            if ($s['month'] !== date('Y-m')) {
                continue;
            }
            if ($currentSub === null || $s['status'] === 'unpaid'
                || ($s['status'] === 'partial' && $currentSub['status'] === 'paid')) {
                $currentSub = $s;
            }
        }
        $paidMonths = count(array_filter($subscriptions, fn($s) => $s['status'] === 'paid'));
        $lateMonths = count(array_filter($subscriptions, fn($s) => $s['status'] !== 'paid' && strtotime($s['due_date']) < time()));

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
