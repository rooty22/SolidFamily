<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\MonthlySubscription;
use App\Models\Setting;

class SharesController extends Controller
{
    public function index(): void
    {
        $member = Auth::member();
        MonthlySubscription::ensureMonthExists($member['id'], date('Y-m'));
        $history = MonthlySubscription::forMember($member['id']);
        $shareValue = (float) Setting::get('share_value', 0);
        $dueDay = MonthlySubscription::dueDayFor($member);

        $this->view('site/shares/index', [
            'pageTitle' => __('shares_and_subscriptions'),
            'member' => $member,
            'history' => $history,
            'shareValue' => $shareValue,
            'dueDay' => $dueDay,
        ], 'site/layout');
    }
}
