<?php [$label, $variant] = status_badge($founding['status']); $remaining = round($founding['total_required'] - $founding['amount_paid'], 2); $payInFull = \App\Models\FoundingAmount::mustPayInFull($founding); $pct = $founding['total_required'] > 0 ? min(100, round($founding['amount_paid'] / $founding['total_required'] * 100)) : 0; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= member_serial_chip($member) ?> <?= e($member['name']) ?></h5>
    <a href="<?= url('admin/members/' . $member['id']) ?>" class="btn btn-sm btn-soft">ملف المشترك</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card-panel h-100">
            <div class="panel-head"><h3>ملخص مبلغ التأسيس</h3><span class="badge-status badge-<?= $variant ?>"><?= $label ?></span></div>
            <div class="row g-3 mb-3">
                <div class="col-4"><div class="text-muted small">المطلوب</div><div class="fw-bold fs-6"><?= money($founding['total_required']) ?></div></div>
                <div class="col-4"><div class="text-muted small">المسدد</div><div class="fw-bold fs-6 text-success"><?= money($founding['amount_paid']) ?></div></div>
                <div class="col-4"><div class="text-muted small">المتبقي</div><div class="fw-bold fs-6 text-danger"><?= money($remaining) ?></div></div>
            </div>
            <div class="progress-modern"><div style="width:<?= $pct ?>%"></div></div>
            <div class="text-muted small mt-2"><?= $pct ?>% مكتمل</div>
            <div class="mt-3 pt-3 border-top">
                <span class="text-muted small d-block mb-1">خطة السداد الحالية:</span>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span class="badge bg-slate-100 text-slate-800 border px-2.5 py-1.5 text-xs font-bold">
                        <i class="bi bi-calendar3 me-1"></i>
                        <?= empty($founding['plan_start']) ? 'لم يختر المشترك بعد' : ((int) $founding['plan_months'] === 1 ? 'دفعة واحدة (كامل المبلغ)' : 'تقسيط على ' . (int) $founding['plan_months'] . ' أشهر') ?>
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#editPlanCollapse">
                        <i class="bi bi-pencil-square me-1"></i> <?= empty($founding['plan_start']) ? 'تحديد خطة السداد' : 'تعديل الخطة' ?>
                    </button>
                </div>
                <div class="collapse mt-2" id="editPlanCollapse">
                    <form method="post" action="<?= url('admin/founding/' . $member['id'] . '/plan') ?>" class="p-3 bg-slate-50 rounded-xl border">
                        <?= csrf_field() ?>
                        <label class="form-label text-xs font-bold text-slate-700 mb-1">اختر خطة السداد للمشترك:</label>
                        <div class="d-flex gap-2">
                            <select name="plan_months" class="form-select form-select-sm">
                                <option value="1" <?= (int) ($founding['plan_months'] ?? 1) === 1 ? 'selected' : '' ?>>دفعة واحدة (كامل المبلغ)</option>
                                <option value="2" <?= (int) ($founding['plan_months'] ?? 0) === 2 ? 'selected' : '' ?>>تقسيط على شهرين</option>
                                <option value="3" <?= (int) ($founding['plan_months'] ?? 0) === 3 ? 'selected' : '' ?>>تقسيط على 3 أشهر</option>
                                <option value="4" <?= (int) ($founding['plan_months'] ?? 0) === 4 ? 'selected' : '' ?>>تقسيط على 4 أشهر</option>
                                <option value="5" <?= (int) ($founding['plan_months'] ?? 0) === 5 ? 'selected' : '' ?>>تقسيط على 5 أشهر</option>
                                <option value="6" <?= (int) ($founding['plan_months'] ?? 0) === 6 ? 'selected' : '' ?>>تقسيط على 6 أشهر</option>
                                <option value="10" <?= (int) ($founding['plan_months'] ?? 0) === 10 ? 'selected' : '' ?>>تقسيط على 10 أشهر</option>
                                <option value="12" <?= (int) ($founding['plan_months'] ?? 0) === 12 ? 'selected' : '' ?>>تقسيط على 12 شهراً</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary shrink-0">حفظ الخطة</button>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1 mb-0">يمكن للمشترك أيضاً اختيار خطته من حسابه، أو يستطيع المسؤول تعيينها هنا.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-panel h-100">
            <div class="panel-head"><h3><i class="bi bi-plus-circle"></i> تسجيل دفعة</h3></div>
            <form method="post" action="<?= url('admin/founding/' . $member['id'] . '/pay') ?>">
                <?= csrf_field() ?>
                <?php
                // The first share that still owes something is pre-selected; its remaining amount is the default payment.
                $firstOpen = null;
                foreach ($breakdown as $b) {
                    if ($b['remaining'] > 0) { $firstOpen = $b; break; }
                }
                ?>
                <div class="mb-2">
                    <label class="form-label text-xs font-bold text-slate-700">السهم المسدَّد عنه <?= !empty($activeLots) ? '<span class="text-rose-500">*</span>' : '' ?></label>
                    <select name="lot_id" id="foundingLotSelect" class="form-select text-sm font-bold" <?= !empty($activeLots) ? 'required' : '' ?>>
                        <?php if (empty($activeLots)): ?>
                            <option value="">لا توجد أسهم نشطة</option>
                        <?php else: ?>
                            <option value="" data-remaining="0" disabled <?= $firstOpen ? '' : 'selected' ?>>— اختر السهم —</option>
                            <?php foreach ($breakdown as $b): ?>
                                <option value="<?= $b['lot_id'] ?>" data-remaining="<?= $b['remaining'] ?>" <?= $b['remaining'] <= 0 ? 'disabled' : '' ?> <?= $firstOpen && $firstOpen['lot_id'] === $b['lot_id'] ? 'selected' : '' ?>>
                                    السهم رقم <?= $b['number'] ?> (<?= number_format($b['shares_count']) ?> سهم) — <?= $b['remaining'] <= 0 ? 'مسدد' : 'المتبقي ' . money($b['remaining']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="text-muted small mt-1">تُسجَّل الدفعة على السهم المحدد فقط ولا تُوزَّع على بقية أسهم المشترك (مثلاً 500 ريال للسهم رقم 1 يبقي الأسهم الأخرى غير مسددة).</div>
                </div>
                <div class="mb-2">
                    <label class="form-label text-xs font-bold text-slate-700">المبلغ</label>
                    <input type="number" step="0.01" min="0.01" max="<?= $firstOpen ? min($remaining, $firstOpen['remaining']) : $remaining ?>" name="amount" id="foundingAmountInput" class="form-control font-numeric font-bold" value="<?= $remaining > 0 ? ($firstOpen ? min($remaining, $firstOpen['remaining']) : ($nextInstallment ? $nextInstallment['remaining'] : $remaining)) : '' ?>" required>
                    <?php if ($nextInstallment): ?>
                        <div class="text-muted small mt-1">القسط القادم من خطة السداد رقم <?= (int) $nextInstallment['number'] ?> (<?= date_ar($nextInstallment['due_date']) ?>) بقيمة <?= money($nextInstallment['remaining']) ?></div>
                    <?php else: ?>
                        <div class="text-muted small mt-1">حتى المتبقي على السهم المحدد، وإجمالي المتبقي على المشترك: <?= money($remaining) ?></div>
                    <?php endif; ?>
                </div>
                <script>
                    (function () {
                        var sel = document.getElementById('foundingLotSelect'), amt = document.getElementById('foundingAmountInput');
                        if (!sel || !amt) { return; }
                        var total = <?= json_encode((float) $remaining) ?>;
                        sel.addEventListener('change', function () {
                            var rem = parseFloat(sel.options[sel.selectedIndex].dataset.remaining || '0');
                            if (rem > 0) { var v = Math.min(rem, total); amt.max = v; amt.value = v; }
                        });
                    })();
                </script>
                <div class="mb-2">
                    <label class="form-label text-xs font-bold text-slate-700">تاريخ الدفع</label>
                    <input type="date" name="payment_date" class="form-control font-numeric" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                    <div class="text-muted small mt-1">يوم استلام المبلغ (لا يمكن أن يكون في المستقبل).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-xs font-bold text-slate-700">ملاحظات</label>
                    <input type="text" name="notes" class="form-control text-sm" placeholder="اختياري (سداد دفعة أولى، كاش، تحويل...)">
                </div>
                <button type="submit" class="btn btn-primary w-100 font-bold py-2.5">
                    <i class="bi bi-check2-circle me-1"></i> تسجيل الدفعة
                </button>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($breakdown)): ?>
