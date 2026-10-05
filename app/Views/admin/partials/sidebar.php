<?php
$pendingShareRequests = admin_can('share_requests.view') ? \App\Models\ShareRequest::count(['status' => 'pending']) : 0;
$pendingLoanRequests = admin_can('loan_requests.view') ? \App\Models\LoanRequest::count(['status' => 'pending']) : 0;
$newMessages = admin_can('messages.view') ? \App\Models\ContactMessage::count(['status' => 'new']) : 0;
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
        <?php if (admin_can('dashboard.view')): ?>
            <a href="<?= url('admin/dashboard') ?>" class="sidebar-link <?= is_active('/admin/dashboard') ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span><?= __('overview') ?></span>
            </a>
        <?php endif; ?>

        <?php if (admin_can('members.view') || admin_can('shares.view') || admin_can('share_requests.view')): ?>
            <div class="sidebar-section-title"><?= is_rtl() ? 'المشتركون والأسهم' : 'Members & Shares' ?></div>
            <?php if (admin_can('members.view')): ?>
                <a href="<?= url('admin/members') ?>" class="sidebar-link <?= is_active('/admin/members') ?>">
                    <i class="bi bi-people-fill"></i>
                    <span><?= __('manage_members') ?></span>
                </a>
            <?php endif; ?>
            <?php if (admin_can('shares.view')): ?>
                <a href="<?= url('admin/shares') ?>" class="sidebar-link <?= is_active('/admin/shares') ?>">
                    <i class="bi bi-pie-chart-fill"></i>
                    <span><?= __('manage_shares') ?></span>
                </a>
            <?php endif; ?>
            <?php if (admin_can('share_requests.view')): ?>
                <a href="<?= url('admin/share-requests') ?>" class="sidebar-link <?= is_active('/admin/share-requests') ?>">
                    <i class="bi bi-arrow-left-right"></i>
                    <span><?= __('manage_share_requests') ?></span>
                    <?php if ($pendingShareRequests > 0): ?><span class="badge-count font-num"><?= $pendingShareRequests ?></span><?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (admin_can('subscriptions.view') || admin_can('founding.view')): ?>
            <div class="sidebar-section-title"><?= is_rtl() ? 'الاشتراكات والتأسيس' : 'Subscriptions & Capital' ?></div>
            <?php if (admin_can('subscriptions.view')): ?>
                <a href="<?= url('admin/subscriptions') ?>" class="sidebar-link <?= is_active('/admin/subscriptions') ?>">
                    <i class="bi bi-calendar2-check-fill"></i>
                    <span><?= __('manage_subscriptions') ?></span>
                </a>
            <?php endif; ?>
            <?php if (admin_can('founding.view')): ?>
                <a href="<?= url('admin/founding') ?>" class="sidebar-link <?= is_active('/admin/founding') ?>">
                    <i class="bi bi-bank2"></i>
                    <span><?= __('manage_founding') ?></span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (admin_can('loan_requests.view') || admin_can('loans.view')): ?>
            <div class="sidebar-section-title"><?= is_rtl() ? 'القروض' : 'Loans Portfolio' ?></div>
            <?php if (admin_can('loan_requests.view')): ?>
                <a href="<?= url('admin/loan-requests') ?>" class="sidebar-link <?= is_active('/admin/loan-requests') ?>">
                    <i class="bi bi-file-earmark-text-fill"></i>
                    <span><?= __('manage_loan_requests') ?></span>
                    <?php if ($pendingLoanRequests > 0): ?><span class="badge-count font-num"><?= $pendingLoanRequests ?></span><?php endif; ?>
                </a>
            <?php endif; ?>
            <?php if (admin_can('loans.view')): ?>
                <a href="<?= url('admin/loans') ?>" class="sidebar-link <?= is_active('/admin/loans') ?>">
                    <i class="bi bi-cash-coin"></i>
                    <span><?= __('manage_loans') ?></span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (admin_can('transactions.view')): ?>
            <div class="sidebar-section-title"><?= is_rtl() ? 'المالية' : 'Finance & Ledger' ?></div>
            <?php if (admin_can('transactions.view')): ?>
                <a href="<?= url('admin/transactions') ?>" class="sidebar-link <?= is_active('/admin/transactions') ?>">
                    <i class="bi bi-journal-text"></i>
                    <span><?= __('manage_transactions') ?></span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (admin_can('notifications.view') || admin_can('messages.view') || admin_can('content.manage') || admin_can('live_translate.manage')): ?>
            <div class="sidebar-section-title"><?= is_rtl() ? 'التواصل والمحتوى' : 'Communications & Content' ?></div>
            <?php if (admin_can('notifications.view')): ?>
                <a href="<?= url('admin/notifications') ?>" class="sidebar-link <?= is_active('/admin/notifications') ?>">
                    <i class="bi bi-bell-fill"></i>
                    <span><?= __('manage_notifications') ?></span>
                </a>
            <?php endif; ?>
            <?php if (admin_can('messages.view')): ?>
                <a href="<?= url('admin/messages') ?>" class="sidebar-link <?= is_active('/admin/messages') ?>">
                    <i class="bi bi-chat-dots-fill"></i>
                    <span><?= __('manage_messages') ?></span>
                    <?php if ($newMessages > 0): ?><span class="badge-count font-num"><?= $newMessages ?></span><?php endif; ?>
                </a>
            <?php endif; ?>
            <?php if (admin_can('content.manage')): ?>
                <a href="<?= url('admin/content') ?>" class="sidebar-link <?= is_active('/admin/content') ?>">
                    <i class="bi bi-file-earmark-richtext-fill"></i>
                    <span><?= __('manage_content') ?></span>
                </a>
            <?php endif; ?>
            <?php if (admin_can('live_translate.manage')): ?>
                <a href="<?= url('admin/live-translate') ?>" class="sidebar-link <?= is_active('/admin/live-translate') ?>">
                    <i class="bi bi-translate"></i>
                    <span><?= is_rtl() ? 'الترجمة الفورية' : 'Live Translate' ?></span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (admin_can('roles.manage') || admin_can('settings.manage')): ?>
            <div class="sidebar-section-title"><?= is_rtl() ? 'النظام والصلاحيات' : 'System & Security' ?></div>
            <?php if (admin_can('roles.manage')): ?>
                <a href="<?= url('admin/roles') ?>" class="sidebar-link <?= is_active('/admin/roles') ?>">
                    <i class="bi bi-shield-lock-fill text-emerald-400"></i>
                    <span><?= is_rtl() ? 'الأدوار والصلاحيات' : 'Roles & Permissions' ?></span>
                </a>
            <?php endif; ?>
            <?php if (admin_can('settings.manage')): ?>
                <a href="<?= url('admin/settings') ?>" class="sidebar-link <?= is_active('/admin/settings') ?>">
                    <i class="bi bi-gear-fill"></i>
                    <span><?= __('system_settings') ?></span>
                </a>
            <?php endif; ?>
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
