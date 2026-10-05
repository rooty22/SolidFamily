<?php $admin = \App\Core\Auth::admin(); ?>
<header class="topbar">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="sidebar-toggle-btn" aria-label="Toggle Sidebar"><i class="bi bi-list"></i></button>
        <div class="page-title"><?= e($pageTitle ?? __('overview')) ?></div>
    </div>
    <div class="topbar-actions d-flex align-items-center gap-2 sm:gap-3">
        <!-- Live Translate: turns Translate mode on/off (and shows/hides the Translate button of the site) -->
        <?= \App\LiveTranslate\LiveTranslate::adminButton() ?>

        <!-- View Public Website -->
        <a href="<?= url('/') ?>" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all" title="<?= __('home') ?>">
            <i class="bi bi-globe text-sky-600"></i>
            <span><?= __('visit_website') ?></span>
        </a>

        <!-- Fast Language Switcher in Admin Topbar -->
        <a href="<?= url('admin/lang/' . (current_locale() === 'ar' ? 'en' : 'ar')) ?>" 
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-xs"
           title="<?= current_locale() === 'ar' ? 'Switch to English' : 'التحويل إلى العربية' ?>">
            <i class="bi bi-translate text-sky-600"></i>
            <span class="hidden sm:inline"><?= current_locale() === 'ar' ? 'English' : 'العربية' ?></span>
            <span class="sm:hidden"><?= current_locale() === 'ar' ? 'EN' : 'ع' ?></span>
        </a>

        <!-- Admin Profile Chip with Role -->
        <div class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
            <div class="w-7 h-7 rounded-lg <?= admin_is_super() ? 'bg-purple-600' : 'bg-emerald-600' ?> text-white font-bold text-xs flex items-center justify-center">
                <?= e(mb_substr($admin['name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="flex flex-col text-start">
                <span class="text-xs font-bold text-slate-800 leading-tight truncate max-w-[130px]"><?= e($admin['name'] ?? '') ?></span>
                <span class="text-[10px] <?= admin_is_super() ? 'text-purple-700' : 'text-emerald-700' ?> font-semibold leading-none mt-0.5">
                    <?= e(admin_role_name() ?: __('admin_portal')) ?>
                </span>
            </div>
        </div>
    </div>
</header>
