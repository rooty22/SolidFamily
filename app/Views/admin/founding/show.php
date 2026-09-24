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
            <div class="mt-3 small"><span class="text-muted">خطة السداد التي اختارها المشترك:</span>
                <b><?= empty($founding['plan_start']) ? 'لم يختر بعد' : ((int) $founding['plan_months'] === 1 ? 'مرة واحدة' : 'على ' . (int) $founding['plan_months'] . ' أشهر') ?></b></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-panel h-100">
            <div class="panel-head"><h3><i class="bi bi-plus-circle"></i> تسجيل دفعة</h3></div>
            <form method="post" action="<?= url('admin/founding/' . $member['id'] . '/pay') ?>">
                <?= csrf_field() ?>
                <div class="mb-2"><label class="form-label">المبلغ</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="<?= $remaining > 0 ? ($nextInstallment ? $nextInstallment['remaining'] : $remaining) : '' ?>" required<?= $payInFull && $remaining > 0 ? ' readonly' : '' ?>>
                    <?php if ($payInFull && $remaining > 0): ?><div class="text-muted small mt-1">لم يختر المشترك خطة تقسيط، فمبلغ التأسيس يُسدَّد دفعة واحدة بكامل المتبقي.</div>
                    <?php elseif ($nextInstallment): ?><div class="text-muted small mt-1">القسط القادم رقم <?= (int) $nextInstallment['number'] ?> (<?= date_ar($nextInstallment['due_date']) ?>)</div><?php endif; ?></div>
                <div class="mb-2"><label class="form-label">تاريخ الدفع</label><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required><div class="text-muted small mt-1">يوم استلام المبلغ (لا يمكن أن يكون في المستقبل). السداد يُحتسب على الأقساط بالترتيب.</div></div>
                <div class="mb-3"><label class="form-label">ملاحظات</label><input type="text" name="notes" class="form-control"></div>
                <button type="submit" class="btn btn-primary w-100">تسجيل الدفعة</button>
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
        <?php foreach ($schedule as $i): [$sl, $sv] = status_badge($i['late'] ? 'unpaid' : $i['status']); ?>
            <tr <?= $i['late'] ? 'style="background:#fef2f2;"' : '' ?>>
                <td class="fw-bold"><?= (int) $i['number'] ?></td>
                <td><?= date_ar($i['due_date']) ?></td>
                <td><?= money($i['amount']) ?></td>
                <td><?= money($i['paid']) ?></td>
                <td><span class="badge-status badge-<?= $sv ?>"><?= $i['late'] ? 'متأخر' : status_badge($i['status'])[0] ?></span></td>
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
