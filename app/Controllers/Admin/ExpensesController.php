<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Expense;

/**
 * The administrative and bank expenses log. Main admin only: Admin::isSuperOnlyPath() keeps every /admin/expenses page
 * away from sub-admins. It is a plain record (name, cost, date): it does not move the ledger or any other total.
 */
class ExpensesController extends Controller
{
    public function index(): void
    {
        $q = trim((string) $this->input('q', ''));
        $all = Expense::search($q);
        $pager = paginate($all);

        $this->view('admin/expenses/index', [
            'pageTitle' => is_rtl() ? 'سجل المصاريف' : 'Expenses log',
            'expenses' => $pager['items'],
            'pager' => $pager,
            'q' => $q,
            'summary' => Expense::summary(),
            // what the current search adds up to (the same as the summary when nothing is searched)
            'foundTotal' => round(array_sum(array_map(fn($e) => (float) $e['amount'], $all)), 2),
        ], 'admin/layout');
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
        Session::flash('success', 'تمت إضافة المصروف إلى السجل.');
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
        Session::flash('success', 'تم حذف المصروف "' . $expense['name'] . '" من السجل.');
        $this->redirect('admin/expenses');
    }

    /**
     * The posted name / cost / date, checked. The date is optional (today); it may be in the past or up to a year ahead.
     *
     * @return array{0: ?array, 1: ?string} [row data, null] or [null, the error to show]
     */
    private function validated(): array
    {
        $in = [
            'name' => trim((string) $this->input('name', '')),
            'amount' => (string) $this->input('amount', ''),
            'expense_date' => trim((string) $this->input('expense_date', '')),
        ];
        if ($in['expense_date'] === '') {
            $in['expense_date'] = date('Y-m-d');
        }

        $validator = Validator::make($in)
            ->required('name', 'اسم المصروف')->max('name', 150, 'اسم المصروف')
            ->required('amount', 'تكلفة المصروف')->decimal('amount', 'تكلفة المصروف', 0.01, 1000000000)
            ->date('expense_date', 'المصروف', 366);
        if ($validator->fails()) {
            return [null, $validator->firstError()];
        }

        $date = null;
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $fmt) {
            $parsed = \DateTime::createFromFormat('!' . $fmt, $in['expense_date']);
            if ($parsed && $parsed->format($fmt) === $in['expense_date']) {
                $date = $parsed->format('Y-m-d');
                break;
            }
        }

        return [['name' => $in['name'], 'amount' => round((float) $in['amount'], 2), 'expense_date' => $date ?? date('Y-m-d')], null];
    }
}
