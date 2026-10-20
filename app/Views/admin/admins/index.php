<?php
use App\Models\Admin;

$sections = Admin::sections();
$lang = is_rtl() ? 0 : 1;
$visibleChips = 3;
?>
<style>
    .adm-user { display: flex; align-items: center; gap: 12px; min-width: 220px; }
    .adm-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; flex-shrink: 0; box-shadow: 0 0 0 2px #fff, 0 0 0 3px #e2e8f0; }
    .adm-avatar-fallback { display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #e0e7ff, #ede9fe); color: #4f46e5; font-weight: 800; font-size: 16px; }
    .adm-name { font-weight: 800; color: #0f172a; line-height: 1.3; }
    .adm-email { font-size: 12px; color: #64748b; direction: ltr; text-align: start; unicode-bidi: plaintext; }
    .adm-chips { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    .adm-chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700; white-space: nowrap; }
    .adm-chip i { font-size: 12px; }
    .adm-chip.manage { background: #ecfdf5; color: #047857; }
    .adm-chip.view { background: #f0f9ff; color: #0369a1; }
    .adm-chip.more { background: #f1f5f9; color: #475569; cursor: default; }
    .adm-chip.super { background: #fffbeb; color: #b45309; }
    .adm-chip.none { background: #f8fafc; color: #94a3b8; }
    .adm-actions { display: inline-flex; gap: 6px; justify-content: flex-end; }
    .adm-actions .btn { width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; }
    .adm-actions form { margin: 0; }
    .adm-count { font-size: 11px; color: #94a3b8; font-weight: 700; margin-top: 4px; }
    .adm-table td { vertical-align: middle; }
</style>

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
    <table class="table-modern adm-table">
        <thead>
            <tr>
                <th><?= is_rtl() ? 'المشرف' : 'Admin' ?></th>
                <th><?= is_rtl() ? 'الصلاحيات' : 'Permissions' ?></th>
                <th style="width:110px"><?= is_rtl() ? 'الحالة' : 'Status' ?></th>
                <th class="text-end" style="width:140px"><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($admins as $a):
            $isSuper = !empty($a['is_super']);
            $perms = Admin::permissionsOf($a);
            $avatar = Admin::avatarUrl($a);
            $shown = array_slice($perms, 0, $visibleChips, true);
            $rest = array_slice($perms, $visibleChips, null, true);
            $restTitle = implode('، ', array_map(fn($k, $lv) => $sections[$k][$lang] . ' (' . ($lv === 'manage' ? (is_rtl() ? 'تحكم' : 'manage') : (is_rtl() ? 'عرض' : 'view')) . ')', array_keys($rest), $rest));
        ?>
            <tr>
                <td>
                    <div class="adm-user">
                        <?php if ($avatar): ?>
                            <img src="<?= e($avatar) ?>" alt="" class="adm-avatar">
                        <?php else: ?>
                            <div class="adm-avatar adm-avatar-fallback"><?= e(mb_substr($a['name'], 0, 1)) ?></div>
                        <?php endif; ?>
                        <div>
                            <div class="adm-name">
                                <?= e($a['name']) ?>
                                <?php if ((int) $a['id'] === $currentId): ?><span class="text-[11px] text-slate-400 fw-normal">(<?= is_rtl() ? 'أنت' : 'you' ?>)</span><?php endif; ?>
                            </div>
                            <div class="adm-email"><?= e($a['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="adm-chips">
                        <?php if ($isSuper): ?>
                            <span class="adm-chip super"><i class="bi bi-shield-check"></i><?= is_rtl() ? 'مدير رئيسي - كل الصلاحيات' : 'Main admin - full access' ?></span>
                        <?php elseif (empty($perms)): ?>
                            <span class="adm-chip none"><?= is_rtl() ? 'بدون صلاحيات' : 'No permissions' ?></span>
                        <?php else: ?>
                            <?php foreach ($shown as $key => $level): ?>
                                <span class="adm-chip <?= $level ?>">
                                    <i class="bi bi-<?= e($sections[$key][3]) ?>"></i><?= e($sections[$key][$lang]) ?>
                                    <i class="bi bi-<?= $level === 'manage' ? 'pencil-square' : 'eye' ?> opacity-75" title="<?= $level === 'manage' ? (is_rtl() ? 'تحكم كامل' : 'Full control') : (is_rtl() ? 'عرض فقط' : 'View only') ?>"></i>
                                </span>
                            <?php endforeach; ?>
                            <?php if (!empty($rest)): ?>
                                <span class="adm-chip more" title="<?= e($restTitle) ?>">+<?= count($rest) ?> <?= is_rtl() ? 'أخرى' : 'more' ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!$isSuper && !empty($perms)): ?>
                        <div class="adm-count"><?= is_rtl() ? count($perms) . ' من ' . count($sections) . ' قسم' : count($perms) . ' of ' . count($sections) . ' sections' ?></div>
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
                    <div class="adm-actions">
                        <a href="<?= url('admin/admins/' . $a['id'] . '/edit') ?>" class="btn btn-soft" title="<?= __('edit') ?>"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= url('admin/admins/' . $a['id'] . '/toggle-status') ?>" data-confirm="<?= e(is_rtl() ? ($a['status'] === 'active' ? 'تعطيل حساب هذا المشرف مؤقتًا؟' : 'تفعيل حساب هذا المشرف؟') : 'Change this account status?') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-soft" title="<?= $a['status'] === 'active' ? __('deactivate') : __('activate') ?>">
                                <i class="bi bi-<?= $a['status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i>
                            </button>
                        </form>
                        <form method="post" action="<?= url('admin/admins/' . $a['id'] . '/delete') ?>" data-confirm="<?= e(is_rtl() ? 'سيتم حذف حساب المشرف "' . $a['name'] . '" نهائيًا من النظام. هل أنت متأكد؟' : 'Delete this sub-admin permanently?') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-soft text-rose-600" title="<?= is_rtl() ? 'حذف' : 'Delete' ?>"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                    <?php else: ?>
                        <span class="text-slate-300">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
