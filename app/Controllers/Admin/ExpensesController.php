<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Expense;
use App\Models\Member;

/**
 * The administrative and bank expenses log. Main admin only: Admin::isSuperOnlyPath() keeps every /admin/expenses page
 * away from sub-admins. It is a plain record (name, cost, date): it does not move the ledger or any other total.
 */
class ExpensesController extends Controller
{
    public function index(): void
    {
        $q = trim((string) $this->input('q', ''));
        $type = (string) $this->input('type', '');
        $type = in_array($type, Expense::TYPES, true) ? $type : '';
        $all = Expense::search($q, $type);
        $pager = paginate($all);

        $this->view('admin/expenses/index', [
            'pageTitle' => is_rtl() ? 'سجل المصاريف' : 'Expenses log',
            'expenses' => $pager['items'],
            'pager' => $pager,
            'q' => $q,
            'type' => $type,
            'pickerMembers' => Expense::memberOptions(),
            'summary' => Expense::summary(),
            // what the current search / filter adds up to, with the signs (deposits +, withdrawals -)
            'foundNet' => round(array_sum(array_map(fn($e) => Expense::signed($e), $all)), 2),
        ], 'admin/layout');
    }

    public function export(): void
    {
        $q = trim((string) $this->input('q', ''));
        $type = (string) $this->input('type', '');
        $type = in_array($type, Expense::TYPES, true) ? $type : '';
        try {
            $all = Expense::search($q, $type);
            $summary = Expense::summary();
        } catch (\Throwable $e) {
            try {
                $all = Expense::raw('SELECT * FROM expenses ORDER BY expense_date DESC, id DESC');
                if ($q !== '') {
                    $all = array_values(array_filter($all, fn($r) => str_contains((string) ($r['name'] ?? ''), $q)));
                }
                if ($type === Expense::DEPOSIT) {
                    $all = [];
                }
                $w = round(array_sum(array_map(fn($r) => (float) ($r['amount'] ?? 0), $all)), 2);
                $summary = ['count' => count($all), 'total' => $w, 'withdrawals' => $w, 'deposits' => 0.0, 'net' => -$w, 'withdraw_count' => count($all), 'deposit_count' => 0];
            } catch (\Throwable $e2) {
                $all = [];
                $summary = ['count' => 0, 'total' => 0.0, 'withdrawals' => 0.0, 'deposits' => 0.0, 'net' => 0.0, 'withdraw_count' => 0, 'deposit_count' => 0];
            }
        }
        $ar = is_rtl();
        $X = \App\Core\XlsxReport::class;
        $report = new \App\Core\XlsxReport($ar);

        $notes = [];
        if ($q !== '') {
            $notes[] = ($ar ? 'بحث: ' : 'Search: ') . $q;
        }
        if ($type !== '') {
            $notes[] = ($ar ? 'النوع: ' : 'Type: ') . ($type === Expense::DEPOSIT ? ($ar ? 'إيداع' : 'Deposit') : ($ar ? 'صرف' : 'Withdrawal'));
        }
        $titleLines = [
            ($ar ? 'سجل المصاريف الإدارية والبنكية - ' : 'Administrative & Bank Expenses Log - ') . site_name(),
            ($ar ? 'تاريخ التقرير: ' : 'Report date: ') . date('d/m/Y H:i')
                . ' · ' . ($ar ? 'عدد السجلات: ' : 'Entries: ') . count($all)
                . ($notes ? ' · ' . implode(' · ', $notes) : ''),
        ];

        $report->sheet($ar ? 'سجل المصاريف' : 'Expenses Log', [
            [$ar ? 'التاريخ' : 'Date', 15, $X::DATE],
            [$ar ? 'اسم المعاملة' : 'Transaction name', 30, $X::TEXT],
            [$ar ? 'النوع' : 'Type', 14, $X::ID],
            [$ar ? 'المشترك' : 'Member', 26, $X::TEXT],
            [$ar ? 'الأثر على الرصيد (ريال)' : 'Signed amount (SAR)', 20, $X::MONEY],
            [$ar ? 'ملاحظات' : 'Notes', 40, $X::TEXT],
        ], $titleLines, true);

        foreach ($all as $x) {
            $isDeposit = ($x['type'] ?? Expense::WITHDRAW) === Expense::DEPOSIT;
            $memberLabel = (!empty($x['member_id']) && !empty($x['member_name']))
                ? ((!empty($x['member_serial']) ? '#' . (int) $x['member_serial'] . ' · ' : '') . $x['member_name'])
                : ($ar ? 'عام' : 'General');
            $report->row([
                $x['expense_date'],
                $x['name'],
                $isDeposit ? ($ar ? 'إيداع' : 'Deposit') : ($ar ? 'صرف' : 'Withdrawal'),
                $memberLabel,
                round(Expense::signed($x), 2),
                trim((string) ($x['notes'] ?? '')) ?: '-',
            ]);
        }

        if ($all) {
            $report->sumTotals($ar ? 'الصافي' : 'Net', [4], 1, 0, count($all) . ' ' . ($ar ? 'معاملة' : 'entries'));
        } else {
            $report->note($ar ? 'لا توجد معاملات مصاريف مطابقة' : 'No matching expense entries.');
        }

        $report->sheet($ar ? 'ملخص المصاريف' : 'Expenses Summary', [
            [$ar ? 'البند' : 'Item', 42, $X::TEXT],
            [$ar ? 'القيمة (ريال)' : 'Amount (SAR)', 20, $X::MONEY],
            [$ar ? 'العدد' : 'Count', 14, $X::INT],
        ], [
            ($ar ? 'ملخص المصاريف والإيداعات - ' : 'Expenses & Deposits Summary - ') . site_name(),
            $titleLines[1],
        ]);
        $report->section($ar ? 'إجمالي السجل الكلي' : 'Overall Log Totals');
        $report->row([$ar ? 'إجمالي المصاريف (صرف)' : 'Total withdrawals', $summary['withdrawals'], (int) $summary['withdraw_count']]);
        $report->row([$ar ? 'إجمالي الإيداعات' : 'Total deposits', $summary['deposits'], (int) $summary['deposit_count']]);
        $report->row([$ar ? 'صافي الأثر على رصيد الصندوق (إيداع − صرف)' : 'Net effect on balance', $summary['net'], (int) $summary['count']]);

        $report->download('expenses-' . date('Y-m-d') . '.xlsx');
    }

