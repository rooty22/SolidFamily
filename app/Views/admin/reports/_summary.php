<?php
/**
 * The totals box of a printed report (PDF): one big figure (the total of every amount) and a few smaller ones next to it.
 * Dark text on a light panel with a heavy border, so it stays readable even when the browser does not print backgrounds.
 *
 * @var array{label: string, value: string, note?: string, items?: array<int, array{0: string, 1: string}>}|null $summary
 */
if (empty($summary)) {
    return;
}
?>
<style>
    .report-summary { display: flex; flex-wrap: wrap; align-items: stretch; margin: 0 0 16px; border: 2px solid #1e3a5f; border-radius: 10px; overflow: hidden; background: #f1f5f9; page-break-inside: avoid; break-inside: avoid; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .report-summary .rs-main { flex: 1 1 280px; padding: 12px 22px; border-inline-start: 8px solid #1e3a5f; }
    .report-summary .rs-label { font-size: 13px; font-weight: 700; color: #475569; }
    .report-summary .rs-value { font-size: 28px; line-height: 1.25; font-weight: 800; color: #1e3a5f; margin: 2px 0; }
    .report-summary .rs-note { font-size: 12px; color: #64748b; }
    .report-summary .rs-item { flex: 0 1 auto; min-width: 170px; padding: 12px 20px; border-inline-start: 1px solid #94a3b8; display: flex; flex-direction: column; justify-content: center; }
    .report-summary .rs-item-label { font-size: 11px; font-weight: 700; color: #64748b; }
    .report-summary .rs-item-value { font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 2px; }
</style>
<div class="report-summary" data-testid="report-summary">
    <div class="rs-main">
        <div class="rs-label"><?= e($summary['label']) ?></div>
        <div class="rs-value" data-testid="report-total"><?= e($summary['value']) ?></div>
        <?php if (!empty($summary['note'])): ?><div class="rs-note"><?= e($summary['note']) ?></div><?php endif; ?>
    </div>
    <?php foreach (($summary['items'] ?? []) as [$itemLabel, $itemValue]): ?>
        <div class="rs-item">
            <div class="rs-item-label"><?= e($itemLabel) ?></div>
            <div class="rs-item-value"><?= e($itemValue) ?></div>
        </div>
    <?php endforeach; ?>
</div>
