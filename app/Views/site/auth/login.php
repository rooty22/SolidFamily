<?php
$authLogo = site_logo_url();
$authName = site_name();
$authSlogan = site_slogan();
$isEn = is_en();
?>
<div class="auth-card" x-data="{ showPass: false }">
    <div class="text-center mb-6">
        <a href="<?= url('/') ?>" class="inline-flex items-center justify-center transition-transform hover:scale-105 mb-2">
            <?php if ($authLogo): ?>
                <img src="<?= e($authLogo) ?>" alt="<?= e($authName) ?>" class="h-14 w-auto max-w-[160px] object-contain rounded-xl shadow-sm">
            <?php else: ?>
                <div class="auth-logo"><?= is_rtl() ? 'ص' : 'F' ?></div>
            <?php endif; ?>
        </a>
        <h3 class="text-2xl font-black text-slate-900 mb-1"><?= e($authName) ?></h3>
        <p class="text-slate-500 text-sm font-medium"><?= __('login_subtitle') ?></p>
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

    <form method="post" action="<?= url('login') ?>" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="form-label text-slate-700 font-bold block mb-1.5 text-sm"><?= __('national_id') ?></label>
            <div class="relative flex items-center">
                <span class="absolute inset-y-0 <?= is_rtl() ? 'right-0' : 'left-0' ?> w-11 flex items-center justify-center pointer-events-none text-slate-400">
                    <i class="bi bi-person-vcard text-lg"></i>
                </span>
                <input type="text" name="national_id" class="form-control form-control-icon-start font-numeric" placeholder="10xxxxxxxx" value="<?= old('national_id') ?>" maxlength="10" inputmode="numeric" pattern="[12][0-9]{9}" title="<?= is_rtl() ? 'رقم الهوية الوطنية (10 أرقام تبدأ بـ 1 أو 2)' : 'Saudi National ID (10 digits starting with 1 or 2)' ?>" required autofocus>
            </div>
            <p class="text-[11px] text-slate-500 mt-1.5 mb-0 flex items-center gap-1.5">
                <i class="bi bi-shield-check text-emerald-600"></i>
                <span><?= is_rtl() ? 'تسجيل الدخول متاح برقم الهوية الوطنية فقط (لا يمكن استخدام البريد أو الهاتف).' : 'Login is available using Saudi National ID only (email or phone not allowed).' ?></span>
            </p>
        </div>

        <div>
            <div class="flex justify-between items-center mb-1.5">
                <label class="form-label text-slate-700 font-bold text-sm mb-0"><?= __('password') ?></label>
                <a href="<?= url('forgot-password') ?>" class="text-xs font-bold text-emerald-700 hover:text-emerald-800"><?= __('forgot_password') ?></a>
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

        <button type="submit" class="btn btn-primary w-100 py-3 text-base shadow-glow mt-2">
            <span><?= __('login') ?></span>
            <i class="bi <?= is_rtl() ? 'bi-arrow-left' : 'bi-arrow-right' ?>"></i>
        </button>

        <div class="pt-4 border-t border-slate-100 text-center space-y-2.5">
            <p class="text-slate-600 text-sm mb-0"><?= __('no_account_prompt') ?> <a href="<?= url('register') ?>" class="font-bold text-emerald-700 hover:underline"><?= __('request_new_membership') ?></a></p>
            <div class="flex items-center justify-center text-xs font-bold text-slate-400">
                <a href="<?= url('/') ?>" class="hover:text-slate-600 transition-colors inline-flex items-center gap-1">
                    <i class="bi bi-house-door"></i>
                    <span><?= __('back_to_home') ?></span>
                </a>
            </div>
        </div>
    </form>
</div>
