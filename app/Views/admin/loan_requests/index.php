<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= __('loan_requests_title') ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= __('loan_requests_desc') ?></p>
    </div>
    <div class="flex items-center gap-3 flex-wrap sm:flex-nowrap">
        <form method="get" action="<?= url('admin/loan-requests') ?>" class="flex items-center gap-2">
            <div class="search-box" style="min-width:260px;">
                <i class="bi bi-search"></i>
                <input type="text" name="q" class="form-control" placeholder="<?= __('search_by_member_loan') ?>" value="<?= e($q ?? '') ?>">
            </div>
            <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
        </form>
        <?php $qs = ($q ?? '') !== '' ? 'q=' . urlencode($q) : ''; ?>
        <div class="btn-group">
            <a href="<?= url('admin/loan-requests' . ($qs ? '?' . $qs : '')) ?>" class="btn <?= $status === '' ? 'btn-primary' : 'btn-soft' ?>"><?= __('all') ?></a>
            <a href="<?= url('admin/loan-requests?status=pending' . ($qs ? '&' . $qs : '')) ?>" class="btn <?= $status === 'pending' ? 'btn-primary' : 'btn-soft' ?>"><?= __('status_pending') ?></a>
            <a href="<?= url('admin/loan-requests?status=approved' . ($qs ? '&' . $qs : '')) ?>" class="btn <?= $status === 'approved' ? 'btn-primary' : 'btn-soft' ?>"><?= __('status_approved') ?></a>
            <a href="<?= url('admin/loan-requests?status=waiting' . ($qs ? '&' . $qs : '')) ?>" class="btn <?= $status === 'waiting' ? 'btn-primary' : 'btn-soft' ?>" title="<?= is_rtl() ? 'موافَق عليها ولم يُصدر قرضها بعد' : 'Approved, loan not issued yet' ?>"><?= is_rtl() ? 'بانتظار الإصدار' : 'Waiting' ?> <span class="font-numeric">(<?= (int) ($waitingCount ?? 0) ?>)</span></a>
            <a href="<?= url('admin/loan-requests?status=rejected' . ($qs ? '&' . $qs : '')) ?>" class="btn <?= $status === 'rejected' ? 'btn-primary' : 'btn-soft' ?>"><?= __('status_rejected') ?></a>
        </div>
    </div>
</div>

