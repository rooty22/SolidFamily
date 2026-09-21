<?php
$member = \App\Core\Auth::member();
$unread = \App\Models\Notification::forMember($member['id']);
$unreadCount = count($unread);
?>
<header class="topbar">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="sidebar-toggle-btn" aria-label="Toggle Sidebar"><i class="bi bi-list"></i></button>
        <div class="page-title"><?= e($pageTitle ?? '') ?></div>
    </div>
    <div class="topbar-actions d-flex align-items-center gap-2 sm:gap-3">
        <!-- Language Switcher -->
        <?php if (!is_single_language()): ?>
        <a href="<?= url('lang/' . (current_locale() === 'ar' ? 'en' : 'ar')) ?>" 
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-xs"
           title="<?= current_locale() === 'ar' ? 'Switch to English' : 'التحويل إلى العربية' ?>">
            <i class="bi bi-translate text-brand-600"></i>
            <span class="hidden sm:inline"><?= current_locale() === 'ar' ? 'English' : 'العربية' ?></span>
            <span class="sm:hidden"><?= current_locale() === 'ar' ? 'EN' : 'ع' ?></span>
        </a>
        <?php endif; ?>

        <!-- Notifications Bell -->
        <a href="<?= url('notifications') ?>" class="notif-bell" title="<?= __('notifications') ?>">
            <i class="bi bi-bell"></i>
            <?php if ($unreadCount > 0): ?><span class="dot"></span><?php endif; ?>
        </a>

        <!-- Member Profile Chip -->
        <a href="<?= url('profile') ?>" class="member-chip" title="<?= __('profile') ?>">
            <div class="avatar"><?= e(mb_substr($member['name'] ?? 'M', 0, 1)) ?></div>
            <span class="truncate max-w-[120px] sm:max-w-none"><?= e($member['name'] ?? '') ?></span>
        </a>
    </div>
</header>
