<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= __('loans_management_title') ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= __('loans_management_desc') ?></p>
    </div>
    <div class="flex items-center gap-3 flex-wrap sm:flex-nowrap">
        <form method="get" action="<?= url('admin/loans') ?>" class="flex items-center gap-2 flex-wrap">
            <div class="search-box" style="min-width:260px;">
                <i class="bi bi-search"></i>
                <input type="text" name="q" class="form-control" placeholder="<?= __('search_by_member_loan') ?>" value="<?= e($q) ?>">
            </div>
            <?php if (!empty($status)): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <select name="fee" class="form-select form-select-sm" style="width:auto;min-width:150px" onchange="this.form.submit()" title="<?= is_rtl() ? 'حالة العمولة' : 'Commission status' ?>">
                <option value=""><?= is_rtl() ? 'العمولة: الكل' : 'Commission: all' ?></option>
                <option value="paid" <?= ($fee ?? '') === 'paid' ? 'selected' : '' ?>><?= is_rtl() ? 'العمولة مدفوعة' : 'Commission paid' ?></option>
                <option value="unpaid" <?= ($fee ?? '') === 'unpaid' ? 'selected' : '' ?>><?= is_rtl() ? 'العمولة غير مدفوعة' : 'Commission unpaid' ?></option>
            </select>
        </form>
        <a href="<?= url('admin/loans/create') ?>" class="btn btn-primary inline-flex items-center gap-2 whitespace-nowrap shrink-0 font-bold">
            <i class="bi bi-plus-circle"></i>
            <span><?= __('add_loan') ?></span>
        </a>
    </div>
</div>

<!-- Totals of the loans that match the filters (all pages) -->
<div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-4">
    <div class="p-3 rounded-xl bg-white border border-slate-200/80">
        <div class="text-[11px] text-slate-500 font-bold"><?= is_rtl() ? 'عدد القروض' : 'Loans' ?></div>
        <div class="text-lg font-black font-numeric text-slate-900"><?= number_format($totals['count']) ?></div>
    </div>
    <div class="p-3 rounded-xl bg-white border border-slate-200/80">
        <div class="text-[11px] text-slate-500 font-bold"><?= is_rtl() ? 'إجمالي القروض المصروفة' : 'Total disbursed' ?></div>
        <div class="text-lg font-black font-numeric text-slate-900"><?= money($totals['amount']) ?></div>
    </div>
    <div class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-100">
        <div class="text-[11px] text-emerald-800 font-bold"><?= is_rtl() ? 'المسدد من الأقساط' : 'Repaid' ?></div>
        <div class="text-lg font-black font-numeric text-emerald-700"><?= money($totals['paid']) ?></div>
    </div>
    <div class="p-3 rounded-xl bg-rose-50/60 border border-rose-100">
        <div class="text-[11px] text-rose-800 font-bold"><?= is_rtl() ? 'المتبقي من الأقساط' : 'Remaining' ?></div>
        <div class="text-lg font-black font-numeric text-rose-700"><?= money($totals['remaining']) ?></div>
    </div>
    <div class="p-3 rounded-xl bg-purple-50/60 border border-purple-100">
        <div class="text-[11px] text-purple-800 font-bold"><?= is_rtl() ? 'العمولات (الإجمالي)' : 'Commissions (total)' ?></div>
        <div class="text-lg font-black font-numeric text-purple-800"><?= money($totals['fee_total']) ?></div>
    </div>
    <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-100">
        <div class="text-[11px] text-amber-800 font-bold"><?= is_rtl() ? 'العمولات مسددة / مستحقة' : 'Commissions received / due' ?></div>
        <div class="text-sm font-black font-numeric"><span class="text-emerald-700"><?= money($totals['fee_paid']) ?></span> <span class="text-slate-300">/</span> <span class="text-amber-700"><?= money($totals['fee_due']) ?></span></div>
    </div>
