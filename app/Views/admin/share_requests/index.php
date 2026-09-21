<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= is_rtl() ? 'طلبات تعديل وتنازل الأسهم' : 'Share Adjustment & Transfer Requests' ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= is_rtl() ? 'مراجعة واعتماد أو رفض طلبات زيادة أو استرداد أو التنازل عن الأسهم' : 'Review, approve, or reject share purchase, refund, and transfer requests' ?></p>
    </div>
    <div class="btn-group">
        <a href="<?= url('admin/share-requests') ?>" class="btn <?= $status === '' ? 'btn-primary' : 'btn-soft' ?>"><?= is_rtl() ? 'الكل' : 'All' ?></a>
        <a href="<?= url('admin/share-requests?status=pending') ?>" class="btn <?= $status === 'pending' ? 'btn-primary' : 'btn-soft' ?>"><?= is_rtl() ? 'قيد المراجعة' : 'Pending' ?></a>
        <a href="<?= url('admin/share-requests?status=approved') ?>" class="btn <?= $status === 'approved' ? 'btn-primary' : 'btn-soft' ?>"><?= is_rtl() ? 'مقبول' : 'Approved' ?></a>
        <a href="<?= url('admin/share-requests?status=rejected') ?>" class="btn <?= $status === 'rejected' ? 'btn-primary' : 'btn-soft' ?>"><?= is_rtl() ? 'مرفوض' : 'Rejected' ?></a>
    </div>
</div>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th><?= is_rtl() ? 'المشترك' : 'Member' ?></th>
                <th><?= is_rtl() ? 'نوع الطلب' : 'Request Type' ?></th>
                <th><?= is_rtl() ? 'العدد' : 'Count' ?></th>
                <th><?= is_rtl() ? 'تاريخ الطلب' : 'Request Date' ?></th>
                <th><?= is_rtl() ? 'الحالة' : 'Status' ?></th>
                <th class="text-end"><?= is_rtl() ? 'إجراءات' : 'Actions' ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($requests as $r): [$l, $v] = status_badge($r['status']); ?>
            <tr>
                <td class="fw-bold"><?= e($r['member_name']) ?></td>
                <td><?= $typeLabels[$r['type']] ?? $r['type'] ?></td>
                <td class="font-numeric"><?= $r['shares_count'] ? number_format($r['shares_count']) : '-' ?></td>
                <td class="text-xs text-slate-500"><?= is_rtl() ? date_ar($r['created_at']) : date('M d, Y', strtotime($r['created_at'])) ?></td>
                <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                <td class="text-end">
                    <a href="<?= url('admin/share-requests/' . $r['id']) ?>" class="btn btn-sm btn-soft inline-flex items-center gap-1">
                        <i class="bi bi-eye"></i>
                        <span><?= is_rtl() ? 'مراجعة' : 'Review' ?></span>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($requests)): ?>
            <tr>
                <td colspan="6" class="p-0">
                    <div class="empty-state">
                        <i class="bi bi-arrow-left-right"></i>
                        <span><?= is_rtl() ? 'لا توجد طلبات أسهم مسجلة' : 'No share requests found.' ?></span>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
