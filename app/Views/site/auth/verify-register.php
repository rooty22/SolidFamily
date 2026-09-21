<?php
$isEn = is_en();
$demoCode = \App\Core\Session::get('demo_otp_code');
$isDemo = otp_is_demo();
$cooldown = otp_resend_seconds();
?>
<div class="auth-card" style="max-width: 480px; margin: 0 auto;">
    <div class="text-center">
        <div class="auth-logo"><i class="bi bi-shield-check"></i></div>
        <h4 class="fw-bold mb-1"><?= __('verify_mobile_title') ?></h4>
        <p class="text-muted mb-3 font-numeric" style="font-size:13.5px;"><?= __('code_sent_to', ['mobile' => e($mobile)]) ?></p>
    </div>

    <?php $success = flash('success'); $error = flash('error'); ?>
    <?php if ($success): ?><div class="alert-modern alert-success-modern mb-3"><i class="bi bi-info-circle-fill"></i> <?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-modern alert-danger-modern mb-3"><i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?></div><?php endif; ?>

    <?php if ($isDemo && $demoCode): ?>
        <div class="otp-demo-card flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-full bg-emerald-200 text-emerald-900 font-bold text-[10.5px]"><?= __('demo_mode') ?></span>
                <span class="text-xs text-emerald-900 font-medium"><?= __('verification_code') ?>: <strong class="font-numeric text-base tracking-widest text-emerald-800"><?= e($demoCode) ?></strong></span>
            </div>
            <button type="button" onclick="autoFillOtp('<?= e($demoCode) ?>')" class="otp-demo-chip" title="<?= __('click_to_autofill_otp') ?>">
                <i class="bi bi-lightning-charge-fill text-amber-500 text-xs"></i>
                <span class="text-xs font-bold"><?= __('autofill_code') ?></span>
            </button>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('register/verify') ?>">
        <?= csrf_field() ?>
        <div class="otp-inputs" dir="ltr">
            <?php for ($i = 0; $i < otp_length(); $i++): ?>
                <input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1" class="otp-digit" required>
            <?php endfor; ?>
        </div>
        <input type="hidden" name="code" id="otp-code">
        <button type="submit" class="btn btn-primary w-100 py-3 font-bold shadow-md shadow-brand-500/20" onclick="document.getElementById('otp-code').value = Array.from(document.querySelectorAll('.otp-inputs input')).map(i => i.value).join('')">
            <?= __('confirm_and_complete_register') ?>
        </button>
    </form>

    <form method="post" action="<?= url('register/resend') ?>" class="mt-3">
        <?= csrf_field() ?>
        <button type="submit" id="resend-btn" data-cooldown="<?= $cooldown ?>" class="btn btn-resend btn-soft w-100 py-2.5 is-locked" disabled>
            <i class="bi bi-clock-history"></i>
            <span><?= __('resend_code_in_seconds', ['sec' => '<strong id="resend-timer" class="font-numeric">' . $cooldown . '</strong>']) ?></span>
        </button>
    </form>
</div>
