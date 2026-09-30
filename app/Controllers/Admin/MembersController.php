<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Member;
use App\Models\FoundingAmount;
use App\Models\MonthlySubscription;
use App\Models\Loan;
use App\Models\Transaction;

class MembersController extends Controller
{
    public function index(): void
    {
        $q = trim((string) $this->input('q', ''));
        $paymentStatus = trim((string) $this->input('payment_status', ''));
        $accountStatus = trim((string) $this->input('account_status', ''));
        $statusParam = trim((string) $this->input('status', ''));

        if ($statusParam !== '') {
            if (in_array($statusParam, ['late', 'late_subscription', 'late_loan', 'up_to_date'])) {
                if ($paymentStatus === '') {
                    $paymentStatus = $statusParam;
                }
            } elseif (in_array($statusParam, ['active', 'inactive'])) {
                if ($accountStatus === '') {
                    $accountStatus = $statusParam;
                }
            }
        }

        $allMembers = $q !== '' ? Member::search($q) : Member::all('created_at DESC');
        $lateInfo = Member::getLateStatusInfo(false);

        $counts = [
            'all' => count($allMembers),
            'late' => 0,
            'late_subscription' => 0,
            'late_loan' => 0,
            'up_to_date' => 0,
        ];

        $decorated = [];
        foreach ($allMembers as $m) {
            $mId = (int) $m['id'];
            $isAdmin = Member::isAdmin($m);
            $m['is_admin'] = $isAdmin;
            $isLateSub = isset($lateInfo['lateSubMap'][$mId]);
            $isLateLoan = !$isAdmin && isset($lateInfo['lateLoanMap'][$mId]);
            $isLate = $isLateSub || $isLateLoan;

            if ($isLateSub && $isLateLoan) {
                $pStatus = 'late_both';
            } elseif ($isLateSub) {
                $pStatus = 'late_subscription';
            } elseif ($isLateLoan) {
                $pStatus = 'late_loan';
            } else {
                $pStatus = 'up_to_date';
            }

            if ($isLate) {
                $counts['late']++;
            } else {
                $counts['up_to_date']++;
            }
            if ($isLateSub) {
                $counts['late_subscription']++;
            }
            if ($isLateLoan) {
                $counts['late_loan']++;
            }

            $m['payment_status'] = $pStatus;
            $m['is_late'] = $isLate;
            $m['is_late_sub'] = $isLateSub;
            $m['is_late_loan'] = $isLateLoan;

            // Apply filters
            if ($paymentStatus === 'late' && !$isLate) {
                continue;
            }
            if ($paymentStatus === 'late_subscription' && !$isLateSub) {
                continue;
            }
            if ($paymentStatus === 'late_loan' && !$isLateLoan) {
                continue;
            }
            if ($paymentStatus === 'up_to_date' && $isLate) {
                continue;
            }
            if ($accountStatus !== '' && $m['status'] !== $accountStatus) {
                continue;
            }

            $decorated[] = $m;
        }

        $this->view('admin/members/index', [
            'pageTitle' => __('members'),
            'members' => $decorated,
            'q' => $q,
            'paymentStatus' => $paymentStatus,
            'accountStatus' => $accountStatus,
            'counts' => $counts,
            'lateInfo' => $lateInfo,
        ], 'admin/layout');
    }

    public function create(): void
    {
        $this->view('admin/members/create', ['pageTitle' => __('add_member_title')], 'admin/layout');
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $data = Member::normalize($this->all());

        $validator = Member::validate($data, [
            'name', 'mobile', 'email', 'national_id', 'birth_date', 'national_address',
            'bank_account_number', 'iban', 'bank_name', 'password', 'shares_count', 'subscription_due_day',
        ]);

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Session::setOld($data);
            $this->redirect('admin/members/create');
        }

