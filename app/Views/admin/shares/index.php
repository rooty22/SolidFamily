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
            <?php foreach ($members as $m): 
                $mLots = $lotsByMember[$m['id']] ?? [];
            ?>
            <tr>
                <td class="fw-bold">
                    <a href="<?= url('admin/members/' . $m['id']) ?>" class="text-slate-900 hover:text-sky-600 text-decoration-none">
                        <?= e($m['name']) ?>
                    </a>
                    <?php if (count($mLots) > 1): ?>
                        <span class="badge bg-purple-100 text-purple-700 text-[10px] ms-1"><?= count($mLots) ?> حصص</span>
                    <?php endif; ?>
                </td>
                <td class="font-numeric">
                    <span class="fw-bold"><?= number_format($m['shares_count']) ?></span>
                </td>
                <td class="font-numeric"><?= money($shareValue) ?></td>
                <td class="font-numeric"><?= money($m['shares_count'] * $shareValue) ?></td>
                <td class="text-end">
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" class="btn btn-sm btn-soft-primary inline-flex items-center gap-1" data-bs-toggle="modal" data-bs-target="#manageLots<?= $m['id'] ?>" title="<?= is_rtl() ? 'إدارة حصص الأسهم (دمج / إلغاء / إضافة)' : 'Manage Lots' ?>">
                            <i class="bi bi-stack"></i>
                            <span><?= is_rtl() ? 'إدارة الحصص' : 'Lots' ?></span>
                        </button>
                        <button type="button" class="btn btn-sm btn-soft inline-flex items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#editShares<?= $m['id'] ?>">
                            <i class="bi bi-pencil"></i>
                            <span><?= is_rtl() ? 'تعديل الإجمالي' : 'Edit' ?></span>
                        </button>
                    </div>
                </td>
            </tr>

            <!-- Detailed Lots Management Modal for Member -->
            <div class="modal fade" id="manageLots<?= $m['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title font-bold text-slate-900"><?= is_rtl() ? 'إدارة حصص الأسهم: ' : 'Manage Lots: ' ?><?= e($m['name']) ?></h5>
                                <p class="text-xs text-slate-500 mb-0"><?= is_rtl() ? 'إجمالي الأسهم: ' . number_format($m['shares_count']) . ' سهم | الحصص النشطة: ' . count($mLots) : 'Total Shares: ' . number_format($m['shares_count']) ?></p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body space-y-4">
                            <!-- Quick Action Buttons -->
                            <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <?php if (count($mLots) >= 2): ?>
                                        <form method="post" action="<?= url('admin/shares/' . $m['id'] . '/merge') ?>" onsubmit="return confirm('<?= is_rtl() ? 'هل أنت متأكد من دمج جميع حصص الأسهم لهذا المشترك في سهم واحد؟' : 'Are you sure you want to merge all share lots into one?' ?>');" class="m-0">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-purple font-bold inline-flex items-center gap-1.5">
                                                <i class="bi bi-arrows-collapse"></i>
                                                <span><?= is_rtl() ? 'دمج الأسهم في سهم واحد' : 'Merge All Lots' ?></span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400"><?= is_rtl() ? 'الدمج متاح عند وجود حصتين أو أكثر.' : 'Merge available for 2+ lots.' ?></span>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary inline-flex items-center gap-1.5 font-bold" data-bs-toggle="collapse" data-bs-target="#addLotCollapse<?= $m['id'] ?>">
                                    <i class="bi bi-plus-lg"></i>
                                    <span><?= is_rtl() ? 'إضافة حصة / سهم جديد' : 'Add New Lot' ?></span>
                                </button>
                            </div>

                            <!-- Add Lot Form (Collapse) -->
                            <div class="collapse" id="addLotCollapse<?= $m['id'] ?>">
                                <form method="post" action="<?= url('admin/shares/' . $m['id'] . '/add-lot') ?>" class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                                    <?= csrf_field() ?>
                                    <h6 class="font-bold text-slate-900 text-xs mb-2"><?= is_rtl() ? 'إضافة حصة أسهم جديدة للمشترك' : 'Add New Share Lot' ?></h6>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="form-label text-xs font-bold text-slate-700"><?= is_rtl() ? 'عدد الأسهم في هذه الحصة' : 'Shares Count' ?></label>
                                            <input type="number" name="shares_count" min="1" max="1000" value="1" class="form-control text-sm font-numeric font-bold" required>
                                        </div>
                                        <div>
                                            <label class="form-label text-xs font-bold text-slate-700"><?= is_rtl() ? 'تاريخ بداية اشتراك هذه الحصة' : 'Start Date' ?></label>
                                            <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" class="form-control text-sm font-numeric font-bold" required>
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-2 pt-2">
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="collapse" data-bs-target="#addLotCollapse<?= $m['id'] ?>"><?= is_rtl() ? 'إلغاء' : 'Cancel' ?></button>
                                        <button type="submit" class="btn btn-sm btn-primary font-bold"><?= is_rtl() ? 'إضافة الحصة الآن' : 'Save Lot' ?></button>
                                    </div>
                                </form>
                            </div>

                            <!-- Lots List Table -->
                            <div class="table-responsive">
                                <table class="table-modern text-xs">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th><?= is_rtl() ? 'الحصة' : 'Lot' ?></th>
                                            <th><?= is_rtl() ? 'عدد الأسهم' : 'Shares' ?></th>
                                            <th><?= is_rtl() ? 'تاريخ بداية الاشتراك' : 'Start Date' ?></th>
                                            <th><?= is_rtl() ? 'يوم الاستحقاق' : 'Due Day' ?></th>
                                            <th class="text-end"><?= is_rtl() ? 'إجراءات الحصة' : 'Actions' ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($mLots)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-slate-400 py-3"><?= is_rtl() ? 'لا توجد حصص نشطة للمشترك.' : 'No active lots.' ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($mLots as $lIdx => $lot): ?>
                                                <tr>
                                                    <td class="font-numeric fw-bold">#<?= $lIdx + 1 ?></td>
                                                    <td class="font-bold text-purple-700">
                                                        <?= is_rtl() ? 'السهم رقم ' . ($lIdx + 1) : 'Share #' . ($lIdx + 1) ?>
                                                    </td>
                                                    <td class="font-numeric fw-bold"><?= number_format($lot['shares_count']) ?> سهم</td>
                                                    <td class="font-numeric">
                                                        <bdi dir="ltr"><?= !empty($lot['created_at']) ? date_ar(substr($lot['created_at'], 0, 10)) : '-' ?></bdi>
                                                    </td>
                                                    <td class="font-numeric text-slate-600">
                                                        <?= (int) \App\Models\MonthlySubscription::dueDayFor($m, $lot) ?> من كل شهر
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="flex items-center justify-end gap-1">
                                                            <!-- Edit Lot Start Date Trigger -->
                                                            <button type="button" class="btn btn-xs btn-outline-secondary inline-flex items-center gap-1" data-bs-toggle="collapse" data-bs-target="#editDateCollapse<?= $lot['id'] ?>" title="<?= is_rtl() ? 'تعديل تاريخ بداية الحصة' : 'Edit Date' ?>">
                                                                <i class="bi bi-calendar-event"></i>
                                                                <span><?= is_rtl() ? 'تعديل التاريخ' : 'Date' ?></span>
                                                            </button>
                                                            <!-- Cancel Lot Form -->
                                                            <form method="post" action="<?= url('admin/shares/' . $m['id'] . '/cancel-lot/' . $lot['id']) ?>" onsubmit="return confirm('<?= is_rtl() ? 'هل أنت متأكد من إلغاء السهم رقم ' . ($lIdx + 1) . ' (' . $lot['shares_count'] . ' سهم)؟ سيتم تخفيض الأسهم وإلغاء استحقاقاتها المستقبلية.' : 'Are you sure you want to cancel this lot?' ?>');" class="m-0 d-inline">
                                                                <?= csrf_field() ?>
                                                                <button type="submit" class="btn btn-xs btn-outline-danger inline-flex items-center gap-1" title="<?= is_rtl() ? 'إلغاء هذه الحصة' : 'Cancel Lot' ?>">
                                                                    <i class="bi bi-trash"></i>
                                                                    <span><?= is_rtl() ? 'إلغاء' : 'Cancel' ?></span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                        <!-- Inline Edit Date Collapse -->
                                                        <div class="collapse text-start mt-2" id="editDateCollapse<?= $lot['id'] ?>">
                                                            <form method="post" action="<?= url('admin/subscriptions/' . $m['id'] . '/lot/' . $lot['id'] . '/date') ?>" class="p-2.5 bg-slate-50 border rounded-lg">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="redirect_to" value="admin/shares">
                                                                <label class="form-label text-[11px] font-bold text-slate-700 mb-1"><?= is_rtl() ? 'تاريخ بداية اشتراك السهم الجديد:' : 'New Start Date:' ?></label>
                                                                <div class="flex items-center gap-1.5">
                                                                    <input type="date" name="start_date" class="form-control form-control-sm text-xs font-numeric" value="<?= !empty($lot['created_at']) ? substr($lot['created_at'], 0, 10) : date('Y-m-d') ?>" required>
                                                                    <button type="submit" class="btn btn-xs btn-primary font-bold shrink-0"><?= is_rtl() ? 'حفظ' : 'Save' ?></button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Edit Total Shares Modal -->
            <div class="modal fade" id="editShares<?= $m['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post" action="<?= url('admin/shares/' . $m['id']) ?>">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <h5 class="modal-title font-bold"><?= is_rtl() ? 'تعديل إجمالي أسهم: ' : 'Edit Shares: ' ?><?= e($m['name']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body space-y-3">
                                <div>
                                    <label class="form-label text-xs font-bold text-slate-700 mb-1"><?= is_rtl() ? 'عدد الأسهم الإجمالي' : 'Total Shares Count' ?></label>
                                    <input type="number" min="0" name="shares_count" class="form-control font-numeric font-bold" value="<?= $m['shares_count'] ?>">
                                </div>
                                <div>
                                    <label class="form-label text-xs font-bold text-slate-700 mb-1"><?= is_rtl() ? 'تاريخ بداية اشتراك الأسهم الجديدة (في حال الزيادة)' : 'Start Date for New Shares' ?></label>
                                    <input type="date" name="start_date" class="form-control text-sm font-numeric" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                                    <p class="text-[11px] text-slate-400 mt-1 mb-0"><?= is_rtl() ? 'في حال إضافة أسهم جديدة، سيتم توثيق هذا التاريخ كتاريخ بداية اشتراكها.' : 'Documented as start date if new shares are added.' ?></p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary font-bold"><?= is_rtl() ? 'حفظ التعديل' : 'Save Changes' ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
