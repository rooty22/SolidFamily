<div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="card-panel flex flex-col sm:flex-row sm:items-center justify-between gap-4 !p-5">
        <div class="flex items-center gap-3.5">
            <a href="<?= url('admin/messages') ?>" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition-all shadow-xs" title="<?= __('back_to_messages') ?>">
                <i class="bi <?= is_rtl() ? 'bi-arrow-right' : 'bi-arrow-left' ?> text-lg"></i>
            </a>
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-lg sm:text-xl font-black text-slate-900 m-0"><?= __('contact_message_from', ['name' => $message['name']]) ?></h1>
                    <span class="badge-status <?= $message['status'] === 'new' ? 'badge-warning' : 'badge-secondary' ?>">
                        <?= $message['status'] === 'new' ? __('new_message') : __('read_message') ?>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1 mb-0 font-numeric">
                    <i class="bi bi-clock me-1 text-slate-400"></i>
                    <span><?= __('received_at', ['date' => is_rtl() ? date_ar($message['created_at'], 'Y-m-d H:i') : date('M d, Y H:i', strtotime($message['created_at']))]) ?></span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?= url('admin/messages') ?>" class="btn btn-sm btn-soft inline-flex items-center gap-1.5 font-bold">
                <i class="bi bi-inbox-fill text-sky-600"></i>
                <span><?= __('back_to_messages') ?></span>
            </a>
        </div>
    </div>

    <!-- 2-Column Responsive Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Main Message Thread Column -->
        <div class="lg:col-span-8 space-y-6">
            <!-- Message Body Card -->
            <div class="card-panel space-y-5">
                <div class="border-b border-slate-100 pb-3.5 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-sm shadow-xs">
                            <i class="bi bi-chat-left-quote-fill"></i>
                        </div>
                        <h2 class="text-base font-bold text-slate-900 m-0"><?= __('message_body_details') ?></h2>
                    </div>
                    <span class="text-xs text-slate-400 font-numeric font-medium"><?= is_rtl() ? date_ar($message['created_at'], 'Y-m-d H:i') : date('M d, Y H:i', strtotime($message['created_at'])) ?></span>
                </div>

                <!-- Message Bubble -->
                <div class="p-5 sm:p-6 rounded-2xl bg-slate-50/80 border border-slate-200/80 text-slate-800 text-sm leading-relaxed shadow-inner-xs relative">
                    <div class="text-xs font-bold text-slate-500 mb-2.5 flex items-center gap-1.5">
                        <i class="bi bi-person-fill text-slate-400"></i>
                        <span><?= e($message['name']) ?></span>
                    </div>
                    <div class="whitespace-pre-wrap font-medium"><?= nl2br(e($message['message'])) ?></div>
                </div>

                <!-- Existing Admin Reply -->
                <?php if (!empty($message['reply_text'])): ?>
                <div class="p-5 sm:p-6 rounded-2xl bg-emerald-50/80 border border-emerald-200/90 text-slate-800 text-sm leading-relaxed shadow-inner-xs">
                    <div class="flex items-center justify-between gap-2 mb-2.5 text-xs font-bold text-emerald-800">
                        <div class="flex items-center gap-1.5">
                            <i class="bi bi-patch-check-fill text-emerald-600 text-sm"></i>
                            <span><?= __('admin_reply') ?></span>
                        </div>
                        <span class="font-numeric text-emerald-700/80"><?= is_rtl() ? date_ar($message['replied_at'], 'Y-m-d H:i') : date('M d, Y H:i', strtotime($message['replied_at'])) ?></span>
                    </div>
                    <div class="whitespace-pre-wrap text-emerald-950 font-medium"><?= nl2br(e($message['reply_text'])) ?></div>
                </div>
                <?php endif; ?>

                <!-- Reply Composer -->
                <form method="post" action="<?= url('admin/messages/' . $message['id'] . '/reply') ?>" class="space-y-3 pt-2">
                    <?= csrf_field() ?>
                    <div>
                        <label class="form-label text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                            <i class="bi bi-reply-fill text-sky-600"></i>
                            <span><?= __('reply_to_message') ?></span>
                        </label>
                        <textarea name="reply" rows="4" required minlength="2" maxlength="2000" class="form-control text-sm font-medium" placeholder="<?= __('reply_placeholder') ?>"></textarea>
                        <div class="text-xs text-slate-500 mt-2 flex items-center gap-1.5">
                            <i class="bi bi-info-circle text-slate-400"></i>
                            <span>
                                <?= !empty($message['member_id'])
                                    ? __('reply_notification_member_hint')
                                    : __('reply_notification_guest_hint') ?>
                            </span>
                        </div>
                    </div>
                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="btn btn-primary px-5 py-2.5 font-bold inline-flex items-center gap-2 shadow-md shadow-sky-600/20">
                            <i class="bi bi-send-fill"></i>
                            <span><?= __('save_and_send_reply') ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sender Information Sidebar Column -->
        <div class="lg:col-span-4 space-y-6">
            <div class="card-panel space-y-4">
                <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 m-0 flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                            <i class="bi bi-person-lines-fill"></i>
                        </div>
                        <span><?= __('sender_info') ?></span>
                    </h3>
                </div>

                <!-- Avatar and Name Tag -->
                <div class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-sky-600 to-emerald-500 text-white font-black text-lg flex items-center justify-center shadow-sm">
                        <?= mb_substr($message['name'] ?? 'U', 0, 1) ?>
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-sm font-bold text-slate-900 truncate m-0"><?= e($message['name']) ?></h4>
                        <span class="text-xs text-slate-500 font-numeric" dir="ltr"><?= e($message['phone'] ?: '-') ?></span>
                    </div>
                </div>

                <!-- Details Rows -->
                <div class="space-y-2.5 text-xs pt-1">
                    <div class="flex items-center justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium"><?= __('name') ?></span>
                        <span class="font-bold text-slate-900"><?= e($message['name']) ?></span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-slate-100">
                        <span class="text-slate-500 font-medium"><?= __('phone_number') ?></span>
                        <span class="font-bold text-slate-900 font-numeric" dir="ltr"><?= e($message['phone'] ?: '-') ?></span>
                    </div>
                </div>

                <!-- Contact & Profile Actions -->
                <div class="space-y-2 pt-2">
                    <?php if (!empty($message['phone'])): ?>
                        <a href="tel:<?= e($message['phone']) ?>" class="btn-soft-success w-full justify-center py-2.5 text-xs font-bold">
                            <i class="bi bi-telephone-fill"></i>
                            <span><?= __('call_sender') ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($message['member_id'])): ?>
                        <a href="<?= url('admin/members/' . $message['member_id']) ?>" class="btn btn-soft w-full justify-center py-2.5 text-xs font-bold">
                            <i class="bi bi-person-badge"></i>
                            <span><?= __('view_member_profile') ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
