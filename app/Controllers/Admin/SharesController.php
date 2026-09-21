<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Member;
use App\Models\Setting;

class SharesController extends Controller
{
    public function index(): void
    {
        $members = Member::all('shares_count DESC');
        $shareValue = (float) Setting::get('share_value', 0);

        $this->view('admin/shares/index', [
            'pageTitle' => __('shares_management'),
            'members' => $members,
            'shareValue' => $shareValue,
        ], 'admin/layout');
    }

    public function updateShareValue(): void
    {
        $this->verifyCsrf();
        $validator = Validator::make(['share_value' => (string) $this->input('share_value', '')])
            ->required('share_value', 'قيمة السهم')
            ->decimal('share_value', 'قيمة السهم', 1, 1000000);
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
        } else {
            Setting::set('share_value', (string) $this->input('share_value'));
            Session::flash('success', 'تم تحديث قيمة السهم بنجاح.');
        }
        $this->redirect('admin/shares');
    }

    public function updateMemberShares(string $id): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $id);
        if ($member) {
            $validator = Validator::make(['shares_count' => (string) $this->input('shares_count', '')])
                ->required('shares_count', 'عدد الأسهم')
                ->integer('shares_count', 'عدد الأسهم', 0, 10000);
            if ($validator->fails()) {
                Session::flash('error', $validator->firstError());
            } else {
                Member::update((int) $id, ['shares_count' => (int) $this->input('shares_count')]);
                \App\Models\MonthlySubscription::ensureMonthExists((int) $id, date('Y-m'));
                Session::flash('success', 'تم تحديث عدد أسهم المشترك بنجاح.');
            }
        }
        $this->redirect('admin/shares');
    }
}
