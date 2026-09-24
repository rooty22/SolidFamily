<?php 
[$label, $variant] = status_badge($request['status']); 
$currentShares = (int) ($member['shares_count'] ?? 1);
$requestedShares = (int) ($request['shares_count'] ?? 0);
$typeTitle = $typeLabels[$request['type']] ?? $request['type'];
?>

<div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?= url('admin/share-requests') ?>" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors">
                <i class="bi <?= is_rtl() ? 'bi-arrow-right' : 'bi-arrow-left' ?> text-lg"></i>
            </a>
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl font-bold text-slate-900">طلب أسهم #<?= $request['id'] ?> - <?= e($typeTitle) ?></h1>
                    <span class="badge-status badge-<?= $variant ?>"><?= $label ?></span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">تاريخ التقديم: <?= date_ar($request['created_at'], 'Y-m-d H:i') ?></p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?= url('admin/members/' . $member['id']) ?>" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 inline-flex items-center gap-1.5 transition-colors">
                <i class="bi bi-person-badge"></i>
                <span>ملف المشترك</span>
            </a>
        </div>
    </div>

    <!-- 2-Column Responsive Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Column (Request Info & Decision) -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5">
                <div class="border-b border-slate-100 pb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">تفاصيل معاملة الأسهم</h2>
                        <p class="text-xs text-slate-500 mt-0.5">نوع الطلب والكمية المطلوبة</p>
                    </div>
                    <span class="px-3 py-1 rounded-lg text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                        <?= e($typeTitle) ?>
                    </span>
                </div>

                <?php
                // members.shares_count is always the balance NOW. Once a request is approved it already includes the request,
                // so "current + requested" would count it twice: the projection only makes sense while the request is pending.
                $isPending = $request['status'] === 'pending';
                $delta = $request['type'] === 'cancel' ? -$requestedShares : ($request['type'] === 'add' ? $requestedShares : 0);
                ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/60">
                        <span class="text-[11px] font-semibold text-slate-500 block mb-1"><?= $isPending ? 'الأسهم الحالية' : 'رصيد المشترك الحالي' ?></span>
                        <span class="text-lg font-bold text-slate-800 font-numeric"><?= number_format($currentShares) ?> سهم</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/60">
                        <span class="text-[11px] font-semibold text-slate-500 block mb-1">الأسهم المطلوبة</span>
                        <span class="text-lg font-bold text-brand-600 font-numeric"><?= $requestedShares ? ($request['type'] === 'cancel' ? '-' : '+') . number_format($requestedShares) . ' سهم' : 'غير محدد (دمج)' ?></span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/60 col-span-2 sm:col-span-1">
                        <?php if ($isPending): ?>
                            <span class="text-[11px] font-semibold text-slate-500 block mb-1">الرصيد بعد التنفيذ</span>
                            <span class="text-lg font-bold text-indigo-700 font-numeric"><?= number_format(max(0, $currentShares + $delta)) ?> سهم</span>
                        <?php elseif ($request['status'] === 'approved'): ?>
                            <span class="text-[11px] font-semibold text-slate-500 block mb-1">حالة التنفيذ</span>
                            <span class="text-sm font-bold text-emerald-700">تم التنفيذ، والرصيد الحالي يشمل الطلب</span>
                        <?php else: ?>
                            <span class="text-[11px] font-semibold text-slate-500 block mb-1">حالة التنفيذ</span>
                            <span class="text-sm font-bold text-slate-600">لم يُنفَّذ، الرصيد دون تغيير</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="py-3 px-4 bg-slate-50/50 text-xs font-bold text-slate-600 w-1/3">تاريخ التقديم</td>
                                <td class="py-3 px-4 text-xs font-medium text-slate-700 font-numeric"><?= date_ar($request['created_at'], 'Y-m-d H:i') ?></td>
                            </tr>
                            <?php if (!empty($request['admin_note'])): ?>
                            <tr>
                                <td class="py-3 px-4 bg-slate-50/50 text-xs font-bold text-slate-600">ملاحظة الإدارة المسجلة</td>
                                <td class="py-3 px-4 text-xs text-slate-700 leading-relaxed font-semibold"><?= e($request['admin_note']) ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Decision Box -->
            <?php if ($request['status'] === 'pending'): ?>
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
                    <h3 class="text-base font-bold text-slate-900">اتخاذ القرار الإداري</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Approve -->
                        <div class="p-4 rounded-xl bg-emerald-50/60 border border-emerald-200 flex flex-col justify-between">
                            <div class="mb-3">
                                <div class="flex items-center gap-2 text-emerald-800 font-bold text-sm mb-1">
                                    <i class="bi bi-check2-circle text-lg"></i>
                                    <span>الموافقة على الطلب</span>
                                </div>
                                <p class="text-xs text-emerald-700">سيتم تعديل رصيد أسهم العضو مباشرة في سجل المساهمين.</p>
                            </div>
                            <form method="post" action="<?= url('admin/share-requests/' . $request['id'] . '/approve') ?>" data-confirm="تأكيد الموافقة على الطلب؟">
                                <?= csrf_field() ?>
                                <input type="text" name="admin_note" class="w-full text-xs py-2 px-3 rounded-lg border border-emerald-200 bg-white mb-2" placeholder="ملاحظة اعتماد (اختياري)...">
                                <input type="number" name="subscription_due_day" min="1" max="28" value="<?= e($member['subscription_due_day'] ?? '') ?>" class="w-full text-xs py-2 px-3 rounded-lg border border-emerald-200 bg-white mb-2 font-numeric" placeholder="يوم استحقاق مخصص لهذا العضو (اختياري، 1-28)...">
                                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm flex items-center justify-center gap-2">
                                    <i class="bi bi-check-lg text-base"></i>
                                    <span>الموافقة والاعتماد</span>
                                </button>
                            </form>
                        </div>

                        <!-- Reject -->
                        <div class="p-4 rounded-xl bg-rose-50/60 border border-rose-200 flex flex-col justify-between">
                            <div class="mb-3">
                                <div class="flex items-center gap-2 text-rose-800 font-bold text-sm mb-1">
                                    <i class="bi bi-x-circle text-lg"></i>
                                    <span>رفض الطلب</span>
                                </div>
                                <p class="text-xs text-rose-700">سيتم إشعار المشترك بسبب الرفض المدون.</p>
                            </div>
                            <form method="post" action="<?= url('admin/share-requests/' . $request['id'] . '/reject') ?>" data-confirm="تأكيد رفض الطلب؟">
                                <?= csrf_field() ?>
                                <input type="text" name="admin_note" class="w-full text-xs py-2 px-3 rounded-lg border border-rose-200 bg-white mb-2" placeholder="سبب الرفض (اختياري)...">
                                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-white hover:bg-rose-100 text-rose-700 border border-rose-300 font-bold text-xs flex items-center justify-center gap-2">
                                    <i class="bi bi-x-lg"></i>
                                    <span>رفض الطلب</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column (Member Card) -->
        <div class="lg:col-span-5 xl:col-span-4 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                <div class="flex items-center gap-3.5 pb-4 border-b border-slate-100 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-brand-600 to-emerald-400 text-white font-black text-xl flex items-center justify-center shadow-md shadow-brand-500/20">
                        <?= mb_substr($member['name'], 0, 1) ?>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900"><?= e($member['name']) ?></h3>
                        <p class="text-xs text-slate-400 font-numeric"><?= e($member['mobile']) ?></p>
                    </div>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">رقم الهوية</span>
                        <span class="font-bold text-slate-800 font-numeric"><?= e($member['national_id'] ?? '-') ?></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">تاريخ الانضمام</span>
                        <span class="font-bold text-slate-800 font-numeric"><?= date_ar($member['created_at'], 'Y-m-d') ?></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">حالة العضوية</span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <?= $member['status'] === 'active' ? 'نشط' : e($member['status']) ?>
                        </span>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100">
                    <a href="<?= url('admin/members/' . $member['id']) ?>" class="w-full py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 flex items-center justify-center gap-1.5 transition-colors">
                        <i class="bi bi-box-arrow-up-right"></i>
                        <span>عرض كشف حساب المشترك</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
