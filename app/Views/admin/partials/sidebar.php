<?php
$can = fn(string $section): bool => \App\Core\Auth::adminCan($section);
$pendingShareRequests = \App\Models\ShareRequest::count(['status' => 'pending']);
$pendingLoanRequests = \App\Models\LoanRequest::count(['status' => 'pending']);
$newMessages = \App\Models\ContactMessage::count(['status' => 'new']);
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <?php if ($sbLogo = site_logo_url()): ?>
            <img src="<?= e($sbLogo) ?>" alt="<?= e(site_name()) ?>" class="h-9 w-auto max-w-[42px] object-contain rounded-lg">
        <?php else: ?>
            <div class="logo-badge"><?= is_rtl() ? 'ص' : 'A' ?></div>
        <?php endif; ?>
        <div>
            <div class="brand-title"><?= e(site_name()) ?></div>
            <div class="brand-sub"><?= __('admin_portal') ?></div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <?php if ($can('dashboard')): ?>
        <a href="<?= url('admin/dashboard') ?>" class="sidebar-link <?= is_active('/admin/dashboard') ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span><?= __('overview') ?></span>
        </a>
        <?php endif; ?>

        <?php if ($can('members') || $can('shares') || $can('share_requests')): ?>
        <div class="sidebar-section-title"><?= is_rtl() ? 'المشتركون والأسهم' : 'Members & Shares' ?></div>
        <?php endif; ?>
        <?php if ($can('members')): ?>
        <a href="<?= url('admin/members') ?>" class="sidebar-link <?= is_active('/admin/members') ?>">
            <i class="bi bi-people-fill"></i>
            <span><?= __('manage_members') ?></span>
        </a>
        <?php endif; ?>
        <?php if ($can('shares')): ?>
        <a href="<?= url('admin/shares') ?>" class="sidebar-link <?= is_active('/admin/shares') ?>">
            <i class="bi bi-pie-chart-fill"></i>
            <span><?= __('manage_shares') ?></span>
        </a>
        <?php endif; ?>
        <?php if ($can('share_requests')): ?>
        <a href="<?= url('admin/share-requests') ?>" class="sidebar-link <?= is_active('/admin/share-requests') ?>">
            <i class="bi bi-arrow-left-right"></i>
            <span><?= __('manage_share_requests') ?></span>
            <?php if ($pendingShareRequests > 0): ?><span class="badge-count font-num"><?= $pendingShareRequests ?></span><?php endif; ?>
        </a>
        <?php endif; ?>

        <?php if ($can('subscriptions') || $can('founding')): ?>
        <div class="sidebar-section-title"><?= is_rtl() ? 'الاشتراكات والتأسيس' : 'Subscriptions & Capital' ?></div>
        <?php endif; ?>
        <?php if ($can('subscriptions')): ?>
        <a href="<?= url('admin/subscriptions') ?>" class="sidebar-link <?= is_active('/admin/subscriptions') ?>">
            <i class="bi bi-calendar2-check-fill"></i>
            <span><?= __('manage_subscriptions') ?></span>
        </a>
        <?php endif; ?>
        <?php if ($can('founding')): ?>
        <a href="<?= url('admin/founding') ?>" class="sidebar-link <?= is_active('/admin/founding') ?>">
            <i class="bi bi-bank2"></i>
            <span><?= __('manage_founding') ?></span>
        </a>
        <?php endif; ?>

        <?php if ($can('loan_requests') || $can('loans')): ?>
        <div class="sidebar-section-title"><?= is_rtl() ? 'القروض' : 'Loans Portfolio' ?></div>
        <?php endif; ?>
        <?php if ($can('loan_requests')): ?>
        <a href="<?= url('admin/loan-requests') ?>" class="sidebar-link <?= is_active('/admin/loan-requests') ?>">
            <i class="bi bi-file-earmark-text-fill"></i>
            <span><?= __('manage_loan_requests') ?></span>
            <?php if ($pendingLoanRequests > 0): ?><span class="badge-count font-num"><?= $pendingLoanRequests ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
        <?php if ($can('loans')): ?>
        <a href="<?= url('admin/loans') ?>" class="sidebar-link <?= is_active('/admin/loans') ?>">
            <i class="bi bi-cash-coin"></i>
            <span><?= __('manage_loans') ?></span>
        </a>
        <?php endif; ?>

        <?php if ($can('transactions')): ?>
        <div class="sidebar-section-title"><?= is_rtl() ? 'المالية' : 'Finance & Ledger' ?></div>
        <?php endif; ?>
        <?php if ($can('transactions')): ?>
        <a href="<?= url('admin/transactions') ?>" class="sidebar-link <?= is_active('/admin/transactions') ?>">
            <i class="bi bi-journal-text"></i>
            <span><?= __('manage_transactions') ?></span>
        </a>
        <?php endif; ?>

        <?php if ($can('notifications') || $can('messages') || $can('content') || $can('live_translate')): ?>
        <div class="sidebar-section-title"><?= is_rtl() ? 'التواصل والمحتوى' : 'Communications & Content' ?></div>
        <?php endif; ?>
        <?php if ($can('notifications')): ?>
        <a href="<?= url('admin/notifications') ?>" class="sidebar-link <?= is_active('/admin/notifications') ?>">
            <i class="bi bi-bell-fill"></i>
            <span><?= __('manage_notifications') ?></span>
        </a>
        <?php endif; ?>
        <?php if ($can('messages')): ?>
        <a href="<?= url('admin/messages') ?>" class="sidebar-link <?= is_active('/admin/messages') ?>">
            <i class="bi bi-chat-dots-fill"></i>
            <span><?= __('manage_messages') ?></span>
            <?php if ($newMessages > 0): ?><span class="badge-count font-num"><?= $newMessages ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
        <?php if ($can('content')): ?>
        <a href="<?= url('admin/content') ?>" class="sidebar-link <?= is_active('/admin/content') ?>">
            <i class="bi bi-file-earmark-richtext-fill"></i>
            <span><?= __('manage_content') ?></span>
        </a>
        <?php endif; ?>
        <?php if ($can('live_translate')): ?>
        <a href="<?= url('admin/live-translate') ?>" class="sidebar-link <?= is_active('/admin/live-translate') ?>">
            <i class="bi bi-translate"></i>
            <span><?= is_rtl() ? 'الترجمة الفورية' : 'Live Translate' ?></span>
        </a>
        <?php endif; ?>

        <?php if ($can('settings') || \App\Core\Auth::adminIsSuper()): ?>
        <div class="sidebar-section-title"><?= is_rtl() ? 'النظام' : 'System' ?></div>
        <?php endif; ?>
        <?php if ($can('settings')): ?>
        <a href="<?= url('admin/settings') ?>" class="sidebar-link <?= is_active('/admin/settings') ?>">
            <i class="bi bi-gear-fill"></i>
            <span><?= __('system_settings') ?></span>
        </a>
        <?php endif; ?>
        <?php if (\App\Core\Auth::adminIsSuper()): ?>
        <a href="<?= url('admin/admins') ?>" class="sidebar-link <?= is_active('/admin/admins') ?>">
            <i class="bi bi-person-gear"></i>
            <span><?= is_rtl() ? 'إدارة المشرفين' : 'Sub-admins' ?></span>
        </a>
        <?php endif; ?>
        <form method="post" action="<?= url('admin/logout') ?>" class="mt-auto pt-4">
            <?= csrf_field() ?>
            <button type="submit" class="sidebar-link text-rose-300 hover:text-rose-100 hover:bg-rose-500/10 w-full bg-transparent border-0 text-start cursor-pointer">
                <i class="bi bi-box-arrow-<?= is_rtl() ? 'left' : 'right' ?> text-rose-400"></i>
                <span><?= __('logout') ?></span>
            </button>
        </form>
    </nav>
</aside>
