<div class="card-panel mb-3">
    <div class="profile-hero">
        <div class="avatar-lg"><?= e(mb_substr($member['name'], 0, 1)) ?></div>
        <div class="who">
            <b><?= e($member['name']) ?></b>
            <span><?= __('national_id_and_member_since', ['id' => '<bdi dir="ltr" class="font-num">' . e($member['national_id']) . '</bdi>', 'date' => '<bdi dir="ltr" class="font-num">' . date_ar($member['created_at']) . '</bdi>']) ?></span>
        </div>
        <div class="ms-auto d-flex gap-2 flex-wrap">
            <span class="badge-status badge-success font-num"><?= __('shares_count_val', ['count' => number_format($member['shares_count'])]) ?></span>
            <span class="badge-status badge-<?= $member['status'] === 'active' ? 'success' : 'danger' ?>"><?= $member['status'] === 'active' ? __('active_account') : __('inactive_account') ?></span>
        </div>
    </div>
</div>

<div class="split">
    <div class="card-panel">
        <div class="panel-head"><h3><i class="bi bi-person-vcard"></i> <?= __('personal_info') ?></h3></div>
        <form method="post" action="<?= url('profile') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label"><?= __('name') ?></label><input type="text" class="form-control" value="<?= e($member['name']) ?>" disabled></div>
                <div class="col-md-6"><label class="form-label"><?= __('national_id') ?></label><input type="text" class="form-control ltr-input font-num" value="<?= e($member['national_id']) ?>" disabled></div>
                <div class="col-md-6"><label class="form-label"><?= __('birth_date') ?></label><input type="text" class="form-control ltr-input font-num" value="<?= date_ar($member['birth_date']) ?>" disabled></div>
                <div class="col-md-6"><label class="form-label"><?= __('mobile_number') ?></label><input type="text" name="mobile" class="form-control ltr-input font-num" dir="ltr" value="<?= e($member['mobile']) ?>" required></div>
                <div class="col-md-6"><label class="form-label"><?= __('email_address') ?></label><input type="email" name="email" class="form-control ltr-input font-num" dir="ltr" value="<?= e($member['email']) ?>" required></div>
                <div class="col-md-6"><label class="form-label"><?= __('national_address') ?></label><input type="text" name="national_address" class="form-control" value="<?= e($member['national_address']) ?>"></div>
                <div class="col-md-4"><label class="form-label"><?= __('bank_name') ?></label><input type="text" name="bank_name" class="form-control" value="<?= e($member['bank_name']) ?>"></div>
                <div class="col-md-4"><label class="form-label"><?= __('bank_account_number') ?></label><input type="text" name="bank_account_number" class="form-control ltr-input font-num" dir="ltr" value="<?= e($member['bank_account_number']) ?>"></div>
                <div class="col-md-4"><label class="form-label"><?= __('iban') ?></label><input type="text" name="iban" class="form-control ltr-input font-num" dir="ltr" value="<?= e($member['iban']) ?>"></div>
            </div>
            <div class="tip-box mt-3"><i class="bi bi-info-circle-fill"></i> <?= __('profile_edit_tip') ?></div>
            <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check2-circle"></i> <?= __('save_changes') ?></button>
        </form>
    </div>

    <div class="stack">
        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-shield-lock-fill"></i> <?= __('change_password') ?></h3></div>
            <form method="post" action="<?= url('profile/password') ?>">
                <?= csrf_field() ?>
                <div class="mb-2"><label class="form-label"><?= __('current_password') ?></label><input type="password" name="current_password" class="form-control" autocomplete="current-password" required></div>
                <div class="mb-2"><label class="form-label"><?= __('new_password') ?></label><input type="password" name="new_password" class="form-control" autocomplete="new-password" required minlength="8" maxlength="72"></div>
                <div class="mb-3"><label class="form-label"><?= __('confirm_new_password') ?></label><input type="password" name="new_password_confirmation" class="form-control" autocomplete="new-password" required minlength="8" maxlength="72"></div>
                <button type="submit" class="btn btn-soft w-100"><i class="bi bi-key-fill"></i> <?= __('update_password') ?></button>
            </form>
        </div>
        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-person-badge-fill"></i> <?= __('account_summary') ?></h3></div>
            <div class="info-list">
                <div class="info-row"><span><?= __('shares_count') ?></span><b class="font-num"><?= number_format($member['shares_count']) ?></b></div>
                <div class="info-row"><span><?= __('join_date') ?></span><b class="font-num"><?= date_ar($member['created_at']) ?></b></div>
                <div class="info-row"><span><?= __('status') ?></span><b><?= $member['status'] === 'active' ? __('status_active') : __('status_inactive') ?></b></div>
            </div>
        </div>
    </div>
</div>
