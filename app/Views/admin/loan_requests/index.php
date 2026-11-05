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
            <a href="<?= url('admin/loan-requests?status=rejected' . ($qs ? '&' . $qs : '')) ?>" class="btn <?= $status === 'rejected' ? 'btn-primary' : 'btn-soft' ?>"><?= __('status_rejected') ?></a>
        </div>
    </div>
</div>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
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
                <td><?= member_cell((string) $r['member_name'], $r['member_serial'] ?? null, url('admin/members/' . $r['member_id'])) ?></td>
                <td class="font-numeric font-bold text-slate-900"><?= money($r['amount_requested']) ?></td>
                <td><span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold"><?= $reasonLabels[$r['reason']] ?? $r['reason'] ?></span></td>
                <td class="text-xs text-slate-500 font-numeric"><bdi dir="ltr"><?= date_ar($r['created_at']) ?></bdi></td>
                <td class="font-numeric font-bold"><?php if ($r['status'] === 'pending'): ?>#<?= array_search($r['id'], array_column($pendingOrder, 'id')) + 1 ?><?php else: ?>-<?php endif; ?></td>
                <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                <td class="text-end">
                    <div class="row-actions">
                        <a href="<?= url('admin/loan-requests/' . $r['id']) ?>" class="row-btn row-btn-edit row-btn-text"><i class="bi bi-clipboard-check"></i> <?= __('review') ?></a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($requests)): ?>
            <tr>
                <td colspan="7" class="p-0">
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
