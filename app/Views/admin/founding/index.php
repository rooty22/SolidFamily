<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= is_rtl() ? 'المبالغ التأسيسية ورأس مال الصندوق' : 'Founding Capital & Member Allocations' ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= is_rtl() ? 'متابعة سداد المبالغ التأسيسية وأسهم رأس المال التأسيسي للأعضاء' : 'Monitor founding share commitments, payments, and remaining balances' ?></p>
    </div>
</div>

<!-- Search a member by name / mobile / national ID -->
<form method="get" action="<?= url('admin/founding') ?>" class="flex flex-wrap items-center gap-2 mb-4">
    <div class="search-box" style="min-width:300px; flex: 1 1 300px; max-width: 460px;">
        <i class="bi bi-search"></i>
        <input type="text" name="q" class="form-control" placeholder="<?= is_rtl() ? 'ابحث باسم المشترك أو الجوال أو الهوية...' : 'Search by name, mobile or national ID...' ?>" value="<?= e($q ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-primary btn-sm font-bold inline-flex items-center gap-1.5"><i class="bi bi-search"></i> <?= is_rtl() ? 'بحث' : 'Search' ?></button>
    <?php if (($q ?? '') !== ''): ?>
        <a href="<?= url('admin/founding') ?>" class="btn btn-soft btn-sm inline-flex items-center gap-1.5"><i class="bi bi-x-circle"></i> <?= is_rtl() ? 'إلغاء البحث' : 'Clear' ?></a>
        <span class="text-xs text-slate-500 font-bold"><?= is_rtl() ? number_format($pager['total']) . ' نتيجة' : number_format($pager['total']) . ' result(s)' ?></span>
    <?php endif; ?>
</form>

<?php if (($q ?? '') === '' && (int) ($noFoundingCount ?? 0) > 0): ?>
    <div class="text-xs text-slate-500 font-bold mb-2 inline-flex items-center gap-1.5">
        <i class="bi bi-info-circle"></i>
        <?= is_rtl() ? number_format((int) $noFoundingCount) . ' مشترك بدون أسهم (لا مبلغ تأسيس عليهم) غير معروضين هنا — ابحث باسم المشترك لعرضه.' : number_format((int) $noFoundingCount) . ' member(s) without shares (no founding amount) are not listed — search by name to show one.' ?>
    </div>
<?php endif; ?>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th><?= is_rtl() ? 'المشترك' : 'Member' ?></th>
                <th><?= is_rtl() ? 'عدد الأسهم' : 'Shares' ?></th>
                <th><?= is_rtl() ? 'المطلوب' : 'Required' ?></th>
                <th><?= is_rtl() ? 'المسدد' : 'Paid' ?></th>
                <th><?= is_rtl() ? 'المتبقي' : 'Remaining' ?></th>
                <th><?= is_rtl() ? 'الحالة' : 'Status' ?></th>
                <th><?= is_rtl() ? 'الأسهم المسددة' : 'Shares paid' ?></th>
                <th><?= is_rtl() ? 'خطة السداد' : 'Plan' ?></th>
                <th class="text-end"><?= is_rtl() ? 'إجراءات' : 'Actions' ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): [$l, $v] = status_badge((float) $r['total_required'] > 0 ? $r['status'] : 'no_shares'); $remaining = max(0, round($r['total_required'] - $r['amount_paid'], 2)); $surplus = max(0, round($r['amount_paid'] - $r['total_required'], 2)); ?>
            <tr>
                <td><?= member_cell((string) $r['member']['name'], $r['member']['serial_no'] ?? null, url('admin/founding/' . $r['member']['id'])) ?></td>
                <td class="font-numeric"><?= number_format($r['shares_count_linked']) ?></td>
                <td class="font-numeric"><?= money($r['total_required']) ?></td>
                <td class="font-numeric text-emerald-600 font-bold"><?= money($r['amount_paid']) ?></td>
                <td class="font-numeric text-rose-500 font-bold"><?= money($remaining) ?><?= $surplus > 0 ? '<div class="text-[11px] text-sky-600 font-bold">' . e(is_rtl() ? 'زيادة ' . money($surplus) : 'surplus ' . money($surplus)) . '</div>' : '' ?></td>
                <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                <td class="text-xs font-numeric">
                    <?php $lotsTotal = count($r['lots'] ?? []); $lotsMet = count(array_filter($r['lots'] ?? [], fn($b) => $b['met'])); ?>
                    <?php if ($lotsTotal === 0): ?><span class="text-slate-400">-</span>
                    <?php else: ?><span class="badge-status badge-<?= $lotsMet === $lotsTotal ? 'success' : ($lotsMet > 0 ? 'warning' : 'secondary') ?>" title="<?= is_rtl() ? 'عدد الأسهم التي اكتمل مبلغ تأسيسها من إجمالي أسهم المشترك' : 'Shares whose own founding amount is complete' ?>"><?= $lotsMet ?> / <?= $lotsTotal ?></span><?php endif; ?>
                </td>
                <td class="text-xs">
                    <?php if (empty($r['plan_start'])): ?>
                        <span class="text-slate-400"><?= is_rtl() ? 'لم يختر' : 'Not chosen' ?></span>
                    <?php else: ?>
                        <b><?= (int) $r['plan_months'] === 1 ? (is_rtl() ? 'مرة واحدة' : 'One payment') : (is_rtl() ? 'على ' . (int) $r['plan_months'] . ' أشهر' : 'Over ' . (int) $r['plan_months'] . ' months') ?></b>
                        <?php if (!empty($r['next_installment'])): ?><div class="text-slate-500 font-numeric"><?= money($r['next_installment']['remaining']) ?> · <?= date_ar($r['next_installment']['due_date']) ?></div><?php endif; ?>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <div class="row-actions">
                        <a href="<?= url('admin/founding/' . $r['member']['id']) ?>" class="row-btn row-btn-view row-btn-text"><i class="bi bi-eye"></i> <?= is_rtl() ? 'التفاصيل' : 'Details' ?></a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr>
                <td colspan="9" class="p-0">
                    <div class="empty-state">
                        <i class="bi bi-bank2"></i>
                        <span><?= ($q ?? '') !== '' ? (is_rtl() ? 'لا يوجد مشترك مطابق للبحث.' : 'No member matches your search.') : (is_rtl() ? 'لا توجد مبالغ تأسيس مسجلة بعد.' : 'No founding amounts recorded yet.') ?></span>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= pagination_links($pager ?? []) ?>
