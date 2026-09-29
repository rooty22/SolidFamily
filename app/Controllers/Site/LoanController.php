<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Setting;
use App\Models\Loan;
use App\Models\LoanRequest;
use App\Models\LoanInstallment;
use App\Models\ContentPage;

class LoanController extends Controller
{
    private array $reasonLabels = [];

    public function __construct()
    {
        $this->reasonLabels = loan_reasons();
    }

    public function index(): void
    {
        $member = Auth::member();
        $requests = LoanRequest::where(['member_id' => $member['id']], 'created_at DESC');
        foreach ($requests as &$r) {
            // Do not display queue position to the client until admin approval
            $r['queue_position'] = $r['status'] === 'approved' ? LoanRequest::queuePosition($r['id']) : null;
        }
        unset($r);
        $loans = Loan::forMember($member['id']);

        $running = array_filter($loans, fn($l) => in_array($l['status'], ['active', 'partial'], true));
        $nextInstallment = LoanInstallment::rawOne(
            "SELECT li.*, l.id AS loan_number FROM loan_installments li
             JOIN loans l ON l.id = li.loan_id
             WHERE l.member_id = ? AND l.status IN ('active', 'partial') AND li.status <> 'paid'
             ORDER BY li.due_date ASC, li.installment_number ASC LIMIT 1",
            [$member['id']]
        );

        $this->view('site/loans/index', [
            'pageTitle' => __('loans_and_requests'),
            'requests' => $requests,
            'loans' => $loans,
            'reasonLabels' => $this->reasonLabels,
            'runningCount' => count($running),
            'runningRemaining' => array_sum(array_column($running, 'amount_remaining')),
            'pendingRequests' => count(array_filter($requests, fn($r) => $r['status'] === 'pending')),
            'nextInstallment' => $nextInstallment,
        ], 'site/layout');
    }

    public function createRequest(): void
    {
        $member = Auth::member();
        $commitment = ContentPage::bySlug('loan_commitment');

        $shares = (int) $member['shares_count'];
        $shareValue = (float) Setting::get('share_value', 0);
        $ratio = (float) Setting::get('max_loan_ratio', 10);
        $running = Loan::rawOne(
            "SELECT COUNT(*) AS c, COALESCE(SUM(amount_remaining), 0) AS s FROM loans WHERE member_id = ? AND status IN ('active', 'partial')",
            [$member['id']]
        );

        $lots = \App\Models\ShareLot::activeFor($member['id']);
        $founding = \App\Models\FoundingAmount::ensureForMember($member['id']);

        $lotsData = [];
        foreach ($lots as $lot) {
            $elig = \App\Models\ShareLot::eligibilityDetails($lot, $member, $founding);
            $lotShares = (int) $lot['shares_count'];
            $lotCapital = $lotShares * $shareValue;
            $lotMaxLoan = $lotCapital * $ratio;
            $lotsData[] = [
                'id' => (int) $lot['id'],
                'shares_count' => $lotShares,
                'capital' => $lotCapital,
                'max_loan' => $lotMaxLoan,
                'start_date' => $elig['start_date'],
                'start_date_ar' => date_ar($elig['start_date']),
                'target_date' => $elig['target_date'],
                'target_date_ar' => date_ar($elig['target_date']),
                'six_months_met' => (bool) $elig['six_months_met'],
                'months_passed' => (int) $elig['months_passed'],
                'months_remaining' => (int) $elig['months_remaining'],
                'months_remaining_label' => $elig['months_remaining_label'],
                'founding_met' => (bool) $elig['founding_met'],
                'founding_paid_per_share' => (float) $elig['founding_paid_per_share'],
                'is_eligible' => (bool) $elig['is_eligible'],
            ];
        }

        $totalShares = (int) ($member['shares_count'] ?? 0);
        $foundingPaid = (float) ($founding['amount_paid'] ?? 0);
        $paidPerShare = $totalShares > 0 ? ($foundingPaid / $totalShares) : 0;
        $foundingMet = ($founding['status'] === 'paid') || ($paidPerShare >= 500) || ((float)($founding['total_required'] ?? 0) <= 0);

        $hasEligibleLot = false;
        foreach ($lotsData as $ld) {
            if ($ld['six_months_met']) {
                $hasEligibleLot = true;
                break;
            }
        }

        $canRequest = ($shares > 0) && $foundingMet && $hasEligibleLot;
        $defaultMax = !empty($lotsData) ? $lotsData[0]['max_loan'] : ($shares * $shareValue * $ratio);

        $this->view('site/loans/create-request', [
            'pageTitle' => __('new_loan_request'),
            'reasonLabels' => $this->reasonLabels,
            'commitmentText' => $commitment['content'] ?? '',
            'shares' => $shares,
            'shareValue' => $shareValue,
            'ratio' => $ratio,
            'capital' => $shares * $shareValue,
            'maxLoan' => $defaultMax,
            'runningCount' => (int) $running['c'],
            'runningRemaining' => (float) $running['s'],
            'pendingRequests' => LoanRequest::count(['member_id' => $member['id'], 'status' => 'pending']),
            'lots' => $lots,
            'lotsData' => $lotsData,
            'founding' => $founding,
            'foundingMet' => $foundingMet,
            'hasEligibleLot' => $hasEligibleLot,
            'canRequest' => $canRequest,
            'member' => $member,
        ], 'site/layout');
    }

