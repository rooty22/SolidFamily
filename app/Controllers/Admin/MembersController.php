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
        [$decorated, $counts, $q, $paymentStatus, $accountStatus, $lateInfo] = $this->filteredMembers();

        $pager = paginate($decorated);

        $this->view('admin/members/index', [
            'pageTitle' => __('members'),
            'members' => $pager['items'],
            'pager' => $pager,
            'q' => $q,
            'paymentStatus' => $paymentStatus,
            'accountStatus' => $accountStatus,
            'counts' => $counts,
            'lateInfo' => $lateInfo,
            'pendingCount' => Member::count(['status' => 'pending']),
        ], 'admin/layout');
    }

    /**
     * The members of the list with the filters of the page (search, payment status, account status), each with its payment
     * status worked out, plus the counters of the filter buttons. Shared by the list and the Excel export, so both always
     * show the same members.
     *
     * @return array{0: array, 1: array, 2: string, 3: string, 4: string, 5: array}
     */
    private function filteredMembers(): array
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
            } elseif (in_array($statusParam, ['active', 'inactive', 'pending', 'rejected'])) {
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
            $isLateLoan = isset($lateInfo['lateLoanMap'][$mId]);
            $isLate = $isLateSub || $isLateLoan;

            if ($isLateSub && $isLateLoan) {
                $pStatus = 'late_both';
            } elseif ($isLateSub) {
                $pStatus = 'late_subscription';
            } elseif ($isLateLoan) {
                $pStatus = 'late_loan';
            } elseif ((int) $m['shares_count'] <= 0) {
                // A member who owns nothing yet (e.g. just registered) is not "up to date": there is nothing to pay.
                $pStatus = 'no_shares';
            } else {
                $pStatus = 'up_to_date';
            }

            if ($isLate) {
                $counts['late']++;
            } elseif ($pStatus === 'up_to_date') {
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
            if ($paymentStatus === 'up_to_date' && $pStatus !== 'up_to_date') {
                continue;
            }
            if ($accountStatus !== '' && $m['status'] !== $accountStatus) {
                continue;
            }

            $decorated[] = $m;
        }

        return [$decorated, $counts, $q, $paymentStatus, $accountStatus, $lateInfo];
    }

    /**
     * The members of the list (same filters as the page) as a real Excel file: every field of the member, where he stands
     * on the monthly subscription, his founding amount and his loans, in ordered columns with the right types (IDs and
     * phone numbers stay text, amounts are numbers, dates are dates). ?format=csv gives a semicolon CSV instead, which is
     * also what is sent when the server has no zip extension.
     */
    public function export(): void
    {
        [$members, , $q, $paymentStatus, $accountStatus] = $this->filteredMembers();
        $ar = is_rtl();
        $shareValue = (float) \App\Models\Setting::get('share_value', 0);

        // what each member has paid / still owes, read for everybody at once
        $founding = [];
        foreach (FoundingAmount::raw('SELECT member_id, total_required, amount_paid FROM founding_amounts') as $f) {
            $founding[(int) $f['member_id']] = $f;
        }
        $loans = [];
        foreach (Loan::raw("SELECT member_id, COUNT(*) AS c, COALESCE(SUM(amount_remaining), 0) AS r FROM loans WHERE status IN ('active', 'partial') GROUP BY member_id") as $l) {
            $loans[(int) $l['member_id']] = $l;
        }

        $accountLabels = $ar
            ? ['active' => 'نشط', 'inactive' => 'موقوف', 'pending' => 'قيد المراجعة', 'rejected' => 'مرفوض']
            : ['active' => 'Active', 'inactive' => 'Suspended', 'pending' => 'Pending approval', 'rejected' => 'Rejected'];
        $payLabels = $ar
            ? ['late_both' => 'متأخر (اشتراك وقرض)', 'late_subscription' => 'متأخر (اشتراك شهري)', 'late_loan' => 'متأخر (قسط قرض)', 'no_shares' => 'بدون أسهم', 'up_to_date' => 'منتظم']
            : ['late_both' => 'Late (subscription & loan)', 'late_subscription' => 'Late (subscription)', 'late_loan' => 'Late (loan)', 'no_shares' => 'No shares', 'up_to_date' => 'Up to date'];
        $monthLabels = $ar
            ? ['paid' => 'مسدد', 'partial' => 'مسدد جزئياً', 'unpaid' => 'غير مسدد', 'late' => 'متأخر', 'no_shares' => 'بدون أسهم']
            : ['paid' => 'Paid', 'partial' => 'Partly paid', 'unpaid' => 'Unpaid', 'late' => 'Late', 'no_shares' => 'No shares'];

        // by registration number (a pending registration has none yet: last), then by name
        usort($members, function ($a, $b) {
            $sa = $a['serial_no'] === null ? PHP_INT_MAX : (int) $a['serial_no'];
            $sb = $b['serial_no'] === null ? PHP_INT_MAX : (int) $b['serial_no'];
            return $sa <=> $sb ?: strcmp((string) $a['name'], (string) $b['name']);
        });

        $columns = [
            [$ar ? 'الرقم التسلسلي' : 'Serial', 12, \App\Core\XlsxWriter::INT],
            ['User ID', 14, \App\Core\XlsxWriter::ID],
            [$ar ? 'اسم المشترك' : 'Member name', 30, \App\Core\XlsxWriter::TEXT],
            [$ar ? 'رقم الجوال' : 'Mobile', 17, \App\Core\XlsxWriter::ID],
            [$ar ? 'البريد الإلكتروني' : 'E-mail', 30, \App\Core\XlsxWriter::ID],
            [$ar ? 'رقم الهوية' : 'National ID', 16, \App\Core\XlsxWriter::ID],
            [$ar ? 'تاريخ الميلاد' : 'Birth date', 14, \App\Core\XlsxWriter::DATE],
            [$ar ? 'العنوان الوطني' : 'National address', 34, \App\Core\XlsxWriter::TEXT],
            [$ar ? 'اسم البنك' : 'Bank', 20, \App\Core\XlsxWriter::TEXT],
            [$ar ? 'رقم الآيبان (IBAN)' : 'IBAN', 28, \App\Core\XlsxWriter::ID],
            [$ar ? 'رقم الحساب البنكي' : 'Bank account', 22, \App\Core\XlsxWriter::ID],
            [$ar ? 'عدد الأسهم' : 'Shares', 11, \App\Core\XlsxWriter::INT],
            [$ar ? 'الاشتراك الشهري (ريال)' : 'Monthly subscription (SAR)', 16, \App\Core\XlsxWriter::MONEY],
            [$ar ? 'حالة الحساب' : 'Account status', 14, \App\Core\XlsxWriter::TEXT],
            [$ar ? 'حالة السداد' : 'Payment status', 22, \App\Core\XlsxWriter::TEXT],
            [$ar ? 'اشتراك الشهر الحالي' : 'This month', 16, \App\Core\XlsxWriter::TEXT],
            [$ar ? 'مبلغ التأسيس المطلوب' : 'Founding required', 16, \App\Core\XlsxWriter::MONEY],
            [$ar ? 'المسدد من التأسيس' : 'Founding paid', 16, \App\Core\XlsxWriter::MONEY],
            [$ar ? 'المتبقي من التأسيس' : 'Founding remaining', 16, \App\Core\XlsxWriter::MONEY],
            [$ar ? 'عدد القروض القائمة' : 'Open loans', 13, \App\Core\XlsxWriter::INT],
            [$ar ? 'المتبقي من القروض (ريال)' : 'Loans remaining (SAR)', 16, \App\Core\XlsxWriter::MONEY],
            [$ar ? 'تاريخ التسجيل' : 'Registered on', 14, \App\Core\XlsxWriter::DATE],
        ];

        $rows = [];
        $currentMonth = date('Y-m');
        foreach ($members as $m) {
            $id = (int) $m['id'];
            $shares = (int) $m['shares_count'];
            $f = $founding[$id] ?? null;
            $required = $f ? (float) $f['total_required'] : 0.0;
            $paid = $f ? (float) $f['amount_paid'] : 0.0;
            $monthStatus = MonthlySubscription::memberMonthStatus($m, $currentMonth);
            $rows[] = [
                $m['serial_no'] !== null ? (int) $m['serial_no'] : null,
                $m['uid'] ?? null,
                $m['name'],
                $m['mobile'],
                $m['email'],
                $m['national_id'],
                $m['birth_date'] ?: null,
                $m['national_address'] ?: null,
                $m['bank_name'] ?: null,
                $m['iban'] ?: null,
                $m['bank_account_number'] ?: null,
                $shares,
                round($shares * $shareValue, 2),
                $accountLabels[$m['status']] ?? $m['status'],
                $payLabels[$m['payment_status']] ?? $m['payment_status'],
                $monthLabels[$monthStatus] ?? $monthStatus,
                round($required, 2),
                round($paid, 2),
                round(max(0.0, $required - $paid), 2),
                (int) ($loans[$id]['c'] ?? 0),
                round((float) ($loans[$id]['r'] ?? 0), 2),
                $m['created_at'] ?: null,
            ];
        }

        $notes = [];
        if ($q !== '') {
            $notes[] = ($ar ? 'بحث: ' : 'Search: ') . $q;
        }
        if ($paymentStatus !== '') {
            $notes[] = ($ar ? 'حالة السداد: ' : 'Payment: ') . ($payLabels[$paymentStatus] ?? ($paymentStatus === 'late' ? ($ar ? 'متأخر' : 'Late') : $paymentStatus));
        }
        if ($accountStatus !== '') {
            $notes[] = ($ar ? 'حالة الحساب: ' : 'Account: ') . ($accountLabels[$accountStatus] ?? $accountStatus);
        }
        $titleLines = [
            ($ar ? 'قائمة المشتركين - ' : 'Members - ') . site_name(),
            ($ar ? 'تاريخ التقرير: ' : 'Report date: ') . date('d/m/Y H:i') . ' · ' . ($ar ? 'عدد المشتركين: ' : 'Members: ') . count($rows)
                . ($notes ? ' · ' . implode(' · ', $notes) : ''),
        ];
        $filename = 'members-' . date('Y-m-d');

        if (\App\Core\XlsxWriter::available() && (string) $this->input('format', '') !== 'csv') {
            $x = new \App\Core\XlsxWriter($ar ? 'المشتركون' : 'Members', $columns, $ar);
            $x->setTitleLines($titleLines);
            foreach ($rows as $r) {
                $x->addRow($r);
            }
            $x->download($filename . '.xlsx');
        }

        // CSV fallback: semicolon-separated (what Excel expects with an Arabic / European regional setting), UTF-8 with BOM
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename . '.csv');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, array_map('csv_cell', array_column($columns, 0)), ';');
        foreach ($rows as $r) {
            foreach ([6, 21] as $dateCol) {      // dates as dd/mm/yyyy
                if (!empty($r[$dateCol])) {
                    $r[$dateCol] = date('d/m/Y', strtotime(substr((string) $r[$dateCol], 0, 10)));
                }
            }
            fputcsv($out, array_map('csv_cell', $r), ';');
        }
        fclose($out);
        exit;
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
            'created_at',
        ]);

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Session::setOld($data);
            $this->redirect('admin/members/create');
        }

        $createdAt = !empty($data['created_at']) 
            ? date('Y-m-d H:i:s', strtotime($data['created_at'] . ' ' . date('H:i:s'))) 
            : date('Y-m-d H:i:s');

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
            'created_at' => $createdAt,
            'status' => 'active',
        ]);

        FoundingAmount::ensureForMember($id);
        \App\Models\ShareLot::syncToMemberTotal((int) $id, $createdAt); // shares given at creation bill through a lot

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
        $loans = Loan::forMember((int) $id);
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
            'created_at',
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
        if (!empty($data['created_at'])) {
            $existingTime = !empty($member['created_at']) ? date('H:i:s', strtotime($member['created_at'])) : '12:00:00';
            $newCreatedAt = date('Y-m-d H:i:s', strtotime($data['created_at'] . ' ' . $existingTime));
            $update['created_at'] = $newCreatedAt;

            // Also sync active share lots so subscription start date and 6-month rule reflect the new registration date
            $activeLots = \App\Models\ShareLot::activeFor((int) $id);
            if (count($activeLots) === 1) {
                \App\Models\ShareLot::update($activeLots[0]['id'], ['created_at' => $newCreatedAt]);
                \App\Models\MonthlySubscription::purgeStalePreStartRows((int) $id);
            }
        }
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

    /** Decide a self-registration: approve (the member can log in) or reject. A rejected one can still be approved later. */
    public function decideRegistration(string $id, string $decision): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $id);
        if (!$member || !in_array($member['status'], ['pending', 'rejected'], true)) {
            Session::flash('error', 'هذا الطلب غير موجود أو تمت معالجته بالفعل.');
            $this->redirect('admin/members');
        }

        $approve = $decision === 'approve';
        Member::update((int) $id, ['status' => $approve ? 'active' : 'rejected']);
        if ($approve) {
            // The registration becomes a member now: it gets the next serial number (kept forever, never reused).
            Member::ensureSerial((int) $id);
        }
        Session::flash('success', $approve ? 'تمت الموافقة على التسجيل ويمكن للمشترك الدخول الآن.' : 'تم رفض طلب التسجيل.');
        $this->redirect('admin/members');
    }

    public function toggleStatus(string $id): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $id);
        // A registration is decided with approve / reject, never flipped to active by the suspend toggle.
        if ($member && !in_array($member['status'], ['pending', 'rejected'], true)) {
            $newStatus = $member['status'] === 'active' ? 'inactive' : 'active';
            Member::update((int) $id, ['status' => $newStatus]);
            Session::flash('success', $newStatus === 'active' ? 'تم تفعيل حساب المشترك.' : 'تم إيقاف حساب المشترك.');
        }
        $this->redirect('admin/members');
    }

    /** Permanently deletes a member together with all of their records (cascade). Admin accounts and members with open loans are protected. */
    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        $member = Member::find((int) $id);
        if (!$member) {
            $this->redirect('admin/members');
        }

        if (Member::isAdmin($member)) {
            Session::flash('error', 'لا يمكن حذف حساب إداري.');
            $this->redirect('admin/members');
        }

        $openLoans = Loan::count(['member_id' => (int) $id, 'status' => 'active'])
            + Loan::count(['member_id' => (int) $id, 'status' => 'partial']);
        if ($openLoans > 0) {
            Session::flash('error', 'لا يمكن حذف المشترك لوجود قروض قائمة عليه. يمكنك إيقاف حسابه بدلاً من ذلك.');
            $this->redirect('admin/members');
        }

        // The database cascade removes his shares, subscriptions, founding payments, loans, requests, ledger entries and
        // notifications. Two kinds of rows only lose their link to him (ON DELETE SET NULL) and would stay behind as
        // orphans -- his support messages and the expenses recorded FOR him in the expenses log (which would still count in
        // its total) -- so they are removed with him, together with his verification codes.
        $pdo = \App\Core\Database::connection();
        $pdo->beginTransaction();
        try {
            \App\Models\ContactMessage::deleteWhere(['member_id' => (int) $id]);
            \App\Models\OtpCode::deleteWhere(['identifier' => (string) $member['mobile']]);
            try {
                \App\Models\Expense::deleteWhere(['member_id' => (int) $id]);
            } catch (\Throwable $e) {
                // the expenses table does not exist yet (its migration has not run): nothing to remove
            }
            Member::delete((int) $id);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        Session::flash('success', 'تم حذف المشترك "' . $member['name'] . '" وجميع بياناته.');
        $this->redirect('admin/members');
    }
}
