<?php
$isEn = current_locale() === 'en';
?>
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-800 flex items-center gap-2 mb-1">
                <i class="bi bi-person-badge-fill text-emerald-600"></i>
                <span><?= $isEn ? 'Grant Dashboard Access to Member' : 'تعيين صلاحيات لوحة التحكم لعضو' ?></span>
            </h1>
            <p class="text-xs text-slate-500 mb-0">
                <?= $isEn ? 'Select a family fund member and assign an administrative role to govern their capabilities on the dashboard.' : 'اختر أحد أفراد العائلة المشتركين بالصندوق وعيّن له دوراً إدارياً لممارسة مهامه على لوحة التحكم.' ?>
            </p>
        </div>
        <a href="<?= url('admin/roles') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
            <i class="bi bi-arrow-<?= is_rtl() ? 'right' : 'left' ?>"></i>
            <span><?= $isEn ? 'Back' : 'العودة' ?></span>
        </a>
    </div>

    <form method="post" action="<?= url('admin/roles/assign') ?>" class="space-y-6">
        <?= csrf_field() ?>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-5">
            <!-- Member Selection -->
            <div>
                <label class="form-label text-xs font-bold text-slate-700">
                    <?= $isEn ? 'Select Fund Member' : 'اختيار عضو الصندوق' ?> <span class="text-rose-500">*</span>
                </label>
                <?php if ($member): ?>
                    <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                    <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-xl flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-200 text-emerald-800 font-black flex items-center justify-center text-sm">
                                <?= mb_substr($member['name'], 0, 1) ?>
                            </div>
                            <div>
                                <div class="text-xs font-black text-slate-900"><?= e($member['name']) ?></div>
                                <div class="text-[11px] text-slate-600 font-numeric flex items-center gap-2 mt-0.5">
                                    <span><?= e($member['email']) ?></span>
                                    <span>•</span>
                                    <span><?= e($member['national_id']) ?></span>
                                    <span>•</span>
                                    <span><?= e($member['mobile']) ?></span>
                                </div>
                            </div>
                        </div>
                        <a href="<?= url('admin/roles/assign') ?>" class="text-[11px] font-bold text-emerald-700 hover:underline">
                            <?= $isEn ? 'Change Member' : 'تغيير العضو' ?>
                        </a>
                    </div>
                <?php else: ?>
                    <select name="member_id" required class="form-select text-xs rounded-xl border-slate-200 py-2.5">
                        <option value=""><?= $isEn ? '-- Select Member --' : '-- اختر العضو من القائمة --' ?></option>
                        <?php foreach ($allMembers as $m): ?>
                            <option value="<?= $m['id'] ?>">
                                <?= e($m['name']) ?> (<?= e($m['national_id']) ?> - <?= e($m['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <!-- Role Selection -->
            <div>
                <label class="form-label text-xs font-bold text-slate-700 mb-2">
                    <?= $isEn ? 'Designate Administrative Role' : 'تحديد الدور الوظيفي في لوحة التحكم' ?> <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php foreach ($roles as $r): ?>
                        <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer hover:border-emerald-300 transition-all bg-white shadow-2xs has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/30">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-bold text-xs text-slate-900 flex items-center gap-1.5">
                                    <i class="bi bi-shield-check text-emerald-600"></i>
                                    <span><?= e($isEn ? ($r['label_en'] ?: $r['label_ar']) : $r['label_ar']) ?></span>
                                </span>
                                <input type="radio" name="role_id" value="<?= $r['id'] ?>" <?= ($currentRoleId == $r['id'] || (!$currentRoleId && $r['name'] === 'treasurer')) ? 'checked' : '' ?> class="text-emerald-600 focus:ring-emerald-500">
                            </div>
                            <p class="text-[11px] text-slate-500 mb-0 line-clamp-2">
                                <?= e($r['description'] ?: ($isEn ? 'Standard capabilities' : 'صلاحيات قياسية')) ?>
                            </p>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Note about Loan Exemption -->
            <div class="bg-amber-50 border border-amber-200/80 rounded-xl p-3.5 flex items-start gap-2.5">
                <i class="bi bi-info-circle-fill text-amber-600 text-sm mt-0.5"></i>
                <div class="text-[11px] text-amber-900 leading-relaxed">
                    <span class="font-bold"><?= $isEn ? 'Automatic Administrative Privileges:' : 'ملاحظة مهمة:' ?></span>
                    <?= $isEn 
                        ? 'Once dashboard access is granted, the member can log in to the admin panel using their member credentials. Their account is automatically flagged as administrative and exempt from taking loans.' 
                        : 'بمجرد تعيين الصلاحيات، سيتمكن هذا العضو من تسجيل الدخول إلى لوحة التحكم (/admin/login) باستخدام بريده أو رقم هويته وكلمة مروره الخاصة، وسيتم وسمه كحساب إداري معفى من القروض.' ?>
                </div>
            </div>
        </div>

        <!-- Optional Direct Permissions Accordion -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5" x-data="{ showDirect: <?= !empty($currentDirectPerms) ? 'true' : 'false' ?> }">
            <div class="flex items-center justify-between cursor-pointer" @click="showDirect = !showDirect">
                <div>
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-700 mb-0.5 flex items-center gap-2">
                        <i class="bi bi-sliders2"></i>
                        <span><?= $isEn ? 'Direct Permissions Override (Optional)' : 'صلاحيات مباشرة إضافية وتخصيص دقيق (اختياري)' ?></span>
                    </h2>
                    <p class="text-[11px] text-slate-400 mb-0">
                        <?= $isEn ? 'Grant additional specific permissions on top of the assigned role.' : 'يمكنك إضافة صلاحيات إضافية محددة لهذا العضو بالإضافة لصلاحيات دوره الأساسي.' ?>
                    </p>
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-600 text-sm">
                    <i class="bi" :class="showDirect ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </button>
            </div>

            <div x-show="showDirect" x-transition class="mt-4 pt-4 border-t border-slate-100">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($groupedPermissions as $moduleKey => $group): ?>
                        <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50">
                            <div class="font-bold text-xs text-slate-800 mb-2 pb-1 border-b border-slate-200 flex items-center gap-1.5">
                                <i class="bi bi-folder text-emerald-600"></i>
                                <span><?= e($isEn ? $group['label_en'] : $group['label_ar']) ?></span>
                            </div>
                            <div class="space-y-2">
                                <?php foreach ($group['permissions'] as $p): ?>
                                    <label class="flex items-center gap-2 text-xs text-slate-700 hover:text-slate-900 cursor-pointer select-none">
                                        <input type="checkbox" name="direct_permissions[]" value="<?= $p['id'] ?>" <?= in_array($p['id'], $currentDirectPerms, true) ? 'checked' : '' ?> class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        <span><?= e($isEn ? $p['label_en'] : $p['label_ar']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3">
            <a href="<?= url('admin/roles') ?>" class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all">
                <?= $isEn ? 'Cancel' : 'إلغاء' ?>
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-2">
                <i class="bi bi-check2-circle text-base"></i>
                <span><?= $isEn ? 'Activate Dashboard Access' : 'تفعيل صلاحيات لوحة التحكم' ?></span>
            </button>
        </div>
    </form>
</div>