<form method="post" id="bulkDeleteForm" action="<?= url('admin/loan-requests/bulk-delete') ?>" data-confirm="<?= e(is_rtl() ? 'سيتم حذف الطلبات المحددة نهائياً. القروض التي صدرت منها (إن وُجدت) تبقى كما هي. هل تريد المتابعة؟' : 'The selected requests will be deleted permanently. Loans issued from them (if any) stay as they are. Continue?') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="redirect_to" value="<?= e('admin/loan-requests' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">
</form>
<div class="table-panel" x-data="{ n: 0, recount() { this.n = document.querySelectorAll('.req-check:checked').length; } }">
    <div x-show="n > 0" x-cloak>
        <div class="d-flex align-items-center justify-content-between gap-2 px-3 py-2" style="background:#fef2f2;border-bottom:1px solid #fecaca;">
            <span class="text-sm fw-bold text-rose-800"><i class="bi bi-check2-square"></i> <span x-text="n"></span> <?= is_rtl() ? 'طلب محدد' : 'selected' ?></span>
            <button type="submit" form="bulkDeleteForm" class="btn btn-sm btn-danger fw-bold"><i class="bi bi-trash3"></i> <?= is_rtl() ? 'حذف المحدد' : 'Delete selected' ?></button>
        </div>
    </div>
    <table class="table-modern">
        <thead>
            <tr>
                <th style="width:34px;"><input type="checkbox" class="form-check-input" title="<?= is_rtl() ? 'تحديد الكل' : 'Select all' ?>" @change="document.querySelectorAll('.req-check').forEach(c => c.checked = $event.target.checked); recount()"></th>
                <th><?= __('member') ?></th>
                <th><?= __('requested_amount') ?></th>
                <th><?= __('reason') ?></th>
                <th><?= __('request_date') ?></th>
                <th><?= __('queue_pos') ?></th>
                <th><?= __('status') ?></th>
                <th class="text-end"><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($requests as $r): [$l, $v] = status_badge($r['status']); ?>
            <tr>
                <td><input type="checkbox" class="form-check-input req-check" name="ids[]" value="<?= (int) $r['id'] ?>" form="bulkDeleteForm" @change="recount()"></td>
                <td><?= member_cell((string) $r['member_name'], $r['member_serial'] ?? null, url('admin/members/' . $r['member_id'])) ?>
                    <?php if (!empty($r['duplicate_hint'])): ?><span class="d-inline-block mt-1 px-2 py-0 rounded-lg text-[11px] fw-bold" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;" title="<?= is_rtl() ? 'للمشترك أكثر من طلب مفتوح على نفس السهم' : 'This member has more than one open request on the same share' ?>"><i class="bi bi-files"></i> <?= is_rtl() ? 'طلب مكرر؟' : 'Duplicate?' ?></span><?php endif; ?>
                </td>
                <td class="font-numeric font-bold text-slate-900"><?= money($r['amount_requested']) ?></td>
                <td><span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold"><?= $reasonLabels[$r['reason']] ?? $r['reason'] ?></span></td>
                <td class="text-xs text-slate-500 font-numeric"><bdi dir="ltr"><?= date_ar($r['created_at']) ?></bdi></td>
                <td class="font-numeric font-bold">
                    <?php if ($r['status'] === 'pending'): ?>
                        #<?= array_search($r['id'], array_column($pendingOrder, 'id')) + 1 ?>
                    <?php elseif (($r['waiting_position'] ?? null) !== null): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 text-xs" title="<?= is_rtl() ? 'ترتيبه في قائمة انتظار إصدار القرض' : 'Position in the loan disbursement waiting list' ?>"><i class="bi bi-hourglass-split"></i> #<?= (int) $r['waiting_position'] ?></span>
                        <?php if (!empty($r['blocking_loan'])): ?>
                            <span class="d-block text-[11px] text-slate-500 fw-normal mt-1"><?= is_rtl() ? 'بانتظار سداد القرض رقم ' . (int) $r['blocking_loan']['id'] : 'Waiting for loan #' . (int) $r['blocking_loan']['id'] . ' to be repaid' ?></span>
                        <?php else: ?>
                            <span class="d-block text-[11px] text-emerald-700 fw-normal mt-1"><?= is_rtl() ? 'جاهز لإصدار القرض' : 'Ready to issue' ?></span>
                        <?php endif; ?>
                    <?php elseif (!empty($r['loan_id'])): ?>
                        <a href="<?= url('admin/loans/' . (int) $r['loan_id']) ?>" class="text-xs fw-bold text-decoration-none"><i class="bi bi-cash-coin"></i> <?= is_rtl() ? 'قرض #' . (int) $r['loan_id'] : 'Loan #' . (int) $r['loan_id'] ?></a>
                    <?php else: ?>-<?php endif; ?>
                </td>
                <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                <td class="text-end">
                    <div class="row-actions">
                        <?php if (($r['waiting_position'] ?? null) !== null && empty($r['blocking_loan'])): ?>
                            <a href="<?= url('admin/loans/create?request_id=' . (int) $r['id']) ?>" class="row-btn row-btn-text" style="color:#047857;border-color:#a7f3d0;background:#ecfdf5;"><i class="bi bi-cash-coin"></i> <?= is_rtl() ? 'إصدار القرض' : 'Issue loan' ?></a>
                        <?php endif; ?>
                        <a href="<?= url('admin/loan-requests/' . $r['id']) ?>" class="row-btn row-btn-edit row-btn-text"><i class="bi bi-clipboard-check"></i> <?= __('review') ?></a>
                        <form method="post" action="<?= url('admin/loan-requests/' . (int) $r['id'] . '/delete') ?>" class="m-0" data-confirm="<?= e(is_rtl() ? 'حذف طلب القرض رقم ' . (int) $r['id'] . ' للمشترك "' . $r['member_name'] . '" نهائياً؟' . (!empty($r['loan_id']) ? ' القرض الذي صدر منه يبقى كما هو.' : '') : 'Delete loan request #' . (int) $r['id'] . ' permanently?') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="redirect_to" value="<?= e('admin/loan-requests' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">
                            <button class="row-btn row-btn-danger" title="<?= is_rtl() ? 'حذف الطلب' : 'Delete request' ?>"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($requests)): ?>
            <tr>
                <td colspan="8" class="p-0">
                    <div class="empty-state">
                        <i class="bi bi-file-earmark-text text-3xl text-slate-400 mb-2"></i>
                        <span><?= __('no_loan_requests_recorded') ?></span>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= pagination_links($pager ?? []) ?>
