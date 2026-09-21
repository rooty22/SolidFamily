<?php $canRequest = $shares > 0; ?>
<div class="page-head">
    <div>
        <p><?= __('loan_request_subtitle') ?></p>
    </div>
    <a href="<?= url('loans') ?>" class="btn btn-soft btn-sm"><i class="bi bi-arrow-<?= is_rtl() ? 'right' : 'left' ?>"></i> <?= __('back_to_loans') ?></a>
</div>

<div class="split">
    <div class="card-panel"
         x-data="{ amount: '', months: 6, reason: 'personal', max: <?= json_encode((float) $maxLoan) ?>,
                   get installment() { return (this.amount > 0 && this.months > 0) ? this.amount / this.months : 0; },
                   get over() { return this.max > 0 && this.amount > this.max; } }">
        <div class="panel-head"><h3><i class="bi bi-file-earmark-plus-fill"></i> <?= __('request_details') ?></h3></div>

        <?php if (!$canRequest): ?>
            <div class="tip-box mb-3"><i class="bi bi-info-circle-fill"></i> <?= __('loan_need_shares_tip', ['url' => url('share-requests')]) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= url('loans/request') ?>">
            <?= csrf_field() ?>
            <fieldset <?= $canRequest ? '' : 'disabled' ?>>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><?= __('loan_amount_requested_riyal') ?></label>
                        <input type="number" step="0.01" min="1" <?= $maxLoan > 0 ? 'max="' . (float) $maxLoan . '"' : '' ?> name="amount_requested" x-model.number="amount" class="form-control font-num" placeholder="0.00" required>
                        <div class="small mt-1" :class="over ? 'text-danger' : 'text-muted'">
                            <?= __('max_loan_available') ?> <b class="font-num"><?= money($maxLoan) ?></b>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= __('repayment_period_months') ?></label>
                        <select name="installments_months" x-model.number="months" class="form-select" required>
                            <?php foreach ([3, 6, 9, 12, 18, 24, 36, 48, 60] as $m): ?>
                                <option value="<?= $m ?>" <?= $m === 6 ? 'selected' : '' ?>><?= $m ?> <?= __('months') ?><?= $m === 12 ? ' (' . __('one_year') . ')' : ($m === 24 ? ' (' . __('two_years') . ')' : '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= __('loan_reason') ?></label>
                        <select name="reason" x-model="reason" class="form-select" required>
                            <?php foreach ($reasonLabels as $key => $lbl): ?>
                                <option value="<?= $key ?>"><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= __('estimated_monthly_installment') ?></label>
                        <div class="form-control d-flex align-items-center justify-content-between" style="background:var(--body-bg);">
                            <b class="font-num" x-text="installment.toLocaleString('en-US', {maximumFractionDigits: 2})">0</b><span class="text-muted small"><?= __('riyal_per_month') ?></span>
                        </div>
                    </div>
                    <div class="col-12" x-show="reason === 'other'" x-cloak style="display:none;">
                        <label class="form-label"><?= __('reason_description') ?></label>
                        <textarea name="reason_other_text" class="form-control" rows="2" maxlength="500" placeholder="<?= __('reason_placeholder') ?>" :required="reason === 'other'"></textarea>
                    </div>
                </div>

                <div class="mt-3 p-3" style="background:var(--body-bg);border:1px dashed var(--border-color);border-radius:12px;font-size:13px;line-height:1.9;">
                    <div class="fw-bold mb-1"><i class="bi bi-file-earmark-text"></i> <?= __('commitment_text_title') ?></div>
                    <?= nl2br(e($commitmentText)) ?>
                </div>
                <div class="form-check mt-3 mb-4">
                    <input class="form-check-input" type="checkbox" name="agreed_terms" id="agreeCheck" value="1" required>
                    <label class="form-check-label" for="agreeCheck"><?= __('agree_loan_terms_label') ?></label>
                </div>
                <button type="submit" class="btn btn-primary w-100" :disabled="over"><i class="bi bi-send-fill"></i> <?= __('submit_loan_request') ?></button>
            </fieldset>
        </form>
    </div>

    <div class="stack">
        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-person-check-fill"></i> <?= __('loan_eligibility_title') ?></h3></div>
            <div class="info-list">
                <div class="info-row"><span><?= __('your_shares_count') ?></span><b class="font-num"><?= number_format($shares) ?></b></div>
                <div class="info-row"><span><?= __('your_capital_calc') ?></span><b class="font-num"><?= money($capital) ?></b></div>
                <div class="info-row"><span><?= __('max_loan_calc', ['ratio' => rtrim(rtrim(number_format($ratio, 2), '0'), '.')]) ?></span><b class="font-num text-success"><?= money($maxLoan) ?></b></div>
                <div class="info-row"><span><?= __('active_loans') ?></span><b class="font-num"><?= $runningCount ?> · <?= money($runningRemaining) ?></b></div>
                <div class="info-row"><span><?= __('pending_requests') ?></span><b class="font-num"><?= $pendingRequests ?></b></div>
            </div>
        </div>

        <div class="card-panel">
            <div class="panel-head"><h3><i class="bi bi-signpost-split-fill"></i> <?= __('how_loan_process_works') ?></h3></div>
            <ol class="steps">
                <li><?= __('loan_step_1') ?></li>
                <li><?= __('loan_step_2') ?></li>
                <li><?= __('loan_step_3') ?></li>
                <li><?= __('loan_step_4') ?></li>
            </ol>
        </div>
    </div>
</div>
