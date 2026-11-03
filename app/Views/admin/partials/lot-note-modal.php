<?php
/**
 * Popup to write / edit / delete the small note of ONE share (lot).
 * Needs: $member (array), $lot (array), $shareNumber (int), $redirectTo (admin path to return to).
 */
$noteText = \App\Models\ShareLot::note($lot);
$noteAction = url('admin/shares/' . (int) $member['id'] . '/lot/' . (int) $lot['id'] . '/note');
$noteLabel = is_rtl() ? 'السهم رقم ' . (int) $shareNumber : 'Share #' . (int) $shareNumber;
?>
<div class="modal fade" id="lotNoteModal<?= (int) $lot['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl border-0 shadow-2xl">
            <div class="modal-header border-b border-slate-100 bg-slate-50/70 p-4">
                <h5 class="modal-title text-sm font-black text-slate-800 flex items-center gap-2">
                    <i class="bi bi-sticky text-amber-600"></i>
                    <span><?= is_rtl() ? 'ملاحظة ' . e($noteLabel) : 'Note for ' . e($noteLabel) ?></span>
                </h5>
                <button type="button" class="btn-close text-xs" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= $noteAction ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_to" value="<?= e($redirectTo) ?>">
                <div class="modal-body p-4">
                    <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl text-xs text-amber-900 leading-relaxed mb-3">
                        <i class="bi bi-info-circle-fill text-amber-600 me-1"></i>
                        <?= is_rtl()
                            ? 'الملاحظة خاصة بهذا السهم فقط ولا تظهر على بقية أسهم المشترك، وتظهر للمشترك نفسه في صفحة أسهمه، فاكتبها بصياغة مناسبة له.'
                            : 'The note belongs to this share only. The member sees it on their shares page, so word it accordingly.' ?>
                    </div>
                    <label class="form-label text-xs font-bold text-slate-700 mb-1.5"><?= is_rtl() ? 'الملاحظة (حتى 500 حرف)' : 'Note (up to 500 characters)' ?></label>
                    <textarea name="admin_note" rows="3" maxlength="500" class="form-control text-sm" placeholder="<?= is_rtl() ? 'مثال: تم استيفاء جميع الشروط' : 'e.g. all conditions are met' ?>"><?= e($noteText) ?></textarea>
                </div>
                <div class="modal-footer border-t border-slate-100 p-3 bg-slate-50/50 flex justify-between">
                    <button type="button" class="btn btn-light text-xs font-bold px-3 py-2 rounded-xl" data-bs-dismiss="modal"><?= is_rtl() ? 'إلغاء' : 'Cancel' ?></button>
                    <button type="submit" class="btn btn-primary text-xs font-bold px-4 py-2 rounded-xl border-0">
                        <i class="bi bi-check-lg me-1"></i>
                        <?= is_rtl() ? 'حفظ الملاحظة' : 'Save note' ?>
                    </button>
                </div>
            </form>
            <?php if ($noteText !== ''): ?>
                <form method="post" action="<?= $noteAction ?>" class="px-3 pb-3 bg-slate-50/50" data-confirm="<?= is_rtl() ? 'حذف ملاحظة هذا السهم؟' : 'Delete this share\'s note?' ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="<?= e($redirectTo) ?>">
                    <input type="hidden" name="admin_note" value="">
                    <button type="submit" class="btn btn-sm btn-outline-danger text-xs font-bold rounded-xl w-100">
                        <i class="bi bi-trash3"></i> <?= is_rtl() ? 'حذف الملاحظة' : 'Delete note' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
