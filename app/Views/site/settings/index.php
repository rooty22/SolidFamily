<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card-panel h-100">
            <div class="panel-head"><h3><i class="bi bi-translate"></i> <?= __('language') ?></h3></div>
            <form method="post" action="<?= url('settings/language') ?>" class="d-flex gap-2">
                <?= csrf_field() ?>
                <button type="submit" name="lang" value="ar" class="btn <?= $currentLang === 'ar' ? 'btn-primary' : 'btn-soft' ?> flex-fill"><?= __('arabic') ?></button>
                <button type="submit" name="lang" value="en" class="btn <?= $currentLang === 'en' ? 'btn-primary' : 'btn-soft' ?> flex-fill"><?= __('english') ?></button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-panel h-100">
            <div class="panel-head"><h3><i class="bi bi-headset"></i> <?= __('support_and_management_contact') ?></h3></div>
            <div class="text-muted mb-2" style="font-size:12.5px;"><?= __('phone_colon') ?> <bdi dir="ltr" class="font-num"><?= e($officialPhone) ?></bdi> · <?= __('email_colon') ?> <bdi dir="ltr" class="font-num"><?= e($officialEmail) ?></bdi></div>
            <form method="post" action="<?= url('settings/support') ?>">
                <?= csrf_field() ?>
                <textarea name="message" class="form-control mb-2" rows="3" placeholder="<?= __('write_your_message_here') ?>" required></textarea>
                <button type="submit" class="btn btn-primary w-100"><?= __('send_message') ?></button>
            </form>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-panel h-100">
            <h3 class="mb-2"><?= e($pages['about']['title'] ?? __('about_us')) ?></h3>
            <p class="text-muted" style="font-size:13.5px;line-height:1.9;"><?= nl2br(e($pages['about']['content'] ?? '')) ?></p>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-panel h-100">
            <h3 class="mb-2"><?= e($pages['terms']['title'] ?? __('terms_and_conditions')) ?></h3>
            <p class="text-muted" style="font-size:13.5px;line-height:1.9;"><?= nl2br(e($pages['terms']['content'] ?? '')) ?></p>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-panel h-100">
            <h3 class="mb-2"><?= e($pages['privacy']['title'] ?? __('privacy_policy')) ?></h3>
            <p class="text-muted" style="font-size:13.5px;line-height:1.9;"><?= nl2br(e($pages['privacy']['content'] ?? '')) ?></p>
        </div>
    </div>
</div>
