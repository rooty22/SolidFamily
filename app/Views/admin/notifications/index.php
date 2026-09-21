<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= is_rtl() ? 'مركز الإشعارات والتنبيهات' : 'Notification & Broadcast Center' ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= is_rtl() ? 'إرسال إشعارات وتنبيهات مباشرة لجميع الأعضاء أو لمشترك محدد ومتابعة السجل' : 'Broadcast direct alerts to members and manage sent notifications history' ?></p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card-panel h-100">
            <div class="panel-head"><h3><i class="bi bi-send-fill text-purple-600"></i> <?= is_rtl() ? 'إرسال إشعار جديد' : 'Send New Notification' ?></h3></div>
            <form method="post" action="<?= url('admin/notifications') ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label"><?= is_rtl() ? 'عنوان الإشعار' : 'Notification Title' ?></label>
                    <input type="text" name="title" class="form-control" required placeholder="<?= is_rtl() ? 'مثال: موعد الاستحقاق الشهري' : 'e.g. Monthly Due Reminder' ?>">
                </div>
                <div class="mb-2">
                    <label class="form-label"><?= is_rtl() ? 'نص الإشعار' : 'Notification Body' ?></label>
                    <textarea name="body" class="form-control" rows="3" required placeholder="<?= is_rtl() ? 'اكتب تفاصيل الإشعار هنا...' : 'Write message details...' ?>"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label"><?= is_rtl() ? 'الفئة المستهدفة' : 'Target Audience' ?></label>
                    <select name="target_type" class="form-select" id="targetType" onchange="document.getElementById('mobileWrap').style.display = this.value === 'specific' ? 'block' : 'none'">
                        <option value="all"><?= is_rtl() ? 'جميع المشتركين' : 'All Members' ?></option>
                        <option value="specific"><?= is_rtl() ? 'مشترك معين' : 'Specific Member' ?></option>
                    </select>
                </div>
                <div class="mb-3" id="mobileWrap" style="display:none;">
                    <label class="form-label"><?= is_rtl() ? 'رقم جوال المشترك' : 'Member Mobile' ?></label>
                    <input type="text" name="mobile" class="form-control font-numeric" dir="ltr" placeholder="05xxxxxxxx">
                </div>
                <button type="submit" class="btn btn-primary w-100"><?= is_rtl() ? 'إرسال الإشعار' : 'Send Notification' ?></button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-panel h-100">
            <div class="panel-head"><h3><i class="bi bi-funnel text-slate-500"></i> <?= is_rtl() ? 'فلترة السجل' : 'Filter Log' ?></h3></div>
            <form method="get" action="<?= url('admin/notifications') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><?= is_rtl() ? 'من تاريخ' : 'From Date' ?></label>
                    <input type="date" name="from" class="form-control font-numeric" value="<?= e($filters['from']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= is_rtl() ? 'إلى تاريخ' : 'To Date' ?></label>
                    <input type="date" name="to" class="form-control font-numeric" value="<?= e($filters['to']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= is_rtl() ? 'الفئة' : 'Audience' ?></label>
                    <select name="target_type" class="form-select">
                        <option value=""><?= is_rtl() ? 'الكل' : 'All' ?></option>
                        <option value="all" <?= $filters['target_type'] === 'all' ? 'selected' : '' ?>><?= is_rtl() ? 'الجميع' : 'All Members' ?></option>
                        <option value="specific" <?= $filters['target_type'] === 'specific' ? 'selected' : '' ?>><?= is_rtl() ? 'مشترك معين' : 'Specific' ?></option>
                    </select>
                </div>
                <div class="col-md-3"><button class="btn btn-primary w-100"><?= is_rtl() ? 'فلترة' : 'Apply Filter' ?></button></div>
            </form>
        </div>
    </div>
</div>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th><?= is_rtl() ? 'العنوان' : 'Title' ?></th>
                <th><?= is_rtl() ? 'الفئة المستهدفة' : 'Audience' ?></th>
                <th><?= is_rtl() ? 'التاريخ والوقت' : 'Date & Time' ?></th>
                <th><?= is_rtl() ? 'الحالة' : 'Status' ?></th>
                <th class="text-end"><?= is_rtl() ? 'إجراءات' : 'Actions' ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($notifications as $n): [$l, $v] = status_badge($n['status']); ?>
            <tr>
                <td class="fw-bold"><?= e($n['title']) ?><div class="text-muted" style="font-size:12px;"><?= e(mb_substr($n['body'], 0, 60)) ?></div></td>
                <td><?= $n['target_type'] === 'all' ? (is_rtl() ? 'جميع المشتركين' : 'All Members') : e($n['target_member_name'] ?? (is_rtl() ? 'غير معروف' : 'Unknown')) ?></td>
                <td class="text-xs text-slate-500"><?= is_rtl() ? date_ar($n['created_at'], 'Y-m-d H:i') : date('M d, Y H:i', strtotime($n['created_at'])) ?></td>
                <td><span class="badge-status badge-<?= $v ?>"><?= $l ?></span></td>
                <td class="text-end">
                    <form method="post" action="<?= url('admin/notifications/' . $n['id'] . '/delete') ?>" data-confirm="<?= is_rtl() ? 'حذف الإشعار؟' : 'Delete notification?' ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-soft text-danger" title="<?= is_rtl() ? 'حذف' : 'Delete' ?>"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($notifications)): ?>
            <tr>
                <td colspan="5" class="p-0">
                    <div class="empty-state">
                        <i class="bi bi-bell"></i>
                        <span><?= is_rtl() ? 'لا توجد إشعارات مسجلة' : 'No notifications found.' ?></span>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
