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
        $members = $q !== '' ? Member::search($q) : Member::all('created_at DESC');

        $this->view('admin/members/index', [
            'pageTitle' => __('members'),
            'members' => $members,
            'q' => $q,
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
            'bank_account_number', 'iban', 'bank_name', 'password', 'shares_count',
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
            'status' => 'active',
        ]);

        FoundingAmount::ensureForMember($id);

        Session::flash('success', 'تم إضافة المشترك بنجاح.');
        $this->redirect('admin/members/' . $id);
    }

    public function show(string $id): void
    {
        $member = Member::find((int) $id);
        if (!$member) {
            $this->redirect('admin/members');
        }

        $founding = FoundingAmount::ensureForMember((int) $id);
        $subscriptions = MonthlySubscription::forMember((int) $id);
        $loans = Loan::forMember((int) $id);
        $transactions = Transaction::withMember(['member_id' => $id]);

        $this->view('admin/members/show', [
            'pageTitle' => $member['name'],
            'member' => $member,
            'founding' => $founding,
            'subscriptions' => array_slice($subscriptions, 0, 6),
            'loans' => $loans,
            'transactions' => array_slice($transactions, 0, 10),
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
            'bank_account_number', 'iban', 'bank_name', 'password',
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