<div class="card-panel mb-3">
    <div class="panel-head"><h3><i class="bi bi-pie-chart-fill"></i> مبلغ التأسيس لكل سهم</h3><span class="text-muted small">كل سهم يُحاسَب بمفرده</span></div>
    <table class="table-modern">
        <thead><tr><th>السهم</th><th>المطلوب</th><th>المسدد</th><th>المتبقي</th><th>الحالة</th></tr></thead>
        <tbody>
        <?php foreach ($breakdown as $b): [$bl, $bv] = status_badge($b['status']); ?>
            <tr>
                <td class="fw-bold">السهم رقم <?= $b['number'] ?> <span class="text-muted small fw-normal">(<?= number_format($b['shares_count']) ?> سهم)</span></td>
                <td class="font-numeric"><?= money($b['required']) ?></td>
                <td class="font-numeric text-success fw-bold"><?= money($b['paid']) ?></td>
                <td class="font-numeric text-danger fw-bold"><?= money($b['remaining']) ?></td>
                <td><span class="badge-status badge-<?= $bv ?>"><?= $bl ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($unallocated)): ?>
<div class="card-panel mb-3" style="border:1.5px solid #fde68a;background:#fffbeb;">
    <div class="panel-head"><h3 class="text-amber-800"><i class="bi bi-exclamation-triangle-fill"></i> مبالغ مسددة غير مخصصة لسهم</h3><span class="text-muted small"><?= money(array_sum(array_column($unallocated, 'unallocated'))) ?></span></div>
    <p class="text-sm text-slate-700 mb-2">هذه المبالغ سُجلت قبل ربط مبلغ التأسيس بكل سهم على حدة، ولا تُحتسب لأي سهم حتى تخصصها لسهم محدد (لن يُوزَّع منها شيء تلقائياً على بقية الأسهم).</p>
    <table class="table-modern">
        <thead><tr><th>تاريخ الدفعة</th><th>غير المخصص</th><th>تخصيص لسهم</th></tr></thead>
        <tbody>
        <?php foreach ($unallocated as $u): ?>
            <tr>
                <td class="font-numeric"><?= date_ar($u['payment_date']) ?><?= !empty($u['notes']) ? ' <span class="text-muted small">— ' . e(localize_dates($u['notes'])) . '</span>' : '' ?></td>
                <td class="font-numeric fw-bold"><?= money($u['unallocated']) ?></td>
                <td>
                    <form method="post" action="<?= url('admin/founding/' . $member['id'] . '/allocate') ?>" class="d-flex gap-2 align-items-center flex-wrap">
                        <?= csrf_field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int) $u['id'] ?>">
                        <select name="lot_id" class="form-select form-select-sm" style="max-width:240px" required>
                            <?php foreach ($breakdown as $b): if ($b['remaining'] <= 0) { continue; } ?>
                                <option value="<?= $b['lot_id'] ?>">السهم رقم <?= $b['number'] ?> — المتبقي <?= money($b['remaining']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" step="0.01" min="0.01" max="<?= $u['unallocated'] ?>" name="amount" value="<?= $u['unallocated'] ?>" class="form-control form-control-sm font-numeric" style="max-width:130px" required>
                        <button type="submit" class="btn btn-sm btn-primary">تخصيص</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($schedule)): ?>
