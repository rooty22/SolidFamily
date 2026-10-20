<?php
use App\Models\Admin;

$sections = Admin::sections();
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= is_rtl() ? 'إدارة المشرفين الفرعيين' : 'Sub-admins' ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= is_rtl() ? 'أضف مشرفين وحدّد صلاحيات كل واحد منهم، أو عطّل حسابه مؤقتًا دون حذفه.' : 'Add sub-admins, choose what each may do, or suspend an account without deleting it.' ?></p>
    </div>
    <a href="<?= url('admin/admins/create') ?>" class="btn btn-primary inline-flex items-center gap-2 whitespace-nowrap shrink-0 font-bold shadow-xs">
        <i class="bi bi-person-plus-fill"></i>
        <span><?= is_rtl() ? 'إضافة مشرف' : 'Add sub-admin' ?></span>
    </a>
</div>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th style="width:64px"><?= is_rtl() ? 'الصورة' : 'Photo' ?></th>
                <th><?= is_rtl() ? 'الاسم' : 'Name' ?></th>
                <th><?= is_rtl() ? 'البريد الإلكتروني' : 'Email' ?></th>
                <th><?= is_rtl() ? 'الصلاحيات' : 'Permissions' ?></th>
                <th><?= is_rtl() ? 'الحالة' : 'Status' ?></th>
                <th class="text-end"><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($admins as $a):
            $isSuper = !empty($a['is_super']);
            $perms = Admin::permissionsOf($a);
            $avatar = Admin::avatarUrl($a);
        ?>
            <tr>
                <td>
                    <?php if ($avatar): ?>
                        <img src="<?= e($avatar) ?>" alt="" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-slate-200 text-slate-600 fw-bold d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><?= e(mb_substr($a['name'], 0, 1)) ?></div>
                    <?php endif; ?>
                </td>
                <td class="fw-bold text-slate-900">
                    <?= e($a['name']) ?>
                    <?php if ((int) $a['id'] === $currentId): ?><span class="text-[11px] text-slate-400 fw-normal">(<?= is_rtl() ? 'أنت' : 'you' ?>)</span><?php endif; ?>
                </td>
                <td dir="ltr" class="text-start"><?= e($a['email']) ?></td>
                <td class="text-xs">
                    <?php if ($isSuper): ?>
                        <span class="badge-status badge-warning"><?= is_rtl() ? 'مدير رئيسي - كل الصلاحيات' : 'Main admin - full access' ?></span>
                    <?php elseif (empty($perms)): ?>
                        <span class="text-slate-400"><?= is_rtl() ? 'بدون صلاحيات' : 'No permissions' ?></span>
                    <?php else: ?>
                        <?php foreach ($perms as $key => $level): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg me-1 mb-1 font-bold <?= $level === 'manage' ? 'bg-emerald-50 text-emerald-700' : 'bg-sky-50 text-sky-700' ?>">
                                <?= e($sections[$key][is_rtl() ? 0 : 1]) ?>
                                <span class="opacity-75">· <?= $level === 'manage' ? (is_rtl() ? 'تحكم' : 'manage') : (is_rtl() ? 'عرض' : 'view') ?></span>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (($a['status'] ?? 'active') === 'active'): ?>
                        <span class="badge-status badge-success"><?= is_rtl() ? 'نشط' : 'Active' ?></span>
                    <?php else: ?>
                        <span class="badge-status badge-danger"><?= is_rtl() ? 'موقوف' : 'Suspended' ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <?php if (!$isSuper): ?>
                        <a href="<?= url('admin/admins/' . $a['id'] . '/edit') ?>" class="btn btn-sm btn-soft" title="<?= __('edit') ?>"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= url('admin/admins/' . $a['id'] . '/toggle-status') ?>" class="d-inline" data-confirm="<?= e(is_rtl() ? ($a['status'] === 'active' ? 'تعطيل حساب هذا المشرف مؤقتًا؟' : 'تفعيل حساب هذا المشرف؟') : 'Change this account status?') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-soft" title="<?= $a['status'] === 'active' ? __('deactivate') : __('activate') ?>">
                                <i class="bi bi-<?= $a['status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i>
                            </button>
                        </form>
                        <form method="post" action="<?= url('admin/admins/' . $a['id'] . '/delete') ?>" class="d-inline" data-confirm="<?= e(is_rtl() ? 'سيتم حذف حساب المشرف "' . $a['name'] . '" نهائيًا من النظام. هل أنت متأكد؟' : 'Delete this sub-admin permanently?') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-soft text-rose-600" title="<?= is_rtl() ? 'حذف' : 'Delete' ?>"><i class="bi bi-trash"></i></button>
                        </form>
                    <?php else: ?>
                        <span class="text-slate-300">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
