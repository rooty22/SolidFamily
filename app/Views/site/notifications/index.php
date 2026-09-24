<?php
// Group + look of each notification, derived from what it says (alerts, payments, admin messages, requests).
$meta = function (array $n): array {
    $t = (string) $n['title'];
    if (str_contains($t, 'تنبيه') || str_contains($t, 'تأخر') || stripos($t, 'alert') !== false || stripos($t, 'overdue') !== false) {
        return ['bi-exclamation-triangle-fill', 'n-danger', 'alerts'];
    }
    if (str_contains($t, 'تذكير') || stripos($t, 'reminder') !== false) {
        return ['bi-alarm-fill', 'n-warning', 'alerts'];
    }
    if (str_contains($t, 'رد الإدارة') || stripos($t, 'admin reply') !== false || stripos($t, 'management') !== false) {
        return ['bi-chat-dots-fill', 'n-brand', 'admin'];
    }
    if (str_contains($t, 'سداد') || stripos($t, 'payment') !== false || stripos($t, 'paid') !== false) {
        return ['bi-check-circle-fill', 'n-success', 'payments'];
    }
    if (($n['category'] ?? '') === 'manual') {
        return ['bi-megaphone-fill', 'n-brand', 'admin'];
    }
    return ['bi-info-circle-fill', 'n-info', 'requests'];
};
$groups = [
    'all' => __('all'),
    'alerts' => __('reminders_and_alerts'),
    'payments' => __('payments'),
    'requests' => __('requests_and_loans'),
    'admin' => __('from_management'),
];
$counts = array_fill_keys(array_keys($groups), 0);
foreach ($notifications as $n) {
    $counts['all']++;
    $counts[$meta($n)[2]]++;
}
$daysLeft = fn(string $date): int => (int) floor((strtotime($date) - strtotime(date('Y-m-d'))) / 86400);
?>
<div class="page-head">
    <div>
        <p><?= __('notifications_subtitle') ?></p>
    </div>
    <?php if ($counts['all'] > 0): ?>
    <div class="chip-row" id="notifFilters">
        <?php foreach ($groups as $key => $label): if ($key !== 'all' && $counts[$key] === 0) { continue; } ?>
            <button type="button" class="chip <?= $key === 'all' ? 'active' : '' ?>" data-group="<?= $key ?>"><?= $label ?><span class="count font-num"><?= $counts[$key] ?></span></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="split">
    <div class="card-panel">
        <div class="panel-head"><h3><i class="bi bi-bell-fill"></i> <?= __('your_notifications') ?></h3><span class="text-muted small font-num"><?= $counts['all'] ?></span></div>
        <?php if (empty($notifications)): ?>
            <div class="empty-state"><i class="bi bi-bell-slash"></i><?= __('no_notifications_currently') ?><br><small class="text-muted"><?= __('no_notifications_hint') ?></small></div>
        <?php else: ?>
        <div class="notif-list" id="notifList">
            <?php foreach ($notifications as $n): [$icon, $tone, $group] = $meta($n); ?>
            <div class="notif-item" data-group="<?= $group ?>">
                <div class="n-icon <?= $tone ?>"><i class="bi <?= $icon ?>"></i></div>
                <div class="flex-grow-1">
                    <div class="n-title"><?= e($n['title']) ?></div>
                    <div class="n-body"><?= nl2br(e($n['body'])) ?></div>
                    <div class="n-time font-num"><i class="bi bi-clock"></i> <?= date_ar($n['created_at'], 'Y-m-d H:i') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="stack">
        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-calendar-event"></i> <?= __('upcoming_dues') ?></h3></div>
            <?php if (!$subscription && !$nextInstallment): ?>
                <div class="note-box"><i class="bi bi-check-circle-fill"></i> <?= __('no_upcoming_dues') ?></div>
            <?php else: ?>
            <div class="info-list">
                <?php if ($subscription): [$sl, $sv] = status_badge($subscription['status']); $left = $daysLeft(\App\Models\MonthlySubscription::effectiveDue($subscription)); ?>
                <div class="info-row"><span><?= __('subscription_month', ['month' => e($subscription['month'])]) ?></span><b><span class="badge-status badge-<?= $sv ?>"><?= $sl ?></span></b></div>
                <div class="info-row"><span><?= __('remaining_amount') ?></span><b class="font-num"><?= money(max(0, $subscription['amount_due'] - $subscription['amount_paid'])) ?></b></div>
                <div class="info-row"><span><?= __('due_date') ?></span><b class="font-num"><?= date_ar($subscription['due_date']) ?>
                    <?php if ($subscription['status'] !== 'paid'): ?><small class="<?= $left < 0 ? 'text-danger' : 'text-muted' ?>"> (<?= relative_days_label($left) ?>)</small><?php endif; ?></b></div>
                <?php endif; ?>
                <?php if ($nextInstallment): $left = $daysLeft($nextInstallment['due_date']); ?>
                <div class="info-row"><span><?= __('next_installment_for_loan', ['num' => (int) $nextInstallment['loan_number']]) ?></span><b class="font-num"><?= money($nextInstallment['amount'] - $nextInstallment['amount_paid']) ?></b></div>
                <div class="info-row"><span><?= __('due_date') ?></span><b class="font-num"><?= date_ar($nextInstallment['due_date']) ?>
                    <small class="<?= $left < 0 ? 'text-danger' : 'text-muted' ?>"> (<?= relative_days_label($left) ?>)</small></b></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-lightning-charge-fill"></i> <?= __('quick_actions') ?></h3></div>
            <div class="d-grid gap-2">
                <a href="<?= url('shares') ?>" class="btn btn-soft"><i class="bi bi-pie-chart-fill"></i> <?= __('shares_and_subscriptions') ?></a>
                <a href="<?= url('loans') ?>" class="btn btn-soft"><i class="bi bi-cash-coin"></i> <?= __('my_loans_and_requests') ?></a>
                <a href="<?= url('settings') ?>" class="btn btn-soft"><i class="bi bi-headset"></i> <?= __('contact_management') ?></a>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var bar = document.getElementById('notifFilters');
    if (!bar) { return; }
    bar.addEventListener('click', function (e) {
        var chip = e.target.closest('.chip');
        if (!chip) { return; }
        var group = chip.getAttribute('data-group');
        bar.querySelectorAll('.chip').forEach(function (c) { c.classList.toggle('active', c === chip); });
        document.querySelectorAll('#notifList .notif-item').forEach(function (item) {
            item.style.display = (group === 'all' || item.getAttribute('data-group') === group) ? '' : 'none';
        });
    });
})();
</script>