</div>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th><?= __('member') ?></th>
                <th><?= is_rtl() ? 'القرض' : 'Loan' ?></th>
                <th><?= __('date') ?></th>
                <th><?= __('installments') ?></th>
                <th style="min-width:170px"><?= is_rtl() ? 'السداد' : 'Repayment' ?></th>
                <th><?= is_rtl() ? 'العمولة' : 'Commission' ?></th>
                <th><?= __('status') ?></th>
                <th class="text-end"><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($loans as $l):
            [$lb, $v] = status_badge($l['display_status']);
            $pct = (float) $l['amount'] > 0 ? min(100, round($l['amount_paid'] / $l['amount'] * 100)) : 0;
            $feeState = \App\Models\Loan::feeState($l);
            $lotLabel = !empty($l['lot_id']) ? \App\Models\ShareLot::label((int) $l['member_id'], (int) $l['lot_id']) : '';
        ?>
            <tr>
                <td><?= member_cell((string) $l['member_name'], $l['member_serial'] ?? null, url('admin/members/' . $l['member_id'])) ?></td>
                <td>
                    <div class="cell-stack">
                        <b class="font-numeric"><?= money($l['amount']) ?></b>
                        <small><?= $lotLabel !== '' ? '<span class="text-purple-700">' . e($lotLabel) . '</span> · ' : '' ?><?= e($reasonLabels[$l['reason']] ?? $l['reason']) ?></small>
                    </div>
                </td>
                <td class="text-xs text-slate-500 font-numeric"><bdi dir="ltr"><?= date_ar($l['loan_date']) ?></bdi></td>
                <td>
                    <div class="cell-stack">
                        <b class="font-numeric"><?= (int) $l['installments_count'] ?> <?= is_rtl() ? 'قسط' : 'inst.' ?></b>
                        <small class="font-numeric"><?= money($l['installment_value']) ?></small>
                    </div>
                </td>
                <td>
                    <div class="cell-stack" style="gap:5px">
                        <div class="mini-bar<?= $l['display_status'] === 'late' ? ' is-late' : '' ?>" title="<?= $pct ?>%"><span style="width:<?= $pct ?>%"></span></div>
                        <small class="font-numeric"><span class="text-emerald-600"><?= money($l['amount_paid']) ?></span> <span class="text-slate-300">/</span> <span class="text-rose-500"><?= money($l['amount_remaining']) ?></span> <span class="text-slate-400">· <?= $pct ?>%</span></small>
                    </div>
                </td>
                <td>
                    <?php if ($feeState === 'none'): ?>
                        <span class="text-slate-400">-</span>
                    <?php else: ?>
                        <div class="cell-stack" style="gap:4px">
                            <span><b class="font-numeric"><?= money($l['admin_fee_amount']) ?></b> <small>(<?= rtrim(rtrim(number_format((float) $l['admin_fee_percent'], 2), '0'), '.') ?>%)</small></span>
                            <span>
                                <span class="badge-status badge-<?= $feeState === 'paid' ? 'success' : 'warning' ?>"><?= $feeState === 'paid' ? (is_rtl() ? 'مدفوعة' : 'Paid') : (is_rtl() ? 'غير مدفوعة' : 'Unpaid') ?></span>
                                <?php if ($feeState === 'paid' && !empty($l['fee_paid_at'])): ?><small class="font-numeric ms-1"><?= date_ar($l['fee_paid_at']) ?></small><?php endif; ?>
                            </span>
                            <?php if ($feeState === 'unpaid'): ?>
                                <!-- Quick record: the day the commission was really received -->
                                <button type="button" class="btn btn-xs btn-outline-success align-self-start py-0 px-2 text-[11px] font-bold" data-bs-toggle="collapse" data-bs-target="#feeQuick<?= (int) $l['id'] ?>">
                                    <i class="bi bi-cash-coin"></i> <?= is_rtl() ? 'تسجيل السداد' : 'Record payment' ?>
                                </button>
                                <div class="collapse" id="feeQuick<?= (int) $l['id'] ?>">
                                    <form method="post" action="<?= url('admin/loans/' . (int) $l['id'] . '/fee') ?>" class="d-flex align-items-center gap-1 flex-nowrap">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="fee_status" value="paid">
                                        <input type="hidden" name="redirect_to" value="<?= e('admin/loans' . ($_SERVER['QUERY_STRING'] ?? '' ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">
                                        <input type="date" name="fee_paid_at" class="form-control form-control-sm font-numeric" style="width:135px" value="<?= date('Y-m-d') ?>" min="<?= e($l['loan_date']) ?>" max="<?= date('Y-m-d') ?>" required>
                                        <button type="submit" class="btn btn-sm btn-success py-0 px-2 font-bold"><?= is_rtl() ? 'تأكيد' : 'OK' ?></button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td><span class="badge-status badge-<?= $v ?>"><?= $lb ?></span></td>
                <td class="text-end">
                    <div class="row-actions">
                        <a href="<?= url('admin/loans/' . $l['id']) ?>" class="row-btn row-btn-view" title="<?= __('view') ?>"><i class="bi bi-eye"></i></a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($loans)): ?>
            <tr>
                <td colspan="8" class="p-0">
                    <div class="empty-state">
                        <i class="bi bi-cash-coin text-3xl text-slate-400 mb-2"></i>
                        <span><?= __('no_loans_recorded') ?></span>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= pagination_links($pager ?? []) ?>