<div class="card-panel mb-3">
    <div class="panel-head"><h3><i class="bi bi-list-ol"></i> جدول أقساط التأسيس</h3><span class="text-muted small"><?= count($schedule) ?></span></div>
    <table class="table-modern">
        <thead><tr><th>#</th><th>الاستحقاق</th><th>القيمة</th><th>المسدد</th><th>الحالة</th></tr></thead>
        <tbody>
        <?php foreach ($schedule as $i): [$sl, $sv] = status_badge($i['status']); ?>
            <tr>
                <td class="fw-bold"><?= (int) $i['number'] ?></td>
                <td><?= date_ar($i['due_date']) ?></td>
                <td><?= money($i['amount']) ?></td>
                <td><?= money($i['paid']) ?></td>
                <td><span class="badge-status badge-<?= $sv ?>"><?= $sl ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card-panel">
    <div class="panel-head"><h3><i class="bi bi-receipt"></i> سجل الدفعات</h3></div>
    <table class="table-modern">
        <thead><tr><th>المبلغ</th><th>السهم المسدَّد عنه</th><th>تاريخ الدفع</th><th>ملاحظات</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): $allocs = $allocByPayment[(int) $p['id']] ?? []; $free = round((float) $p['amount'] - array_sum(array_column($allocs, 'amount')), 2); ?>
            <tr>
                <td class="fw-bold"><?= money($p['amount']) ?></td>
                <td>
                    <?php foreach ($allocs as $a): ?>
                        <form method="post" action="<?= url('admin/founding/' . $member['id'] . '/allocation/' . (int) $a['id'] . '/delete') ?>" class="d-inline-flex align-items-center gap-1 me-1" data-confirm="إلغاء تخصيص هذا المبلغ من السهم؟ سيعود إلى (غير مخصص) ويمكنك تخصيصه لسهم آخر.">
                            <?= csrf_field() ?>
                            <span class="badge-status badge-info">السهم رقم <?= (int) ($lotNumbers[(int) $a['lot_id']] ?? 0) ?> · <?= money($a['amount']) ?></span>
                            <button type="submit" class="btn btn-sm p-0 border-0 text-slate-400" title="إلغاء التخصيص"><i class="bi bi-x-circle"></i></button>
                        </form>
                    <?php endforeach; ?>
                    <?php if ($free > 0): ?><span class="badge-status badge-warning">غير مخصص · <?= money($free) ?></span><?php endif; ?>
                </td>
                <td><?= date_ar($p['payment_date']) ?></td>
                <td><?= e(localize_dates($p['notes'] ?: '-')) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($payments)): ?>
            <tr><td colspan="4"><div class="empty-state"><i class="bi bi-receipt"></i>لا توجد دفعات مسجلة</div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
