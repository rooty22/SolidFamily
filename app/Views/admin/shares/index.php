<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 mb-5">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base">
                    <i class="bi bi-tag-fill"></i>
                </span>
                <span><?= is_rtl() ? 'قيمة السهم الواحد (السياسة المالية للصندوق)' : 'Share Unit Value (Fund Financial Policy)' ?></span>
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                <?= is_rtl() ? 'تُطبق هذه القيمة على جميع الاشتراكات الشهرية وحساب رأس مال الصندوق تلقائياً' : 'This value applies to all monthly subscriptions and fund capital calculation' ?>
            </p>
        </div>
        <form method="post" action="<?= url('admin/shares/value') ?>" class="flex items-center gap-2.5 flex-wrap">
            <?= csrf_field() ?>
            <div class="flex items-center rounded-xl border border-slate-300 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 bg-white overflow-hidden shadow-sm">
                <input type="number" step="0.01" min="0.01" name="share_value" class="py-2 px-3 text-sm font-bold font-numeric border-0 focus:outline-none w-32" value="<?= e($shareValue) ?>">
                <span class="px-3 py-2 bg-slate-50 border-s border-slate-200 text-xs font-bold text-slate-500 shrink-0">
                    <?= is_rtl() ? 'ريال' : 'SAR' ?>
                </span>
            </div>
            <button type="submit" class="btn btn-primary py-2 px-4 rounded-xl text-xs sm:text-sm font-bold inline-flex items-center gap-1.5 shadow-sm">
                <i class="bi bi-check2"></i>
                <span><?= is_rtl() ? 'تحديث القيمة' : 'Update Value' ?></span>
            </button>
        </form>
    </div>
</div>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th><?= is_rtl() ? 'المشترك' : 'Member' ?></th>
                <th><?= is_rtl() ? 'عدد الأسهم' : 'Shares' ?></th>
                <th><?= is_rtl() ? 'قيمة السهم' : 'Share Price' ?></th>
                <th><?= is_rtl() ? 'إجمالي الاشتراك الشهري' : 'Monthly Due Total' ?></th>
                <th class="text-end"><?= is_rtl() ? 'إجراءات' : 'Actions' ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $m): ?>
            <tr>
                <td class="fw-bold"><?= e($m['name']) ?></td>
                <td class="font-numeric"><?= number_format($m['shares_count']) ?></td>
                <td class="font-numeric"><?= money($shareValue) ?></td>
                <td class="font-numeric"><?= money($m['shares_count'] * $shareValue) ?></td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-soft inline-flex items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#editShares<?= $m['id'] ?>">
                        <i class="bi bi-pencil"></i>
                        <span><?= is_rtl() ? 'تعديل' : 'Edit' ?></span>
                    </button>
                </td>
            </tr>
            <div class="modal fade" id="editShares<?= $m['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post" action="<?= url('admin/shares/' . $m['id']) ?>">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <h5 class="modal-title"><?= is_rtl() ? 'تعديل أسهم: ' : 'Edit Shares: ' ?><?= e($m['name']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <label class="form-label"><?= is_rtl() ? 'عدد الأسهم' : 'Number of Shares' ?></label>
                                <input type="number" min="0" name="shares_count" class="form-control font-numeric" value="<?= $m['shares_count'] ?>">
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary"><?= is_rtl() ? 'حفظ' : 'Save Changes' ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
