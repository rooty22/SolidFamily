<div class="auth-card">
    <div class="text-center">
        <div class="auth-logo"><i class="bi bi-key-fill"></i></div>
        <h4 class="fw-bold mb-1">نسيت كلمة المرور</h4>
        <p class="text-muted mb-4" style="font-size:13.5px;">أدخل بريدك الإلكتروني لإرسال رمز التحقق</p>
    </div>

    <?php $error = flash('error'); ?>
    <?php if ($error): ?>
        <div class="alert-modern alert-danger-modern"><i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url('admin/forgot-password') ?>">
        <?= csrf_field() ?>
        <div class="mb-4">
            <label class="form-label">البريد الإلكتروني</label>
            <input type="email" name="email" class="form-control" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">إرسال رمز التحقق</button>
        <a href="<?= url('admin/login') ?>" class="btn btn-soft w-100 py-2 mt-2">العودة لتسجيل الدخول</a>
    </form>
</div>
