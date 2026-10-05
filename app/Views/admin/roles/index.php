<?php
$isEn = current_locale() === 'en';
?>
<div class="space-y-6">
    <!-- Header with Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-xl font-black text-slate-800 flex items-center gap-2 mb-1">
                <i class="bi bi-shield-lock-fill text-emerald-600"></i>
                <span><?= $isEn ? 'Roles & Dashboard Permissions' : 'الأدوار وصلاحيات لوحة التحكم' ?></span>
            </h1>
            <p class="text-xs text-slate-500 mb-0">
                <?= $isEn ? 'Manage administrative roles, grant access permissions to fund members, and regulate dashboard capabilities.' : 'إدارة أدوار النظام وتعيين صلاحيات لوحة التحكم لأعضاء الصندوق وتحديد ما يمكنهم القيام به.' ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= url('admin/roles/assign') ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-all">
                <i class="bi bi-person-plus-fill"></i>
                <span><?= $isEn ? 'Grant Member Access' : 'تعيين صلاحيات لعضو' ?></span>
            </a>
            <a href="<?= url('admin/roles/create') ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition-all">
                <i class="bi bi-plus-lg"></i>
                <span><?= $isEn ? 'Create New Role' : 'إضافة دور جديد' ?></span>
            </a>
        </div>
    </div>

    <!-- Executive Tab Bar -->
    <div x-data="{ activeTab: 'members' }">
        <div class="flex border-b border-slate-200 mb-6 gap-2">
            <button type="button" @click="activeTab = 'members'" :class="activeTab === 'members' ? 'border-emerald-600 text-emerald-600 font-black' : 'border-transparent text-slate-500 hover:text-slate-700 font-bold'" class="pb-3 px-4 border-b-2 text-sm transition-all flex items-center gap-2">
                <i class="bi bi-people-fill text-base"></i>
                <span><?= $isEn ? 'Authorized Members & Staff' : 'الأعضاء والمشرفون المفوّضون' ?></span>
                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-numeric bg-slate-100 text-slate-700"><?= count($adminMembers) ?></span>
            </button>
            <button type="button" @click="activeTab = 'roles'" :class="activeTab === 'roles' ? 'border-emerald-600 text-emerald-600 font-black' : 'border-transparent text-slate-500 hover:text-slate-700 font-bold'" class="pb-3 px-4 border-b-2 text-sm transition-all flex items-center gap-2">
                <i class="bi bi-key-fill text-base"></i>
                <span><?= $isEn ? 'Roles & Capability Templates' : 'الأدوار وقوالب الصلاحيات' ?></span>
                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-numeric bg-slate-100 text-slate-700"><?= count($roles) ?></span>
            </button>
        </div>

        <!-- TAB 1: Authorized Members & Staff -->
        <div x-show="activeTab === 'members'" x-transition class="space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="font-bold text-sm text-slate-800 flex items-center gap-2">
                        <i class="bi bi-shield-check text-emerald-600"></i>
                        <span><?= $isEn ? 'Fund Members with Dashboard Access' : 'أعضاء الصندوق الحاصلون على صلاحيات الإشراف ولوحة التحكم' ?></span>
                    </div>
                    <span class="text-xs text-slate-400">
                        <?= $isEn ? 'Only authorized members can log in to /admin' : 'الأعضاء المفوّضون فقط يمكنهم تسجيل الدخول إلى لوحة الإدارة' ?>
                    </span>
                </div>

                <?php if (empty($adminMembers)): ?>
                    <div class="text-center py-12 text-slate-400">
                        <i class="bi bi-people text-4xl mb-2 d-block"></i>
                        <p class="text-sm font-bold text-slate-600 mb-1"><?= $isEn ? 'No members have administrative access yet' : 'لم يتم تعيين صلاحيات إدارية لأي عضو حتى الآن' ?></p>
                        <p class="text-xs text-slate-400 mb-4"><?= $isEn ? 'You can appoint fund members to administrative roles like Treasurer or Loan Committee.' : 'يمكنك تعيين أعضاء من العائلة في مناصب إدارية كأمين الصندوق أو لجنة القروض.' ?></p>
                        <a href="<?= url('admin/roles/assign') ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold text-xs shadow-xs">
                            <i class="bi bi-person-plus-fill"></i>
                            <span><?= $isEn ? 'Grant First Member Access' : 'تعيين صلاحيات لأول عضو' ?></span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="w-full text-start text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold">
                                    <th class="py-3 px-4 text-start"><?= $isEn ? 'Member Name' : 'اسم العضو / المشرف' ?></th>
                                    <th class="py-3 px-4 text-start"><?= $isEn ? 'National ID / Mobile' : 'الهوية والجوال' ?></th>
                                    <th class="py-3 px-4 text-start"><?= $isEn ? 'Assigned Role' : 'الدور المعيّن' ?></th>
                                    <th class="py-3 px-4 text-center"><?= $isEn ? 'Effective Permissions' : 'عدد الصلاحيات' ?></th>
                                    <th class="py-3 px-4 text-center"><?= $isEn ? 'Account Status' : 'حالة الحساب' ?></th>
                                    <th class="py-3 px-4 text-end"><?= $isEn ? 'Actions' : 'إجراءات' ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($adminMembers as $am): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="py-3 px-4 font-bold text-slate-800">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 font-black flex items-center justify-center text-xs">
                                                    <?= mb_substr($am['member_name'], 0, 1) ?>
                                                </div>
                                                <div>
                                                    <div class="text-xs font-bold text-slate-900"><?= e($am['member_name']) ?></div>
                                                    <div class="text-[11px] text-slate-400 font-numeric"><?= e($am['member_email']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 font-numeric text-slate-600">
                                            <div><?= e($am['member_national_id'] ?? '-') ?></div>
                                            <div class="text-[11px] text-slate-400"><?= e($am['member_mobile'] ?? '-') ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($am['role_id']): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <i class="bi bi-shield-check"></i>
                                                    <span><?= e($isEn ? ($am['role_label_en'] ?: $am['role_label_ar']) : $am['role_label_ar']) ?></span>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                    <i class="bi bi-gear-wide"></i>
                                                    <span><?= $isEn ? 'Custom Permissions' : 'صلاحيات مخصصة' ?></span>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-center font-numeric font-bold text-slate-700">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-800 text-xs">
                                                <?= (int) ($am['permissions_count'] ?? 0) ?> <?= $isEn ? 'permissions' : 'صلاحية' ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <?php if ($am['member_status'] === 'active'): ?>
                                                <span class="badge-status status-active"><?= $isEn ? 'Active' : 'نشط' ?></span>
                                            <?php else: ?>
                                                <span class="badge-status status-cancelled"><?= $isEn ? 'Inactive' : 'معطل' ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-end">
                                            <div class="inline-flex items-center gap-1">
                                                <a href="<?= url('admin/roles/assign?member_id=' . $am['member_id']) ?>" class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors" title="<?= $isEn ? 'Edit Permissions' : 'تعديل الصلاحيات' ?>">
                                                    <i class="bi bi-pencil-square text-sm"></i>
                                                </a>
                                                <form method="post" action="<?= url('admin/roles/revoke/' . $am['member_id']) ?>" class="inline" onsubmit="return confirm('<?= $isEn ? 'Are you sure you want to revoke dashboard access for this member?' : 'هل أنت متأكد من رغبتك في سحب صلاحيات لوحة التحكم من هذا العضو؟' ?>')">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors bg-transparent border-0 cursor-pointer" title="<?= $isEn ? 'Revoke Access' : 'سحب الصلاحيات' ?>">
                                                        <i class="bi bi-x-circle text-sm"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 2: Roles & Capability Templates -->
        <div x-show="activeTab === 'roles'" x-transition class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($roles as $r): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col justify-between hover:border-slate-300 transition-all">
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div>
                                    <h3 class="font-black text-sm text-slate-900 mb-1 flex items-center gap-1.5">
                                        <span><?= e($isEn ? ($r['label_en'] ?: $r['label_ar']) : $r['label_ar']) ?></span>
                                        <?php if (!empty($r['is_system'])): ?>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600" title="<?= $isEn ? 'System Default Role' : 'دور افتراضي بالنظام' ?>">
                                                <?= $isEn ? 'System' : 'افتراضي' ?>
                                            </span>
                                        <?php endif; ?>
                                    </h3>
                                    <div class="font-mono text-[11px] text-slate-400"><?= e($r['name']) ?></div>
                                </div>
                                <div class="w-10 h-10 rounded-xl <?= $r['name'] === 'super_admin' ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700' ?> flex items-center justify-center text-lg">
                                    <i class="bi <?= $r['name'] === 'super_admin' ? 'bi-star-fill' : 'bi-shield-check' ?>"></i>
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 mb-4 line-clamp-2">
                                <?= e($r['description'] ?: ($isEn ? 'Standard role' : 'دور وظيفي مخصص')) ?>
                            </p>
                            <div class="flex items-center gap-3 text-xs text-slate-600 mb-4 pt-3 border-t border-slate-100">
                                <div class="flex items-center gap-1">
                                    <i class="bi bi-key text-emerald-600"></i>
                                    <span class="font-bold font-numeric"><?= (int) $r['permissions_count'] ?></span>
                                    <span><?= $isEn ? 'permissions' : 'صلاحية' ?></span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <i class="bi bi-people text-slate-400"></i>
                                    <span class="font-bold font-numeric"><?= (int) $r['users_count'] ?></span>
                                    <span><?= $isEn ? 'assigned' : 'مشرف/عضو' ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                            <a href="<?= url("admin/roles/{$r['id']}/edit") ?>" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                                <i class="bi bi-sliders"></i>
                                <span><?= $isEn ? 'Customize Permissions' : 'عرض وتعديل الصلاحيات' ?></span>
                            </a>
                            <?php if (empty($r['is_system'])): ?>
                                <form method="post" action="<?= url("admin/roles/{$r['id']}/delete") ?>" onsubmit="return confirm('<?= $isEn ? 'Are you sure you want to delete this role?' : 'هل أنت متأكد من حذف هذا الدور؟' ?>')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="p-1 rounded text-slate-400 hover:text-rose-600 transition-colors bg-transparent border-0 cursor-pointer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
