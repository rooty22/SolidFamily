<?php
$adminLogo = site_logo_url();
$siteName = site_name();
$isEn = is_en();
?>
<div class="auth-card" x-data="{ showPass: false }">
    <div class="text-center mb-6">
        <a href="<?= url('/') ?>" class="inline-flex items-center justify-center transition-transform hover:scale-105 mb-2">
            <?php if ($adminLogo): ?>
                <img src="<?= e($adminLogo) ?>" alt="<?= e($siteName) ?>" class="h-14 w-auto max-w-[160px] object-contain rounded-xl shadow-sm">
            <?php else: ?>
                <div class="auth-logo">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
            <?php endif; ?>
        </a>
        <h3 class="text-2xl font-black text-slate-900 mb-1"><?= __('admin_portal') ?></h3>
        <p class="text-slate-500 text-sm font-medium"><?= e($siteName) ?></p>
    </div>

    <?php $error = flash('error'); $success = flash('success'); ?>
    <?php if ($error): ?>
        <div class="alert-modern alert-danger-modern mb-4">
            <i class="bi bi-exclamation-circle-fill text-lg"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert-modern alert-success-modern mb-4">
            <i class="bi bi-check-circle-fill text-lg"></i>
            <span><?= e($success) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('admin/login') ?>" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="form-label text-slate-700 font-bold block mb-1.5 text-sm"><?= $isEn ? 'Admin Email Address' : 'البريد الإلكتروني للإدارة' ?></label>
            <div class="relative flex items-center">
                <span class="absolute inset-y-0 <?= is_rtl() ? 'right-0' : 'left-0' ?> w-11 flex items-center justify-center pointer-events-none text-slate-400">
                    <i class="bi bi-envelope text-lg"></i>
                </span>
                <input type="email" name="email" class="form-control form-control-icon-start" placeholder="admin@sandouk.local" value="<?= old('email') ?>" required autofocus>
            </div>
        </div>

        <div>
            <div class="flex justify-between items-center mb-1.5">
                <label class="form-label text-slate-700 font-bold text-sm mb-0"><?= __('password') ?></label>
                <a href="<?= url('admin/forgot-password') ?>" class="text-xs font-bold text-sky-700 hover:text-sky-800"><?= __('forgot_password') ?></a>
            </div>
            <div class="relative flex items-center">
                <span class="absolute inset-y-0 <?= is_rtl() ? 'right-0' : 'left-0' ?> w-11 flex items-center justify-center pointer-events-none text-slate-400">
                    <i class="bi bi-lock text-lg"></i>
                </span>
                <input :type="showPass ? 'text' : 'password'" name="password" class="form-control form-control-icon-both" placeholder="••••••••" required>
                <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 <?= is_rtl() ? 'left-0' : 'right-0' ?> w-11 flex items-center justify-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                    <i class="bi" :class="showPass ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-3 text-base shadow-md mt-2">
            <span><?= $isEn ? 'Sign in to Console' : 'تسجيل دخول المسؤول' ?></span>
            <i class="bi <?= is_rtl() ? 'bi-box-arrow-in-left' : 'bi-box-arrow-in-right' ?>"></i>
        </button>

        <div class="pt-4 border-t border-slate-100 text-center space-y-2">
            <p class="text-xs text-slate-500">
                <?= $isEn ? 'Looking for the Member Portal?' : 'هل أنت مشترك وتبحث عن حسابك؟' ?>
                <a href="<?= url('login') ?>" class="font-bold text-sky-700 hover:text-sky-800 underline ms-1">
                    <?= $isEn ? 'Member Login Here' : 'تسجيل دخول المشتركين هنا' ?>
                </a>
            </p>
            <div>
                <a href="<?= url('/') ?>" class="inline-flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="bi bi-house-door"></i>
                    <span><?= __('back_to_home') ?></span>
                </a>
            </div>
        </div>
    </form>
</div>