    public function storeRequest(): void
    {
        $this->verifyCsrf();
        $member = Auth::member();
        $data = $this->all();

        $validator = Validator::make($data)
            ->required('amount_requested', 'مبلغ القرض')
            ->decimal('amount_requested', 'مبلغ القرض', 1, 10000000)
            ->required('reason', 'سبب القرض')
            ->in('reason', array_keys($this->reasonLabels), 'سبب القرض')
            ->required('installments_months', 'مدة السداد')
            ->integer('installments_months', 'مدة السداد (بالأشهر)', 1, 60)
            ->max('reason_other_text', 500, 'وصف السبب');

        if (($data['reason'] ?? '') === 'other') {
            $validator->required('reason_other_text', 'وصف السبب الآخر');
        }
        if (empty($data['agreed_terms'])) {
            $validator->required('agreed_terms', 'الموافقة على شروط السداد');
        }

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('loans/request');
        }

        $amount = round((float) $data['amount_requested'], 2);
        $months = (int) $data['installments_months'];

        // Lot selection for unmerged shares
        $lotId = !empty($data['lot_id']) ? (int) $data['lot_id'] : null;
        $activeLots = \App\Models\ShareLot::activeFor($member['id']);
        $selectedLot = null;

        if (!empty($activeLots)) {
            if ($lotId) {
                foreach ($activeLots as $l) {
                    if ((int) $l['id'] === $lotId) {
                        $selectedLot = $l;
                        break;
                    }
                }
            }
            if (!$selectedLot && count($activeLots) === 1) {
                $selectedLot = $activeLots[0];
                $lotId = (int) $selectedLot['id'];
            }
        }

        if (empty($activeLots)) {
            Session::flash('error', 'لا يمكن تقديم طلب قرض قبل امتلاك سهم واحد على الأقل.');
            $this->redirect('loans/request');
        }

        // Validate Founding Amount: Cannot make debt until paying founding amount in full
        $founding = \App\Models\FoundingAmount::ensureForMember($member['id']);
        $totalShares = (int) ($member['shares_count'] ?? 1);
        $foundingPaid = (float) ($founding['amount_paid'] ?? 0);
        $paidPerShare = $totalShares > 0 ? ($foundingPaid / $totalShares) : 0;
        $foundingMet = ($founding['status'] === 'paid') || ($paidPerShare >= 500) || ((float)($founding['total_required'] ?? 0) <= 0);

        if (!$foundingMet) {
            Session::flash('error', 'تنبيه مبلغ التأسيس: لا يمكن طلب قرض إلا بعد سداد كامل مبلغ التأسيس.');
            $this->redirect('loans/request');
        }

        if (count($activeLots) > 1 && !$selectedLot) {
            Session::flash('error', 'يرجى تحديد السهم / الحصة المراد تقديم طلب القرض عليها.');
            $this->redirect('loans/request');
        }

        // Validate 6 months passing for the selected lot
        $lotElig = \App\Models\ShareLot::eligibilityDetails($selectedLot, $member, $founding);
        if (!$lotElig['six_months_met']) {
            Session::flash('error', 'تنبيه شرط المدة: لا يمكن تقديم طلب قرض على هذه الحصة قبل مرور 6 أشهر كاملة على تاريخ بداية الاشتراك (تاريخ الاستحقاق: ' . date_ar($lotElig['target_date']) . ' - متبقي ' . $lotElig['months_remaining_label'] . ').');
            $this->redirect('loans/request');
        }

        // Eligibility: the requested amount cannot exceed the selected lot's maximum allowed loan
        $shareValue = (float) Setting::get('share_value', 0);
        $ratio = (float) Setting::get('max_loan_ratio', 10);
        $lotShares = (int) ($selectedLot ? $selectedLot['shares_count'] : $member['shares_count']);
        $maxLoan = $lotShares * $shareValue * $ratio;

        if ($amount > $maxLoan) {
            Session::flash('error', 'المبلغ المطلوب (' . money($amount) . ') يتجاوز الحد الأقصى المسموح للحصة المحددة (' . money($maxLoan) . ').');
            $this->redirect('loans/request');
        }

        LoanRequest::create([
            'member_id' => $member['id'],
            'lot_id' => $lotId,
            'amount_requested' => $amount,
            'reason' => $data['reason'],
            'reason_other_text' => ($data['reason'] === 'other') ? $data['reason_other_text'] : null,
            'installments_months' => $months,
            'agreed_terms' => 1,
            'status' => 'pending',
        ]);

        Session::flash('success', 'تم إرسال طلب القرض للإدارة للمراجعة.');
        $this->redirect('loans');
    }

    public function show(string $id): void
    {
        $member = Auth::member();
        $loan = Loan::find((int) $id);
        if (!$loan || (int) $loan['member_id'] !== (int) $member['id']) {
            $this->redirect('loans');
        }
        $installments = LoanInstallment::forLoan((int) $id);

        $this->view('site/loans/show', [
            'pageTitle' => __('loan_details'),
            'loan' => $loan,
            'installments' => $installments,
            'reasonLabels' => $this->reasonLabels,
        ], 'site/layout');
    }
}
