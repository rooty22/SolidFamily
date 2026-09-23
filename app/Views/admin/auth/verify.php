<?php
$demoCode = \App\Core\Session::get('demo_otp_code');
$isDemo = otp_is_demo();
$cooldown = otp_cooldown_seconds($email, 'admin_reset');
$isLocked = $cooldown > otp_resend_seconds();
?>
<div class="auth-card" style="max-width: 480px; margin: 0 auto;">
    <div class="text-center">
        <div class="auth-logo"><i class="bi bi-shield-lock-fill"></i></div>
        <h4 class="fw-bold mb-1">التحقق من الرمز</h4>
        <p class="text-muted mb-3" style="font-size:13.5px;">تم إرسال رمز التحقق إلى <?= e($email) ?></p>
    </div>

    <?php $success = flash('success'); $error = flash('error'); ?>
    <?php if ($success): ?><div class="alert-modern alert-success-modern mb-3"><i class="bi bi-info-circle-fill"></i> <?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-modern alert-danger-modern mb-3"><i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?></div><?php endif; ?>

    <?php if ($isLocked): ?>
        <div class="alert-modern alert-danger-modern mb-3"><i class="bi bi-shield-lock-fill"></i> <?= __('otp_security_locked') ?></div>
    <?php endif; ?>

    <?php if ($isDemo && $demoCode && !$isLocked): ?>
        <div class="otp-demo-card flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-full bg-sky-200 text-sky-900 font-bold text-[10.5px]">وضع تجريبي</span>
                <span class="text-xs text-sky-900 font-medium">رمز التحقق: <strong class="font-numeric text-base tracking-widest text-sky-800"><?= e($demoCode) ?></strong></span>
            </div>
            <button type="button" onclick="autoFillOtp('<?= e($demoCode) ?>')" class="otp-demo-chip" title="انقر لتعبئة الرمز تلقائياً">
                <i class="bi bi-lightning-charge-fill text-amber-500 text-xs"></i>
                <span class="text-xs font-bold">تعبئة الرمز</span>
            </button>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('admin/forgot-password/verify') ?>">
        <?= csrf_field() ?>
        <div class="otp-inputs" dir="ltr" <?= $isLocked ? 'data-locked="1"' : '' ?>>
            <?php for ($i = 0; $i < otp_length(); $i++): ?>
                <input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1" class="otp-digit" required <?= $isLocked ? 'disabled' : '' ?>>
            <?php endfor; ?>
        </div>
        <input type="hidden" name="code" id="otp-code">
        <button type="submit" id="otp-submit-btn" class="btn btn-primary w-100 py-3 font-bold shadow-md shadow-sky-500/20" <?= $isLocked ? 'disabled' : '' ?> onclick="document.getElementById('otp-code').value = Array.from(document.querySelectorAll('.otp-inputs input')).map(i => i.value).join('')">
            تأكيد الرمز
        </button>
    </form>

    <form method="post" action="<?= url('admin/forgot-password') ?>" class="mt-3">
        <?= csrf_field() ?>
        <input type="hidden" name="email" value="<?= e($email) ?>">
        <button type="submit" id="resend-btn" data-cooldown="<?= $cooldown ?>" data-auto-resend="<?= ($isLocked && $isDemo) ? '1' : '0' ?>" class="btn btn-resend btn-soft w-100 py-2.5 is-locked" disabled>
            <i class="bi bi-clock-history"></i>
            <span>إعادة إرسال الرمز بعد <strong id="resend-timer" class="font-numeric"><?= $cooldown ?></strong> ثانية</span>
        </button>
    </form>
</div>
