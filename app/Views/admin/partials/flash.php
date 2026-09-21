<?php $success = flash('success'); $error = flash('error'); ?>
<?php if ($success): ?>
    <div class="alert-modern alert-success-modern" data-auto-dismiss><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert-modern alert-danger-modern" data-auto-dismiss><i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?></div>
<?php endif; ?>
