<?php
/**
 * Standalone printable report (no layout). The browser's print dialog opens automatically:
 * choose "Save as PDF" as the destination to get the PDF.
 *
 * @var string $title
 * @var array<int,string> $headers
 * @var array<int,array<int,mixed>> $rows
 */
$dir = is_rtl() ? 'rtl' : 'ltr';
$align = is_rtl() ? 'right' : 'left';
?>
<!doctype html>
<html lang="<?= e(current_locale()) ?>" dir="<?= $dir ?>">
<head>
    <meta charset="utf-8">
    <title><?= e($title) ?> - <?= e(site_name()) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Tahoma, "Segoe UI", Arial, sans-serif; margin: 24px; color: #0f172a; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .meta { color: #64748b; font-size: 12px; margin-bottom: 16px; }
        .actions { margin-bottom: 16px; }
        .actions .hint { color: #64748b; font-size: 12px; margin-inline-start: 10px; }
        .actions button:disabled { opacity: .6; cursor: default; }
        .actions button { padding: 8px 18px; border: 0; border-radius: 8px; background: #1e3a5f; color: #fff; font-size: 13px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: <?= $align ?>; }
        th { background: #1e3a5f; color: #fff; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        .empty { text-align: center; color: #94a3b8; padding: 24px; }
        @media print {
            .actions { display: none; }
            body { margin: 0; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" id="print-btn"><?= is_rtl() ? 'طباعة / حفظ كملف PDF' : 'Print / Save as PDF' ?></button>
        <span class="hint"><?= is_rtl() ? 'في نافذة الطباعة اختر الوجهة "حفظ كملف PDF" (Save as PDF) وليس الطابعة.' : 'In the print window, choose "Save as PDF" as the destination, not a physical printer.' ?></span>
    </div>
    <h1><?= e(site_name()) ?> - <?= e($title) ?></h1>
    <div class="meta"><?= is_rtl() ? 'تاريخ التقرير:' : 'Report date:' ?> <bdi dir="ltr"><?= date('d/m/Y H:i') ?></bdi> · <?= is_rtl() ? 'عدد السجلات:' : 'Records:' ?> <?= count($rows) ?></div>
    <table>
        <thead>
            <tr><?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr><?php foreach ($row as $cell): ?><td><?= e($cell) ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr><td class="empty" colspan="<?= count($headers) ?>"><?= is_rtl() ? 'لا توجد بيانات' : 'No data' ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <script>
        // Printing only starts from the button, never automatically, and never twice at once.
        // (Auto-printing with an offline default printer left Windows stuck on "Waiting for printer connection".)
        (function () {
            var busy = false;
            document.getElementById('print-btn').addEventListener('click', function () {
                if (busy) { return; }
                busy = true;
                var btn = this;
                btn.disabled = true;
                try { window.print(); } finally {
                    setTimeout(function () { busy = false; btn.disabled = false; }, 2000);
                }
            });
        })();
    </script>
</body>
</html>
