<?php

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\MonthlySubscription;
use App\Models\Setting;
use App\Models\ShareLot;

class SharesController extends Controller
{
    /** The member writes, edits or (empty text) deletes THEIR note on one of THEIR OWN active shares. */
    public function updateNote(string $lotId): void
    {
        $this->verifyCsrf();
        $member = Auth::member();
        $lot = ShareLot::find((int) $lotId);
        if (!$lot || (int) $lot['member_id'] !== (int) $member['id'] || $lot['status'] !== 'active') {
            \App\Core\Session::flash('error', 'هذا السهم غير موجود في حسابك.');
            $this->redirect('shares');
        }
        $note = trim((string) $this->input('member_note', ''));
        if (mb_strlen($note) > 500) {
            \App\Core\Session::flash('error', 'الملاحظة يجب ألا تزيد عن 500 حرف.');
            $this->redirect('shares');
        }
        ShareLot::setMemberNote((int) $lotId, $note);
        \App\Core\Session::flash('success', $note === '' ? 'تم حذف ملاحظتك على ' . ShareLot::label((int) $member['id'], (int) $lotId) . '.' : 'تم حفظ ملاحظتك على ' . ShareLot::label((int) $member['id'], (int) $lotId) . '.');
        $this->redirect('shares');
    }

    public function index(): void
    {
        $member = Auth::member();
        MonthlySubscription::ensureMonthExistsForMember($member['id'], date('Y-m'));
        $history = MonthlySubscription::forMember($member['id']);
        $shareValue = (float) Setting::get('share_value', 0);
        $lots = ShareLot::activeFor($member['id']);
        $dueDay = MonthlySubscription::dueDayFor($member);

        $founding = \App\Models\FoundingAmount::ensureForMember($member['id']);

        $this->view('site/shares/index', [
            'pageTitle' => __('shares_and_subscriptions'),
            'member' => $member,
            'history' => $history,
            'shareValue' => $shareValue,
            'lots' => $lots,
            'dueDay' => $dueDay,
            'founding' => $founding,
        ], 'site/layout');
    }
}
