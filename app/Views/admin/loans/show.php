<?php 
[$label, $variant] = status_badge($loan['status']); 
$pct = $loan['amount'] > 0 ? min(100, round($loan['amount_paid'] / $loan['amount'] * 100)) : 0; 
$isEn = is_en();
?>

<!-- Loan Header & Quick Actions -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-3 mb-1">
            <h4 class="text-xl sm:text-2xl font-black text-slate-900 m-0"><?= e($member['name']) ?></h4>
            <span class="badge-status badge-<?= $variant ?>"><?= $label ?></span>
        </div>
        <div class="flex items-center gap-2 text-xs font-bold text-slate-500">
            <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-700 font-numeric"><?= __('loan_number', ['id' => $loan['id']]) ?></span>
            <span>•</span>
            <span class="text-slate-600"><?= $reasonLabels[$loan['reason']] ?? $loan['reason'] ?></span>
        </div>
    </div>
    
    <div class="flex items-center gap-2 shrink-0">
        <a href="<?= url('admin/loans/' . $loan['id'] . '/edit') ?>" class="btn-soft-primary">
            <i class="bi bi-pencil-square"></i>
            <span><?= __('edit') ?></span>
        </a>
        <?php if ((float) $loan['amount_remaining'] <= 0 && $loan['status'] !== 'closed'): ?>
            <form method="post" action="<?= url('admin/loans/' . $loan['id'] . '/close') ?>" class="m-0">
                <?= csrf_field() ?>
                <button type="submit" class="btn-soft-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?= __('close_loan') ?></span>
                </button>
            </form>
        <?php endif; ?>
        <?php if ((float) $loan['amount_paid'] <= 0): ?>
            <form method="post" action="<?= url('admin/loans/' . $loan['id'] . '/delete') ?>" data-confirm="<?= __('delete_loan_confirm') ?>" class="m-0">
                <?= csrf_field() ?>
                <button type="submit" class="btn-soft-danger">
                    <i class="bi bi-trash3-fill"></i>
                    <span><?= __('delete') ?></span>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Loan Summary Card -->
    <div class="col-lg-8">
        <div class="card-panel h-100 flex flex-col justify-between">
            <div>
                <div class="panel-head">
                    <h3 class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-sm shadow-xs">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                        <span><?= __('loan_summary') ?></span>
                    </h3>
                </div>

                <!-- 4 KPI Metric Tiles -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                    <!-- Total Value -->
                    <div class="metric-tile metric-blue">
                        <div class="metric-label"><?= __('value') ?></div>
                        <div class="metric-value font-numeric text-sky-950"><?= money($loan['amount']) ?></div>
                    </div>

                    <!-- Paid -->
                    <div class="metric-tile metric-green">
                        <div class="metric-label"><?= __('paid') ?></div>
                        <div class="metric-value font-numeric text-emerald-600"><?= money($loan['amount_paid']) ?></div>
                    </div>

                    <!-- Remaining -->
                    <div class="metric-tile metric-rose">
                        <div class="metric-label"><?= __('remaining') ?></div>
                        <div class="metric-value font-numeric text-rose-600"><?= money($loan['amount_remaining']) ?></div>
                    </div>

                    <!-- Admin Fee -->
                    <div class="metric-tile metric-slate">
                        <div class="metric-label"><?= __('admin_fee') ?></div>
                        <div class="metric-value font-numeric text-slate-800"><?= money($loan['admin_fee_amount']) ?></div>
                        <div class="text-[11px] font-bold text-slate-400 mt-0.5">(<?= $loan['admin_fee_percent'] ?>%)</div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-3">
                    <div class="flex items-center justify-between text-xs font-bold mb-2">
                        <span class="text-slate-600"><?= __('repayment_rate') ?></span>
                        <span class="font-numeric text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200"><?= $pct ?>%</span>
                    </div>
                    <div class="progress-modern">
                        <div style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Metadata Footer Tags -->
            <div class="flex flex-wrap items-center gap-3 pt-3 border-t border-slate-100 text-xs text-slate-500 font-semibold mt-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200/80">
                    <i class="bi bi-calendar-check text-sky-600"></i>
                    <span><?= __('loan_date') ?>:</span>
                    <strong class="font-numeric text-slate-700"><?= date_ar($loan['loan_date']) ?></strong>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200/80">
                    <i class="bi bi-layers-half text-emerald-600"></i>
                    <span><?= __('installments_count') ?>:</span>
                    <strong class="font-numeric text-slate-700"><?= $loan['installments_count'] ?></strong>
                </span>
            </div>
        </div>
    </div>

    <!-- Record Payment Card -->
    <div class="col-lg-4">
        <div class="card-panel h-100 flex flex-col justify-between">
            <div>
                <div class="panel-head">
                    <h3 class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm shadow-xs">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <span><?= __('record_installment_payment') ?></span>
                    </h3>
                </div>

                <?php $unpaidInstallments = array_filter($installments, fn($i) => $i['status'] !== 'paid'); ?>
                <?php if (empty($unpaidInstallments)): ?>
                    <div class="p-6 text-center rounded-2xl bg-emerald-50/60 border border-emerald-100 my-4">
                        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-2 text-xl">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <h6 class="font-bold text-emerald-900 mb-1"><?= __('all_installments_paid') ?></h6>
                        <p class="text-xs text-emerald-700 mb-0"><?= __('all_obligations_paid') ?></p>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= url('admin/loans/' . $loan['id'] . '/pay') ?>" class="space-y-4">
                        <?= csrf_field() ?>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700 mb-1.5"><?= __('installment') ?></label>
                            <select name="installment_id" class="form-select font-bold text-slate-800">
                                <?php foreach ($unpaidInstallments as $i): ?>
                                    <option value="<?= $i['id'] ?>">
                                        <?= __('installment') ?> #<?= $i['installment_number'] ?> - <?= __('remaining') ?> <?= money_plain($i['amount'] - $i['amount_paid']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-slate-700 mb-1.5"><?= __('amount') ?></label>
                            <div class="relative">
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control font-numeric font-bold" placeholder="0.00" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 shadow-md shadow-sky-600/20 font-bold">
                            <i class="bi bi-check2-circle text-lg"></i>
                            <span><?= __('record_payment') ?></span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Installments Schedule Table -->
<div class="card-panel">
    <div class="panel-head">
        <h3 class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-sm shadow-xs">
                <i class="bi bi-calendar-range-fill"></i>
            </div>
            <span><?= __('installments_schedule') ?></span>
        </h3>
    </div>

    <div class="table-panel border-0 shadow-none">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= __('due_date') ?></th>
                    <th><?= __('value') ?></th>
                    <th><?= __('paid') ?></th>
                    <th><?= __('remaining') ?></th>
                    <th><?= __('status') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($installments as $i): [$l, $v] = status_badge($i['status']); ?>
                    <tr>
                        <td class="font-numeric font-bold text-slate-900"><?= $i['installment_number'] ?></td>
                        <td class="font-numeric"><?= date_ar($i['due_date']) ?></td>
                        <td class="font-numeric font-bold"><?= money($i['amount']) ?></td>
                        <td class="font-numeric text-emerald-600 font-bold"><?= money($i['amount_paid']) ?></td>
                        <td class="font-numeric text-rose-600 font-bold"><?= money($i['amount'] - $i['amount_paid']) ?></td>
                        <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

