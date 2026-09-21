<?php
$authLogo = site_logo_url();
$authName = site_name();
$isEn = is_en();
?>
<div class="auth-card wide">
    <div class="text-center mb-6">
        <a href="<?= url('/') ?>" class="inline-flex items-center justify-center transition-transform hover:scale-105 mb-2">
            <?php if ($authLogo): ?>
                <img src="<?= e($authLogo) ?>" alt="<?= e($authName) ?>" class="h-14 w-auto max-w-[160px] object-contain rounded-xl shadow-sm">
            <?php else: ?>
                <div class="auth-logo"><?= is_rtl() ? 'ص' : 'F' ?></div>
            <?php endif; ?>
        </a>
        <h2 class="text-xl sm:text-2xl font-black text-slate-900 mb-1"><?= __('register') ?></h2>
        <p class="text-xs sm:text-sm text-slate-500"><?= __('register_subtitle') ?></p>
    </div>

    <?php $error = flash('error'); ?>
    <?php if ($error): ?>
        <div class="alert-modern alert-danger-modern mb-5">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('register') ?>" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Section 1: Personal Info -->
        <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-4 sm:p-5">
            <div class="flex items-center gap-2 text-slate-800 font-bold text-xs sm:text-sm mb-3 pb-2 border-b border-slate-200/60">
                <i class="bi bi-person-lines-fill text-brand-600"></i>
                <span><?= __('personal_contact_info') ?></span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="form-label text-xs font-bold text-slate-700 mb-1"><?= __('full_name_quad') ?> <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" class="form-control text-xs sm:text-sm" value="<?= old('name') ?>" placeholder="<?= __('full_name_placeholder') ?>" required>
                </div>
                <div>
                    <label class="form-label text-xs font-bold text-slate-700 mb-1"><?= __('mobile_number') ?> <span class="text-rose-500">*</span></label>
                    <input type="text" name="mobile" class="form-control text-xs sm:text-sm font-numeric" dir="ltr" value="<?= old('mobile') ?>" placeholder="<?= __('mobile_placeholder') ?>" required>
                </div>
                <div>
                    <label class="form-label text-xs font-bold text-slate-700 mb-1"><?= __('email_address') ?> <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" class="form-control text-xs sm:text-sm" dir="ltr" value="<?= old('email') ?>" placeholder="<?= __('email_placeholder') ?>" required>
                </div>
                <div>
                    <label class="form-label text-xs font-bold text-slate-700 mb-1"><?= __('national_id') ?> <span class="text-rose-500">*</span></label>
                    <input type="text" name="national_id" class="form-control text-xs sm:text-sm font-numeric" dir="ltr" value="<?= old('national_id') ?>" placeholder="<?= __('national_id_placeholder') ?>" required>
                </div>
                <div>
                    <label class="form-label text-xs font-bold text-slate-700 mb-1"><?= __('birth_date') ?></label>
                    <input type="date" name="birth_date" required class="form-control text-xs sm:text-sm font-numeric" value="<?= old('birth_date') ?>">
                </div>
                <div>
                    <label class="form-label text-xs font-bold text-slate-700 mb-1"><?= __('national_address_city') ?></label>
                    <input type="text" name="national_address" required class="form-control text-xs sm:text-sm" value="<?= old('national_address') ?>" placeholder="<?= __('address_placeholder') ?>">
                </div>
            </div>
        </div>

        <!-- Section 2: Bank Info -->
        <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-4 sm:p-5">
            <div class="flex items-center gap-2 text-slate-800 font-bold text-xs sm:text-sm mb-3 pb-2 border-b border-slate-200/60">
                <i class="bi bi-bank text-brand-600"></i>
                <span><?= __('bank_info_title') ?></span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                <div>
                    <label class="form-label text-xs font-bold text-slate-700 mb-1"><?= __('bank_name') ?></label>
                    <input type="text" name="bank_name" required class="form-control text-xs sm:text-sm" value="<?= old('bank_name') ?>" placeholder="<?= __('bank_name_placeholder') ?>">
                </div>
                <div>
                    <label class="form-label text-xs font-bold text-slate-700 mb-1"><?= __('bank_account_number') ?></label>
                    <input type="text" name="bank_account_number" required class="form-control text-xs sm:text-sm font-numeric" dir="ltr" value="<?= old('bank_account_number') ?>" placeholder="<?= __('account_number_placeholder') ?>">
                </div>
                <div>
                    <label class="form-label text-xs font-bold text-slate-700 mb-1"><?= __('iban') ?></label>
                    <input type="text" name="iban" required class="form-control text-xs sm:text-sm font-numeric" dir="ltr" value="<?= old('iban') ?>" placeholder="<?= __('iban_placeholder') ?>">
                </div>
            </div>
        </div>

        <!-- Section 3: Password Security -->
        <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-4 sm:p-5">
            <div class="flex items-center gap-2 text-slate-800 font-bold text-xs sm:text-sm mb-3 pb-2 border-b border-slate-200/60">
                <i class="bi bi-shield-lock text-brand-600"></i>
                <span><?= __('security_password') ?></span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="form-label text-xs font-bold text-slate-700 mb-1"><?= __('password') ?> <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" class="form-control text-xs sm:text-sm" minlength="8" placeholder="<?= __('password_min_chars') ?>" required>
                </div>
                <div>
                    <label class="form-label text-xs font-bold text-slate-700 mb-1"><?= __('confirm_password') ?> <span class="text-rose-500">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control text-xs sm:text-sm" minlength="8" placeholder="<?= __('retype_password') ?>" required>
                </div>
            </div>
        </div>

        <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-500 hover:to-brand-600 text-white font-bold text-sm shadow-md shadow-brand-600/30 flex items-center justify-center gap-2 transition-all">
            <i class="bi bi-check-circle-fill"></i>
            <span><?= __('confirm_create_account') ?></span>
        </button>

        <p class="text-center text-xs text-slate-500 mt-4">
            <?= __('already_have_account') ?> 
            <a href="<?= url('login') ?>" class="font-bold text-brand-600 hover:text-brand-700 transition-colors">
                <?= __('sign_in_now') ?>
            </a>
        </p>
    </form>
</div>