        $id = Member::create([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'],
            'birth_date' => ($data['birth_date'] ?? '') ?: null,
            'national_address' => ($data['national_address'] ?? '') ?: null,
            'national_id' => $data['national_id'],
            'bank_account_number' => ($data['bank_account_number'] ?? '') ?: null,
            'iban' => ($data['iban'] ?? '') ?: null,
            'bank_name' => ($data['bank_name'] ?? '') ?: null,
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'shares_count' => (int) ($data['shares_count'] ?? 0),
            'subscription_due_day' => ($data['subscription_due_day'] ?? '') !== '' ? (int) $data['subscription_due_day'] : null,
            'is_admin' => !empty($data['is_admin']) ? 1 : 0,
            'status' => 'active',
        ]);

        FoundingAmount::ensureForMember($id);
        \App\Models\ShareLot::syncToMemberTotal((int) $id); // shares given at creation bill through a lot

        Session::flash('success', 'تم إضافة المشترك بنجاح.');
        $this->redirect('admin/members/' . $id);
    }

    public function show(string $id): void
    {
        $member = Member::find((int) $id);
        if (!$member) {
            $this->redirect('admin/members');
        }

        $isAdminMember = Member::isAdmin($member);
        $founding = FoundingAmount::ensureForMember((int) $id);
        $subscriptions = MonthlySubscription::forMember((int) $id);
        $loans = $isAdminMember ? [] : Loan::forMember((int) $id);
        $transactions = Transaction::withMember(['member_id' => $id]);
        $overdueDetails = Member::getMemberOverdueDetails((int) $id);

        $lots = \App\Models\ShareLot::activeFor((int) $id);

        $this->view('admin/members/show', [
            'pageTitle' => $member['name'],
            'member' => $member,
            'isAdminMember' => $isAdminMember,
            'founding' => $founding,
            'subscriptions' => array_slice($subscriptions, 0, 6),
            'loans' => $loans,
            'transactions' => array_slice($transactions, 0, 10),
            'overdueDetails' => $overdueDetails,
            'lots' => $lots,
        ], 'admin/layout');
    }

    public function edit(string $id): void
    {
        $member = Member::find((int) $id);
        if (!$member) {
            $this->redirect('admin/members');
        }
        $this->view('admin/members/edit', ['pageTitle' => __('edit_member'), 'member' => $member], 'admin/layout');
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $id);
        if (!$member) {
            $this->redirect('admin/members');
        }

        $data = Member::normalize($this->all());
        $validator = Member::validate($data, [
            'name', 'mobile', 'email', 'national_id', 'birth_date', 'national_address',
            'bank_account_number', 'iban', 'bank_name', 'password', 'subscription_due_day',
        ], (int) $id, false);

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('admin/members/' . $id . '/edit');
        }

        $update = [
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'],
            'birth_date' => ($data['birth_date'] ?? '') ?: null,
            'national_address' => ($data['national_address'] ?? '') ?: null,
            'national_id' => $data['national_id'],
            'bank_account_number' => ($data['bank_account_number'] ?? '') ?: null,
            'iban' => ($data['iban'] ?? '') ?: null,
            'bank_name' => ($data['bank_name'] ?? '') ?: null,
        ];
        if (array_key_exists('subscription_due_day', $data)) {
            $update['subscription_due_day'] = ($data['subscription_due_day'] ?? '') !== '' ? (int) $data['subscription_due_day'] : null;
        }
        if (array_key_exists('is_admin', $data)) {
            $update['is_admin'] = !empty($data['is_admin']) ? 1 : 0;
        }

        if (!empty($data['password'])) {
            $update['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        Member::update((int) $id, $update);

        Session::flash('success', 'تم تحديث بيانات المشترك بنجاح.');
        $this->redirect('admin/members/' . $id);
    }

    public function toggleStatus(string $id): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $id);
        if ($member) {
            $newStatus = $member['status'] === 'active' ? 'inactive' : 'active';
            Member::update((int) $id, ['status' => $newStatus]);
            Session::flash('success', $newStatus === 'active' ? 'تم تفعيل حساب المشترك.' : 'تم إيقاف حساب المشترك.');
        }
        $this->redirect('admin/members');
    }
}