    public function store(): void
    {
        $this->verifyCsrf();
        [$data, $error] = $this->validated();
        if ($error !== null) {
            Session::flash('error', $error);
            $this->redirect('admin/expenses');
        }

        Expense::create($data + ['created_by' => current_admin_id()]);
        Session::flash('success', $data['type'] === Expense::DEPOSIT ? 'تمت إضافة الإيداع إلى السجل وأُضيف إلى الرصيد.' : 'تمت إضافة المصروف إلى السجل وخُصم من الرصيد.');
        $this->redirect('admin/expenses');
    }

    public function edit(string $id): void
    {
        $expense = Expense::find((int) $id);
        if (!$expense) {
            Session::flash('error', 'المصروف غير موجود.');
            $this->redirect('admin/expenses');
        }

        $this->view('admin/expenses/edit', [
            'pageTitle' => is_rtl() ? 'تعديل مصروف' : 'Edit expense',
            'expense' => $expense,
            'pickerMembers' => Expense::memberOptions(),
        ], 'admin/layout');
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $expense = Expense::find((int) $id);
        if (!$expense) {
            Session::flash('error', 'المصروف غير موجود.');
            $this->redirect('admin/expenses');
        }

        [$data, $error] = $this->validated();
        if ($error !== null) {
            Session::flash('error', $error);
            $this->redirect('admin/expenses/' . (int) $id . '/edit');
        }

        Expense::update((int) $id, $data);
        Session::flash('success', 'تم تحديث المصروف.');
        $this->redirect('admin/expenses');
    }

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        $expense = Expense::find((int) $id);
        if (!$expense) {
            Session::flash('error', 'المصروف غير موجود.');
            $this->redirect('admin/expenses');
        }

        Expense::delete((int) $id);
        Session::flash('success', 'تم حذف ' . (($expense['type'] ?? '') === Expense::DEPOSIT ? 'الإيداع' : 'المصروف') . ' "' . $expense['name'] . '" من السجل.');
        $this->redirect('admin/expenses');
    }

    /**
     * The posted name / amount / kind of transaction (withdraw by default) / date / member / notes, checked. The date is optional (today); it may be in the past or up to
     * a year ahead. The member and the notes (up to 1000 characters) are optional too.
     *
     * @return array{0: ?array, 1: ?string} [row data, null] or [null, the error to show]
     */
    private function validated(): array
    {
        $in = [
            'name' => trim((string) $this->input('name', '')),
            'amount' => (string) $this->input('amount', ''),
            'expense_date' => trim((string) $this->input('expense_date', '')),
            'type' => trim((string) $this->input('type', Expense::WITHDRAW)),
            'notes' => str_replace([chr(13) . chr(10), chr(13)], chr(10), (string) $this->input('notes', '')), // a browser posts line breaks as CRLF: one character each
        ];
        if ($in['expense_date'] === '') {
            $in['expense_date'] = date('Y-m-d');
        }
        if ($in['type'] === '') {
            $in['type'] = Expense::WITHDRAW; // an old form / a request without the field: an expense, as it always was
        }

        $validator = Validator::make($in)
            ->required('name', 'اسم المصروف')->max('name', 150, 'اسم المصروف')
            ->required('amount', 'تكلفة المصروف')->decimal('amount', 'تكلفة المصروف', 0.01, 1000000000)
            ->date('expense_date', 'المصروف', 366)
            ->in('type', Expense::TYPES, 'نوع المعاملة')
            ->max('notes', 1000, 'الملاحظات');
        if ($validator->fails()) {
            return [null, $validator->firstError()];
        }

        // The member is optional: empty = a general expense of the fund; otherwise it must be a registered member.
        $memberId = null;
        $memberRaw = trim((string) $this->input('member_id', ''));
        if ($memberRaw !== '') {
            $member = ctype_digit($memberRaw) ? Member::find((int) $memberRaw) : null;
            if (!$member || in_array($member['status'], ['pending', 'rejected'], true)) {
                return [null, 'المشترك المحدد غير موجود.'];
            }
            $memberId = (int) $member['id'];
        }

        $date = null;
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $fmt) {
            $parsed = \DateTime::createFromFormat('!' . $fmt, $in['expense_date']);
            if ($parsed && $parsed->format($fmt) === $in['expense_date']) {
                $date = $parsed->format('Y-m-d');
                break;
            }
        }

        return [['name' => $in['name'], 'amount' => round((float) $in['amount'], 2), 'type' => $in['type'], 'expense_date' => $date ?? date('Y-m-d'), 'member_id' => $memberId, 'notes' => $in['notes'] !== '' ? $in['notes'] : null], null];
    }
}
