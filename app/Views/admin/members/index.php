<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900"><?= __('members_directory_title') ?></h1>
        <p class="text-xs text-slate-500 mt-0.5"><?= __('members_directory_desc') ?></p>
    </div>
    <div class="flex items-center gap-3 flex-wrap sm:flex-nowrap">
        <form method="get" action="<?= url('admin/members') ?>" class="search-box" style="min-width:280px;">
            <i class="bi bi-search"></i>
            <input type="text" name="q" class="form-control" placeholder="<?= __('search_members_full') ?>" value="<?= e($q) ?>">
        </form>
        <a href="<?= url('admin/members/create') ?>" class="btn btn-primary inline-flex items-center gap-2 whitespace-nowrap shrink-0 font-bold">
            <i class="bi bi-person-plus-fill"></i>
            <span><?= __('add_member') ?></span>
        </a>
    </div>
</div>

<div class="table-panel">
    <table class="table-modern">
        <thead>
            <tr>
                <th><?= __('name') ?></th>
                <th><?= __('mobile') ?></th>
                <th><?= __('national_id') ?></th>
                <th><?= __('shares') ?></th>
                <th><?= __('status') ?></th>
                <th><?= __('registered_at') ?></th>
                <th class="text-end"><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $m): [$label, $variant] = status_badge($m['status']); ?>
            <tr>
                <td class="fw-bold text-slate-900"><?= e($m['name']) ?></td>
                <td class="font-numeric" dir="ltr"><?= e($m['mobile']) ?></td>
                <td class="font-numeric" dir="ltr"><?= e($m['national_id']) ?></td>
                <td class="font-numeric font-bold text-slate-800"><?= number_format($m['shares_count']) ?></td>
                <td><span class="badge-status badge-<?= $variant ?>"><?= $label ?></span></td>
                <td class="text-xs text-slate-500 font-numeric"><?= is_rtl() ? date_ar($m['created_at']) : date('M d, Y', strtotime($m['created_at'])) ?></td>
                <td class="text-end">
                    <a href="<?= url('admin/members/' . $m['id']) ?>" class="btn btn-sm btn-soft" title="<?= __('view') ?>"><i class="bi bi-eye"></i></a>
                    <a href="<?= url('admin/members/' . $m['id'] . '/edit') ?>" class="btn btn-sm btn-soft" title="<?= __('edit') ?>"><i class="bi bi-pencil"></i></a>
                    <form method="post" action="<?= url('admin/members/' . $m['id'] . '/toggle-status') ?>" class="d-inline" data-confirm="<?= __('are_you_sure') ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-soft" title="<?= $m['status'] === 'active' ? __('deactivate') : __('activate') ?>">
                            <i class="bi bi-<?= $m['status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($members)): ?>
            <tr>
                <td colspan="7" class="p-0">
                    <div class="empty-state">
                        <i class="bi bi-people text-3xl text-slate-400 mb-2"></i>
                        <span><?= __('no_members_recorded') ?></span>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
