<div class="auth-card">
    <div class="text-center">
        <div class="auth-logo"><i class="bi bi-unlock-fill"></i></div>
        <h4 class="fw-bold mb-1">كلمة مرور جديدة</h4>
        <p class="text-muted mb-4" style="font-size:13.5px;">أدخل كلمة المرور الجديدة وتأكيدها</p>
    </div>

    <?php $error = flash('error'); ?>
    <?php if ($error): ?>
        <div class="alert-modern alert-danger-modern"><i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url('admin/forgot-password/reset') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">كلمة المرور الجديدة</label>
            <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="mb-4">
            <label class="form-label">تأكيد كلمة المرور</label>
            <input type="password" name="password_confirmation" class="form-control" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">حفظ كلمة المرور</button>
    </form>
</div>
