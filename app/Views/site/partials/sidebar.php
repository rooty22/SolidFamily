<?php
$member = \App\Core\Auth::member();
$isAdminMember = \App\Models\Member::isAdmin($member);
$pendingShare = \App\Models\ShareRequest::count(['member_id' => $member['id'], 'status' => 'pending']);
$pendingLoan = !$isAdminMember ? \App\Models\LoanRequest::count(['member_id' => $member['id'], 'status' => 'pending']) : 0;
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="logo-badge"><?= is_rtl() ? 'ص' : 'F' ?></div>
        <div>
            <div class="brand-title"><?= __('brand_name') ?></div>
            <div class="brand-sub"><?= __('member_portal') ?></div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= url('home') ?>" class="sidebar-link <?= is_active('/home') ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span><?= __('home') ?></span>
        </a>
        <a href="<?= url('shares') ?>" class="sidebar-link <?= is_active('/shares') ?>">
            <i class="bi bi-pie-chart-fill"></i>
            <span><?= __('shares_and_subscriptions') ?></span>
        </a>
        <a href="<?= url('share-requests') ?>" class="sidebar-link <?= is_active('/share-requests') ?>">
            <i class="bi bi-arrow-left-right"></i>
            <span><?= __('share_requests') ?></span>
            <?php if ($pendingShare > 0): ?><span class="badge-count font-num"><?= $pendingShare ?></span><?php endif; ?>
        </a>
        <a href="<?= url('founding') ?>" class="sidebar-link <?= is_active('/founding') ?>">
            <i class="bi bi-bank2"></i>
            <span><?= __('founding_amount') ?></span>
        </a>
        <?php if (!$isAdminMember): ?>
        <a href="<?= url('loans') ?>" class="sidebar-link <?= is_active('/loans') ?>">
            <i class="bi bi-cash-coin"></i>
            <span><?= __('loans_title') ?></span>
            <?php if ($pendingLoan > 0): ?><span class="badge-count font-num"><?= $pendingLoan ?></span><?php endif; ?>
        </a>
        <?php else: ?>
        <a href="<?= url('admin/dashboard') ?>" class="sidebar-link text-amber-300 hover:text-amber-100 hover:bg-amber-500/10">
            <i class="bi bi-shield-check text-amber-400"></i>
            <span><?= is_rtl() ? 'بوابة إدارة الصندوق' : 'Admin Portal' ?></span>
        </a>
        <?php endif; ?>
        <a href="<?= url('notifications') ?>" class="sidebar-link <?= is_active('/notifications') ?>">
            <i class="bi bi-bell-fill"></i>
            <span><?= __('notifications') ?></span>
        </a>
        <a href="<?= url('profile') ?>" class="sidebar-link <?= is_active('/profile') ?>">
            <i class="bi bi-person-fill"></i>
            <span><?= __('profile') ?></span>
        </a>
        <a href="<?= url('settings') ?>" class="sidebar-link <?= is_active('/settings') ?>">
            <i class="bi bi-gear-fill"></i>
            <span><?= __('settings_support') ?></span>
        </a>

        <form method="post" action="<?= url('logout') ?>" class="mt-auto pt-4">
            <?= csrf_field() ?>
            <button type="submit" class="sidebar-link text-rose-300 hover:text-rose-100 hover:bg-rose-500/10 w-full bg-transparent border-0 text-start cursor-pointer">
                <i class="bi bi-box-arrow-<?= is_rtl() ? 'left' : 'right' ?> text-rose-400"></i>
                <span><?= __('logout') ?></span>
            </button>
        </form>
    </nav>
</aside>
