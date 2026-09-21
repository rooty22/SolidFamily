<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-900"><?= __('messages_index_title') ?></h1>
            <p class="text-xs text-slate-500 mt-1"><?= __('messages_index_desc') ?></p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200 inline-flex items-center gap-1.5">
                <i class="bi bi-chat-left-text-fill text-emerald-600"></i>
                <span><?= __('messages_count_stat', ['count' => count($messages)]) ?></span>
            </span>
        </div>
    </div>

    <div class="table-panel">
        <table class="table-modern">
            <thead>
                <tr>
                    <th><?= __('name') ?></th>
                    <th><?= __('phone_number') ?></th>
                    <th><?= __('message_body_details') ?></th>
                    <th><?= __('date') ?></th>
                    <th><?= __('status') ?></th>
                    <th class="text-end"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($messages as $msg): [$l, $v] = status_badge($msg['status']); ?>
                <tr>
                    <td class="fw-bold text-slate-900"><?= e($msg['name']) ?></td>
                    <td class="font-numeric" dir="ltr"><?= e($msg['phone'] ?: '-') ?></td>
                    <td class="text-slate-600"><?= e(mb_substr($msg['message'], 0, 70)) ?><?= mb_strlen($msg['message']) > 70 ? '...' : '' ?></td>
                    <td class="text-xs text-slate-500 font-numeric"><?= is_rtl() ? date_ar($msg['created_at'], 'Y-m-d H:i') : date('M d, Y H:i', strtotime($msg['created_at'])) ?></td>
                    <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                    <td class="text-end">
                        <a href="<?= url('admin/messages/' . $msg['id']) ?>" class="btn btn-sm btn-soft inline-flex items-center gap-1.5 font-bold">
                            <i class="bi bi-eye"></i>
                            <span><?= __('view') ?></span>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($messages)): ?>
                <tr>
                    <td colspan="6" class="p-0">
                        <div class="empty-state">
                            <i class="bi bi-chat-dots text-3xl text-slate-400 mb-2"></i>
                            <span><?= __('no_messages_recorded') ?></span>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
