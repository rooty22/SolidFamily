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
                <?php if (!empty($loan['lot_id'])): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-50 border border-purple-200/80 text-purple-700">
                        <i class="bi bi-pie-chart-fill text-purple-600"></i>
                        <span><?= is_rtl() ? 'حصة الأسهم المرتبطة:' : 'Linked Share Lot:' ?></span>
                        <strong class="font-numeric">#<?= (int) $loan['lot_id'] ?></strong>
                    </span>
                <?php endif; ?>
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
                    <th class="text-center"><?= is_rtl() ? 'الإجراءات' : 'Actions' ?></th>
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
                        <td class="text-center">
                            <?php if ((float) $i['amount_paid'] > 0): ?>
                                <div class="d-inline-flex align-items-center gap-1.5">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5 text-xs rounded-lg inline-flex items-center gap-1 font-bold" data-bs-toggle="modal" data-bs-target="#editLoanInstModal<?= $i['id'] ?>" title="<?= is_rtl() ? 'تعديل المبلغ المسدد' : 'Edit Paid Amount' ?>">
                                        <i class="bi bi-pencil-square"></i>
                                        <span><?= is_rtl() ? 'تعديل' : 'Edit' ?></span>
                                    </button>
                                    <form method="post" action="<?= url('admin/loans/' . $loan['id'] . '/installments/' . $i['id'] . '/reset-payment') ?>" onsubmit="return confirm('<?= is_rtl() ? 'هل أنت متأكد من إلغاء سداد القسط رقم ' . $i['installment_number'] . '؟ سيتم إعادة القسط إلى غير مسدد وتحديث رصيد القرض وحذف المعاملة المالية المرتبطة.' : 'Are you sure you want to cancel payment for installment #' . $i['installment_number'] . '?' ?>');" class="d-inline m-0">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2.5 text-xs rounded-lg inline-flex items-center gap-1 font-bold" title="<?= is_rtl() ? 'إلغاء السداد وحذف المعاملة' : 'Cancel Payment' ?>">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            <span><?= is_rtl() ? 'إلغاء السداد' : 'Reset' ?></span>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="text-slate-400 text-xs">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($installments as $i): if ((float) $i['amount_paid'] > 0): ?>
<div class="modal fade" id="editLoanInstModal<?= $i['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl border-0 shadow-2xl">
            <div class="modal-header border-b border-slate-100 bg-slate-50/70 p-4">
                <h5 class="modal-title text-sm font-black text-slate-800 flex items-center gap-2">
                    <i class="bi bi-pencil-square text-emerald-600"></i>
                    <span><?= is_rtl() ? "تعديل سداد القسط رقم {$i['installment_number']}" : "Edit Payment for Installment #{$i['installment_number']}" ?></span>
                </h5>
                <button type="button" class="btn-close text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= url('admin/loans/' . $loan['id'] . '/installments/' . $i['id'] . '/update-payment') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4 space-y-3">
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                        <span class="text-slate-600 font-bold"><?= is_rtl() ? 'قيمة القسط الكاملة:' : 'Installment Value:' ?></span>
                        <span class="font-numeric font-extrabold text-slate-900"><?= money($i['amount']) ?></span>
                    </div>
                    <div>
                        <label class="form-label text-xs font-bold text-slate-700 mb-1.5"><?= is_rtl() ? 'المبلغ المسدد الفعلي' : 'Actual Paid Amount' ?></label>
                        <input type="number" step="0.01" min="0" max="<?= (float) $i['amount'] ?>" name="amount_paid" class="form-control text-sm font-numeric font-bold" value="<?= (float) $i['amount_paid'] ?>" required>
                        <p class="text-[11px] text-muted mb-0 mt-1.5">
                            <?= is_rtl() ? 'إذا جعلت المبلغ 0، سيتم إلغاء السداد بالكامل وإعادة القسط إلى غير مسدد وتحديث رصيد القرض وحذف الحركة المالية.' : 'Setting the amount to 0 will reset the installment to unpaid, update the loan balance, and delete the financial transaction.' ?>
                        </p>
                    </div>
                </div>
                <div class="modal-footer border-t border-slate-100 p-3 bg-slate-50/50 flex justify-between">
                    <button type="button" class="btn btn-light text-xs font-bold px-3 py-2 rounded-xl" data-bs-dismiss="modal"><?= is_rtl() ? 'إلغاء' : 'Cancel' ?></button>
                    <button type="submit" class="btn btn-primary text-xs font-bold px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 border-0 shadow-md shadow-emerald-600/20">
                        <i class="bi bi-check-lg me-1"></i>
                        <?= is_rtl() ? 'حفظ التعديل' : 'Save Changes' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; endforeach; ?>

