<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\FoundingAmount;
use App\Models\FoundingPayment;
use App\Models\Setting;

class FoundingController extends Controller
{
    public function index(): void
    {
        $member = Auth::member();
        $founding = FoundingAmount::ensureForMember($member['id']);
        $payments = FoundingPayment::forMember($member['id']);

        // The fee per share this member's amount was actually computed with (falls back to the current setting).
        $linked = (int) $founding['shares_count_linked'];
        $feePerShare = $linked > 0 ? round((float) $founding['total_required'] / $linked, 2) : (float) Setting::get('founding_fee_per_share', 0);

        $this->view('site/founding/index', [
            'pageTitle' => __('founding_amount'),
            'founding' => $founding,
            'payments' => $payments,
            'feePerShare' => $feePerShare,
            'schedule' => FoundingAmount::schedule($founding, $member),
            'maxPlanMonths' => FoundingAmount::MAX_PLAN_MONTHS,
        ], 'site/layout');
    }

    /** The member picks one payment or N monthly installments for the founding amount. */
    public function setPlan(): void
    {
        $this->verifyCsrf();
        $member = Auth::member();
        $founding = FoundingAmount::ensureForMember((int) $member['id']);

        $months = (string) $this->input('plan_months', '');
        $validator = Validator::make(['plan_months' => $months])
            ->required('plan_months', 'مدة السداد')
            ->integer('plan_months', 'مدة السداد (بالأشهر)', 1, FoundingAmount::MAX_PLAN_MONTHS);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('founding');
        }
        if ((float) $founding['total_required'] <= (float) $founding['amount_paid']) {
            Session::flash('error', 'مبلغ التأسيس مسدد بالكامل أو غير مطلوب، لا حاجة لخطة سداد.');
            $this->redirect('founding');
        }

        FoundingAmount::setPlan((int) $member['id'], (int) $months);
        Session::flash('success', (int) $months === 1 ? 'تم اختيار السداد دفعة واحدة.' : 'تم اختيار السداد على ' . (int) $months . ' أشهر.');
        $this->redirect('founding');
    }
}
