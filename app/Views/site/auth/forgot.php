<?php
$isEn = is_en();
?>
<div class="auth-card">
    <div class="text-center">
        <div class="auth-logo"><i class="bi bi-key-fill"></i></div>
        <h4 class="fw-bold mb-1"><?= __('forgot_password_title') ?></h4>
        <p class="text-muted mb-4" style="font-size:13.5px;"><?= __('forgot_password_desc') ?></p>
    </div>

    <?php $error = flash('error'); ?>
    <?php if ($error): ?><div class="alert-modern alert-danger-modern"><i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?></div><?php endif; ?>

    <form method="post" action="<?= url('forgot-password') ?>">
        <?= csrf_field() ?>
        <div class="mb-4">
            <label class="form-label"><?= __('mobile_number') ?></label>
            <input type="text" name="mobile" class="form-control font-numeric" dir="ltr" placeholder="05xxxxxxxx" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2"><?= __('send_verification_code') ?></button>
        <a href="<?= url('login') ?>" class="btn btn-soft w-100 py-2 mt-2"><?= __('back_to_login') ?></a>
    </form>
</div>
