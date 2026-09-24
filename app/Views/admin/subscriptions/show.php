<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e($member['name']) ?> <span class="text-muted" style="font-size:13px;">(<?= number_format($member['shares_count']) ?> سهم × <?= money($shareValue) ?>)</span></h5>
    <a href="<?= url('admin/members/' . $member['id']) ?>" class="btn btn-sm btn-soft">ملف المشترك</a>
</div>

<?php
// Which share lot a payment goes to: shown only when the member has several lots. Lots that are fully paid are left
// out (unless every lot is settled), and each option says which lot it is (#id) and what it still owes.
$lotPicker = function () use ($lots, $payableLots, $allSettled, $member): string {
    if (count($lots) < 2) {
        return '';
    }
    $label = fn($lot) => '#' . $lot['id'] . ' - ' . number_format($lot['shares_count']) . ' سهم - يوم ' . (int) \App\Models\MonthlySubscription::dueDayFor($member, $lot)
        . ' - ' . ($lot['outstanding'] > 0 ? 'متبقي ' . money($lot['outstanding']) : 'مسددة');
    if (count($payableLots) === 1) {
        $only = $payableLots[0];
        return '<input type="hidden" name="lot_id" value="' . (int) $only['id'] . '"><div class="mb-2"><label class="form-label">الدفعة (لوطة الأسهم)</label>'
            . '<div class="form-control bg-light">' . e($label($only)) . '</div></div>';
    }
    $html = '<div class="mb-2"><label class="form-label">الدفعة (لوطة الأسهم)</label><select name="lot_id" class="form-select" required>';
    foreach ($payableLots as $lot) {
        $html .= '<option value="' . (int) $lot['id'] . '">' . e($label($lot)) . '</option>';
    }
    $html .= '</select>' . ($allSettled ? '<div class="form-text">كل الدفعات مسددة حالياً، يمكنك السداد مقدماً.</div>' : '') . '</div>';
    return $html;
};
?>
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card-panel h-100">
            <div class="panel-head"><h3><i class="bi bi-calendar-plus"></i> تسجيل سداد لعدة أشهر</h3></div>
            <form method="post" action="<?= url('admin/subscriptions/' . $member['id'] . '/pay') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="bulk_pay">
                <?= $lotPicker() ?>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">الشهر الأول</label>
                        <input type="month" name="start_month" class="form-control" value="<?= date('Y-m') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">عدد الأشهر</label>
                        <input type="number" name="months_count" class="form-control" value="1" min="1" max="24">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-3"><i class="bi bi-check-circle"></i> تسجيل السداد بالكامل</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-panel h-100">
            <div class="panel-head"><h3><i class="bi bi-cash"></i> تسجيل دفعة جزئية لشهر واحد</h3></div>
            <form method="post" action="<?= url('admin/subscriptions/' . $member['id'] . '/pay') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="partial_pay">
                <?= $lotPicker() ?>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">الشهر</label>
                        <input type="month" name="partial_month" class="form-control" value="<?= date('Y-m') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">قيمة الدفعة</label>
                        <input type="number" step="0.01" min="0.01" name="partial_amount" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-soft w-100 mt-3"><i class="bi bi-cash-stack"></i> تسجيل الدفعة الجزئية</button>
            </form>
        </div>
    </div>
</div>

<div class="card-panel">
    <div class="panel-head"><h3><i class="bi bi-clock-history"></i> سجل الاشتراكات</h3></div>
    <table class="table-modern">
        <thead><tr><th>الشهر</th><th>الدفعة</th><th>موعد الاستحقاق</th><th>المستحق</th><th>المسدد</th><th>الحالة</th></tr></thead>
        <tbody>
        <?php foreach ($history as $h): [$l, $v] = status_badge($h['status']); ?>
            <tr>
                <td class="fw-bold"><?= e($h['month']) ?></td>
                <td class="text-muted" style="font-size:12.5px;"><?= $h['lot_id'] ? '#' . $h['lot_id'] . ' (' . number_format($h['shares_count_snapshot']) . ' سهم)' : '-' ?>
                    <?php if (!empty($h['lot_status']) && $h['lot_status'] !== 'active'): ?><small class="d-block text-danger">دفعة ملغاة</small><?php endif; ?></td>
                <td><?= date_ar($h['due_date']) ?>
                    <?php if (!empty($h['grace_until']) && $h['status'] !== 'paid' && $h['grace_until'] >= date('Y-m-d')): ?><small class="d-block text-muted">مهلة حتى <?= date_ar($h['grace_until']) ?></small><?php endif; ?></td>
                <td><?= money($h['amount_due']) ?></td>
                <td><?= money($h['amount_paid']) ?></td>
                <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($history)): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="bi bi-calendar-x"></i>لا يوجد سجل بعد</div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
