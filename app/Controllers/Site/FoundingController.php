<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
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
        ], 'site/layout');
    }
}
