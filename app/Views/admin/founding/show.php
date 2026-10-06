<?php [$label, $variant] = status_badge($founding['status']); $remaining = round($founding['total_required'] - $founding['amount_paid'], 2); $payInFull = \App\Models\FoundingAmount::mustPayInFull($founding); $pct = $founding['total_required'] > 0 ? min(100, round($founding['amount_paid'] / $founding['total_required'] * 100)) : 0; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e($member['name']) ?></h5>
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
                <div class="mb-2">
                    <label class="form-label text-xs font-bold text-slate-700">تخصيص الدفعة لسهم محدد (اختياري)</label>
                    <select name="lot_id" class="form-select text-sm font-bold">
                        <option value="">عام / إجمالي مبلغ التأسيس</option>
                        <?php if (!empty($activeLots)): ?>
                            <?php foreach ($activeLots as $lIdx => $aLot): ?>
                                <option value="<?= $aLot['id'] ?>">السهم رقم <?= $lIdx + 1 ?> (<?= number_format($aLot['shares_count']) ?> سهم)</option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="text-muted small mt-1">تحديد السهم يوضح في المعاملات المالية والسجل أن السداد مخصص لهذا السهم (مثلاً 500 ريال للسهم رقم 1).</div>
                </div>
                <div class="mb-2">
                    <label class="form-label text-xs font-bold text-slate-700">المبلغ</label>
                    <input type="number" step="0.01" min="0.01" max="<?= $remaining ?>" name="amount" class="form-control font-numeric font-bold" value="<?= $remaining > 0 ? ($nextInstallment ? $nextInstallment['remaining'] : ($remaining >= 500 ? 500 : $remaining)) : '' ?>" required>
                    <?php if ($nextInstallment): ?>
                        <div class="text-muted small mt-1">القسط القادم رقم <?= (int) $nextInstallment['number'] ?> (<?= date_ar($nextInstallment['due_date']) ?>) بقيمة <?= money($nextInstallment['remaining']) ?></div>
                    <?php else: ?>
                        <div class="text-muted small mt-1">يمكنك إدخال أي مبلغ (مثل 500 ريال لتسديد سهم محدد) حتى المتبقي: <?= money($remaining) ?></div>
                    <?php endif; ?>
                </div>
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
        <thead><tr><th>المبلغ</th><th>تاريخ الدفع</th><th>ملاحظات</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr><td class="fw-bold"><?= money($p['amount']) ?></td><td><?= date_ar($p['payment_date']) ?></td><td><?= e($p['notes'] ?: '-') ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($payments)): ?>
            <tr><td colspan="3"><div class="empty-state"><i class="bi bi-receipt"></i>لا توجد دفعات مسجلة</div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
