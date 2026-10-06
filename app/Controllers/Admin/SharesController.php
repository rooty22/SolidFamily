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
        $lotsByMember = [];
        foreach ($members as $m) {
            $lotsByMember[$m['id']] = \App\Models\ShareLot::activeFor((int) $m['id']);
        }

        $this->view('admin/shares/index', [
            'pageTitle' => __('shares_management'),
            'members' => $members,
            'shareValue' => $shareValue,
            'lotsByMember' => $lotsByMember,
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

    /**
     * Direct manual override of a member's total shares, bypassing the share-request/approval flow.
     */
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
                $startDate = trim((string) $this->input('start_date', ''));
                $createdAt = ($startDate !== '' && strtotime($startDate)) ? date('Y-m-d H:i:s', strtotime($startDate . ' 12:00:00')) : null;
                // Billing follows the share lots: keep them in step with the total the admin just set.
                $emptied = \App\Models\ShareLot::syncToMemberTotal((int) $id, $createdAt);
                \App\Models\MonthlySubscription::voidRowsOfLots((int) $id, $emptied, false);
                \App\Models\MonthlySubscription::ensureMonthExistsForMember((int) $id, date('Y-m'));
                \App\Models\MonthlySubscription::purgeStalePreStartRows((int) $id);
                \App\Models\FoundingAmount::ensureForMember((int) $id);
                Session::flash('success', 'تم تحديث عدد أسهم المشترك بنجاح.');
            }
        }
        $this->redirect('admin/shares');
    }

    /** Merge all active share lots of a member into one lot */
    public function mergeShares(string $id): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $id);
        if (!$member) {
            Session::flash('error', 'المشترك غير موجود.');
            $this->redirect('admin/shares');
            return;
        }

        $activeLots = \App\Models\ShareLot::activeFor((int) $id);
        if (count($activeLots) < 2) {
            Session::flash('error', 'لا يمكن الدمج لأن المشترك لديه حصة واحدة فقط.');
            $this->redirect('admin/shares');
            return;
        }

        $dueDay = $member['subscription_due_day'] !== null ? (int) $member['subscription_due_day'] : null;
        \App\Models\ShareLot::mergeAllFor((int) $id, $dueDay);
        
        \App\Models\MonthlySubscription::ensureMonthExistsForMember((int) $id, date('Y-m'));
        Session::flash('success', 'تم دمج جميع حصص الأسهم في سهم واحد بنجاح.');
        $this->redirect('admin/shares');
    }

    /** Cancel a specific share lot of a member */
    public function cancelLot(string $id, string $lotId): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $id);
        $lot = \App\Models\ShareLot::find((int) $lotId);
        if (!$member || !$lot || (int) $lot['member_id'] !== (int) $id) {
            Session::flash('error', 'بيانات الحصة غير صحيحة.');
            $this->redirect('admin/shares');
            return;
        }

        $lotShares = (int) $lot['shares_count'];
        \App\Models\ShareLot::update((int) $lotId, ['status' => 'merged']);
        
        // Update member total shares
        $newTotal = max(0, (int) $member['shares_count'] - $lotShares);
        Member::update((int) $id, ['shares_count' => $newTotal]);
        
        // Void unpaid future rows of this lot and sync founding amount
        \App\Models\MonthlySubscription::voidRowsOfLots((int) $id, [(int) $lotId], false);
        \App\Models\MonthlySubscription::purgeStalePreStartRows((int) $id);
        \App\Models\FoundingAmount::ensureForMember((int) $id);

        Session::flash('success', "تم إلغاء الحصة وتخفيض {$lotShares} سهم من رصيد المشترك بنجاح.");
        $this->redirect('admin/shares');
    }

    /** Add a specific new share lot for a member */
    public function addLot(string $id): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $id);
        if (!$member) {
            Session::flash('error', 'المشترك غير موجود.');
            $this->redirect('admin/shares');
            return;
        }

        $shares = (int) $this->input('shares_count', 1);
        if ($shares < 1) {
            Session::flash('error', 'عدد الأسهم يجب أن يكون 1 على الأقل.');
            $this->redirect('admin/shares');
            return;
        }

        $startDate = trim((string) $this->input('start_date', ''));
        if ($startDate === '' || !strtotime($startDate)) {
            $startDate = date('Y-m-d');
        }

        $dueDay = $member['subscription_due_day'] !== null ? (int) $member['subscription_due_day'] : null;
        \App\Models\ShareLot::create([
            'member_id' => (int) $id,
            'shares_count' => $shares,
            'subscription_due_day' => $dueDay,
            'status' => 'active',
            'created_at' => $startDate . ' 12:00:00',
        ]);

        $newTotal = (int) $member['shares_count'] + $shares;
        Member::update((int) $id, ['shares_count' => $newTotal]);

        // Backfill months if start date is in past or current
        $startMonth = substr($startDate, 0, 7);
        if ($startMonth <= date('Y-m')) {
            $cur = strtotime($startMonth . '-01');
            $end = strtotime(date('Y-m-01'));
            $safety = 0;
            while ($cur <= $end && $safety < 60) {
                \App\Models\MonthlySubscription::ensureMonthExistsForMember((int) $id, date('Y-m', $cur));
                $cur = strtotime('+1 month', $cur);
                $safety++;
            }
        } else {
            \App\Models\MonthlySubscription::ensureMonthExistsForMember((int) $id, date('Y-m'));
        }

        \App\Models\FoundingAmount::ensureForMember((int) $id);

        Session::flash('success', "تم إضافة حصة جديدة بعدد {$shares} سهم وتاريخ بداية {$startDate} بنجاح.");
        $this->redirect('admin/shares');
    }
}
