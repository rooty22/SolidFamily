<?php $heroImage = \App\Models\HomePage::imageUrl($sec['image'] ?? ''); ?>
<!-- Hero Section -->
<header id="<?= e($sec['id']) ?>" class="relative overflow-hidden bg-gradient-to-b from-slate-900 via-slate-950 to-slate-900 text-white py-12 sm:py-16 lg:py-24">
    <?php if ($heroImage): ?>
        <img src="<?= e($heroImage) ?>" alt="" class="absolute inset-0 w-full h-full object-cover pointer-events-none">
        <div class="absolute inset-0 bg-slate-950/75 pointer-events-none"></div>
    <?php endif; ?>
    <!-- Ambient Glow Backgrounds -->
    <div class="absolute top-1/4 -right-40 w-80 h-80 bg-brand-500/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-10 -left-40 w-80 h-80 bg-gold-500/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto">
            <?php if ($t('badge') !== ''): ?>
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-brand-950/90 border border-brand-500/30 text-brand-300 text-[11px] sm:text-xs font-bold mb-5 shadow-glow">
                <span class="w-2 h-2 rounded-full bg-brand-400 animate-pulse"></span>
                <span><?= $t('badge') ?></span>
            </div>
            <?php endif; ?>

            <!-- Main Heading (App-like scaled typography) -->
            <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight mb-4 sm:mb-6">
                <?= $t('title') ?>
                <?php if ($t('title_2') !== ''): ?>
                    <br>
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-brand-300 via-brand-400 to-gold-400">
                        <?= $t('title_2') ?>
                    </span>
                <?php endif; ?>
            </h1>

            <p class="text-xs sm:text-base lg:text-lg text-slate-300 font-normal leading-relaxed mb-8 max-w-2xl mx-auto px-2">
                <?= $tb('desc') ?>
            </p>

            <!-- CTA Buttons (Mobile App Grid 2-col / Desktop Flex Row) -->
            <div class="max-w-md mx-auto sm:max-w-none mb-12 sm:mb-16">
                <div class="grid grid-cols-2 sm:flex sm:flex-wrap sm:items-center sm:justify-center gap-2.5 sm:gap-3.5">
                    <?php if ($isLoggedIn): ?>
                        <a href="<?= url('home') ?>" class="col-span-2 sm:col-span-1 px-5 py-3 sm:px-7 sm:py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 text-white font-bold text-xs sm:text-sm hover:from-brand-500 hover:to-brand-600 shadow-glow transition-all flex items-center justify-center gap-2">
                            <i class="bi bi-speedometer2 text-base"></i>
                            <span><?= __('enter_dashboard') ?></span>
                        </a>
                    <?php else: ?>
                        <a href="<?= url('login') ?>" class="px-4 py-3 sm:px-7 sm:py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 text-white font-bold text-xs sm:text-sm hover:from-brand-500 hover:to-brand-600 shadow-glow transition-all flex items-center justify-center gap-2">
                            <i class="bi bi-box-arrow-in-left text-base"></i>
                            <span><?= __('login') ?></span>
                        </a>
                        <a href="<?= url('register') ?>" class="px-4 py-3 sm:px-7 sm:py-3.5 rounded-xl bg-slate-800/95 text-slate-100 font-bold text-xs sm:text-sm hover:bg-slate-700 border border-slate-700 transition-all flex items-center justify-center gap-2">
                            <i class="bi bi-person-plus text-base text-brand-400"></i>
                            <span><?= __('join_now') ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if ($hasSection('calculator')): ?>
                    <a href="#calculator" class="col-span-2 sm:col-span-1 px-4 py-2.5 sm:px-5 sm:py-3.5 rounded-xl bg-slate-800/40 hover:bg-slate-800 text-slate-300 hover:text-white font-semibold text-xs sm:text-sm flex items-center justify-center gap-1.5 transition-all border border-slate-700/40">
                        <i class="bi bi-calculator text-gold-400"></i>
                        <span><?= __('simulator') ?></span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($sec['show_stats'])): ?>
            <!-- Stats Ribbon -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 max-w-4xl mx-auto">
                <!-- 1. Total Shares -->
                <div class="bg-slate-800/60 backdrop-blur-sm border border-slate-700/60 rounded-2xl p-4 sm:p-5 text-center shadow-lg flex flex-col justify-center items-center min-h-[105px]">
                    <div class="flex items-baseline justify-center gap-1.5 mb-1">
                        <span class="text-3xl lg:text-4xl font-black text-brand-400 font-num leading-tight"><?= number_format($totalShares) ?></span>
                        <span class="text-xs font-bold text-brand-300"><?= is_rtl() ? 'سهم' : 'shares' ?></span>
                    </div>
                    <div class="text-xs font-bold text-slate-400"><?= $t('stat_shares') ?></div>
                </div>

                <!-- 2. Share Value -->
                <div class="bg-slate-800/60 backdrop-blur-sm border border-slate-700/60 rounded-2xl p-4 sm:p-5 text-center shadow-lg flex flex-col justify-center items-center min-h-[105px]">
                    <div class="flex items-baseline justify-center gap-1.5 mb-1">
                        <span class="text-3xl lg:text-4xl font-black text-gold-400 font-num leading-tight"><?= number_format($shareValue, (floor($shareValue) == $shareValue ? 0 : 2)) ?></span>
                        <span class="text-xs font-bold text-gold-300"><?= __('currency') ?></span>
                    </div>
                    <div class="text-xs font-bold text-slate-400"><?= $t('stat_share_val') ?></div>
                </div>

                <!-- 3. Members Count -->
                <div class="bg-slate-800/60 backdrop-blur-sm border border-slate-700/60 rounded-2xl p-4 sm:p-5 text-center shadow-lg flex flex-col justify-center items-center min-h-[105px]">
                    <div class="flex items-baseline justify-center gap-1.5 mb-1">
                        <span class="text-3xl lg:text-4xl font-black text-emerald-400 font-num leading-tight"><?= number_format($totalMembers) ?></span>
                        <span class="text-xs font-bold text-emerald-300"><?= is_rtl() ? 'عضو' : 'members' ?></span>
                    </div>
                    <div class="text-xs font-bold text-slate-400"><?= $t('stat_members') ?></div>
                </div>

                <!-- 4. Transparency -->
                <div class="bg-slate-800/60 backdrop-blur-sm border border-slate-700/60 rounded-2xl p-4 sm:p-5 text-center shadow-lg flex flex-col justify-center items-center min-h-[105px]">
                    <div class="flex items-baseline justify-center gap-1 mb-1">
                        <span class="text-3xl lg:text-4xl font-black text-sky-400 font-num leading-tight">100%</span>
                    </div>
                    <div class="text-xs font-bold text-slate-400"><?= $t('stat_transparency') ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</header>
