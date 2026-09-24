<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= is_rtl() ? 'المبالغ التأسيسية ورأس مال الصندوق' : 'Founding Capital & Member Allocations' ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= is_rtl() ? 'متابعة سداد المبالغ التأسيسية وأسهم رأس المال التأسيسي للأعضاء' : 'Monitor founding share commitments, payments, and remaining balances' ?></p>
    </div>
</div>

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
                <th><?= is_rtl() ? 'خطة السداد' : 'Plan' ?></th>
                <th class="text-end"><?= is_rtl() ? 'إجراءات' : 'Actions' ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): [$l, $v] = status_badge($r['status']); $remaining = $r['total_required'] - $r['amount_paid']; ?>
            <tr>
                <td class="fw-bold"><?= e($r['member']['name']) ?></td>
                <td class="font-numeric"><?= number_format($r['shares_count_linked']) ?></td>
                <td class="font-numeric"><?= money($r['total_required']) ?></td>
                <td class="font-numeric text-emerald-600 font-bold"><?= money($r['amount_paid']) ?></td>
                <td class="font-numeric text-rose-500 font-bold"><?= money($remaining) ?></td>
                <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                <td class="text-xs">
                    <?php if (empty($r['plan_start'])): ?>
                        <span class="text-slate-400"><?= is_rtl() ? 'لم يختر' : 'Not chosen' ?></span>
                    <?php else: ?>
                        <b><?= (int) $r['plan_months'] === 1 ? (is_rtl() ? 'مرة واحدة' : 'One payment') : (is_rtl() ? 'على ' . (int) $r['plan_months'] . ' أشهر' : 'Over ' . (int) $r['plan_months'] . ' months') ?></b>
                        <?php if (!empty($r['next_installment'])): ?><div class="text-slate-500 font-numeric"><?= money($r['next_installment']['remaining']) ?> · <?= date_ar($r['next_installment']['due_date']) ?></div><?php endif; ?>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <a href="<?= url('admin/founding/' . $r['member']['id']) ?>" class="btn btn-sm btn-soft inline-flex items-center gap-1.5">
                        <i class="bi bi-eye"></i>
                        <span><?= is_rtl() ? 'التفاصيل' : 'Details' ?></span>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr>
                <td colspan="8" class="p-0">
                    <div class="empty-state">
                        <i class="bi bi-bank2"></i>
                        <span><?= is_rtl() ? 'لا توجد سجلات تأسيسية مسجلة' : 'No founding capital records found.' ?></span>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
