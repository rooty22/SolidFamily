<?php
$menuItems = site_menu_items();
$siteLogo = site_logo_url();
$siteName = site_name();
$siteSlogan = site_slogan();
$isSingleLang = is_single_language();
?>

<!-- Navbar (Glassmorphic Sticky Slim Header) -->
<nav x-data="{ mobileMenu: false }" class="sticky top-0 z-50 bg-slate-900/95 backdrop-blur-md border-b border-slate-800/80 text-white transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20 gap-4">
            <!-- Brand -->
            <a href="<?= url('/') ?>" class="flex items-center gap-2.5 group shrink-0">
                <?php if ($siteLogo): ?>
                    <img src="<?= e($siteLogo) ?>" alt="<?= e($siteName) ?>" class="h-9 sm:h-10 w-auto object-contain group-hover:scale-105 transition-transform">
                <?php else: ?>
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-tr from-brand-600 via-brand-500 to-emerald-400 flex items-center justify-center text-white shadow-md shadow-brand-500/20 group-hover:scale-105 transition-transform">
                        <i class="bi bi-<?= e(site_setting('logo_icon', 'safe2-fill')) ?> text-lg sm:text-xl"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <span class="text-base sm:text-lg font-black tracking-tight text-white block group-hover:text-brand-300 transition-colors"><?= e($siteName) ?></span>
                    <span class="text-[10px] text-brand-400 font-semibold tracking-wide uppercase block -mt-0.5"><?= e($siteSlogan) ?></span>
                </div>
            </a>

            <!-- Dynamic Desktop Nav Links -->
            <div class="hidden lg:flex items-center gap-1 xl:gap-2.5 font-semibold text-xs xl:text-sm text-slate-300">
                <?php foreach ($menuItems as $mItem): ?>
                    <a href="<?= e($mItem['url']) ?>" target="<?= e($mItem['target']) ?>" class="px-2.5 py-1.5 rounded-xl hover:text-white hover:bg-white/5 transition-all whitespace-nowrap">
                        <?= e($mItem['title']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Language Switcher & Auth Buttons -->
            <div class="hidden lg:flex items-center gap-2 xl:gap-2.5 shrink-0">
                <!-- Language Toggle Button (Hidden if single language mode is enforced) -->
                <?php if (!$isSingleLang): ?>
                    <a href="<?= url('lang/' . (current_locale() === 'ar' ? 'en' : 'ar')) ?>" 
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800/90 hover:bg-slate-700 text-xs font-bold text-slate-200 border border-slate-700/80 transition-all shrink-0">
                        <i class="bi bi-translate text-brand-400"></i>
                        <span><?= current_locale() === 'ar' ? 'English' : 'العربية' ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <a href="<?= url('home') ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 text-white font-bold text-xs xl:text-sm hover:from-brand-500 hover:to-brand-600 shadow-glow transition-all shrink-0">
                        <i class="bi bi-speedometer2"></i>
                        <span><?= __('member_portal') ?> (<?= e($currentMember['name'] ?? '') ?>)</span>
                    </a>
                <?php else: ?>
                    <a href="<?= url('login') ?>" class="px-3.5 py-2 rounded-xl text-slate-200 hover:text-white font-bold text-xs xl:text-sm hover:bg-slate-800 transition-all border border-slate-700 shrink-0">
                        <?= __('login') ?>
                    </a>
                    <a href="<?= url('register') ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 text-white font-bold text-xs xl:text-sm hover:from-brand-500 hover:to-brand-600 shadow-glow transition-all shrink-0">
                        <i class="bi bi-person-plus-fill"></i>
                        <span><?= __('join_now') ?></span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile / Tablet Actions -->
            <div class="lg:hidden flex items-center gap-2">
                <?php if (!$isSingleLang): ?>
                    <a href="<?= url('lang/' . (current_locale() === 'ar' ? 'en' : 'ar')) ?>" 
                       class="px-2.5 py-1.5 rounded-lg bg-slate-800 text-[11px] font-bold text-slate-200 border border-slate-700">
                        <?= current_locale() === 'ar' ? 'EN' : 'عربي' ?>
                    </a>
                <?php endif; ?>
                <button @click="mobileMenu = !mobileMenu" class="p-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 focus:outline-none">
                    <i class="bi text-2xl leading-none" :class="mobileMenu ? 'bi-x-lg' : 'bi-list'"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu panel -->
    <div x-show="mobileMenu" x-transition class="lg:hidden bg-slate-900 border-b border-slate-800 px-4 pt-2 pb-5 space-y-2">
        <?php foreach ($menuItems as $mItem): ?>
            <a href="<?= e($mItem['url']) ?>" target="<?= e($mItem['target']) ?>" @click="mobileMenu = false" class="block py-2 text-slate-300 font-semibold text-sm">
                <?= e($mItem['title']) ?>
            </a>
        <?php endforeach; ?>
        <div class="pt-3 border-t border-slate-800 grid grid-cols-2 gap-2">
            <?php if ($isLoggedIn): ?>
                <a href="<?= url('home') ?>" class="col-span-2 text-center py-2.5 rounded-xl bg-brand-600 text-white font-bold text-xs">
                    <?= __('member_portal') ?>
                </a>
            <?php else: ?>
                <a href="<?= url('login') ?>" class="text-center py-2 rounded-xl bg-slate-800 text-white font-bold text-xs border border-slate-700"><?= __('login') ?></a>
                <a href="<?= url('register') ?>" class="text-center py-2 rounded-xl bg-brand-600 text-white font-bold text-xs"><?= __('join_now') ?></a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php if (section_enabled('hero')): ?>
<!-- Hero Section -->
<header class="relative overflow-hidden bg-gradient-to-b from-slate-900 via-slate-950 to-slate-900 text-white py-12 sm:py-16 lg:py-24">
    <!-- Ambient Glow Backgrounds -->
    <div class="absolute top-1/4 -right-40 w-80 h-80 bg-brand-500/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-10 -left-40 w-80 h-80 bg-gold-500/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-brand-950/90 border border-brand-500/30 text-brand-300 text-[11px] sm:text-xs font-bold mb-5 shadow-glow">
                <span class="w-2 h-2 rounded-full bg-brand-400 animate-pulse"></span>
                <span><?= __('hero_badge') ?></span>
            </div>

            <!-- Main Heading (App-like scaled typography) -->
            <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight mb-4 sm:mb-6">
                <?= __('hero_title_1') ?> <br>
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-brand-300 via-brand-400 to-gold-400">
                    <?= __('hero_title_2') ?>
                </span>
            </h1>

            <p class="text-xs sm:text-base lg:text-lg text-slate-300 font-normal leading-relaxed mb-8 max-w-2xl mx-auto px-2">
                <?= __('hero_desc') ?>
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
                    <a href="#calculator" class="col-span-2 sm:col-span-1 px-4 py-2.5 sm:px-5 sm:py-3.5 rounded-xl bg-slate-800/40 hover:bg-slate-800 text-slate-300 hover:text-white font-semibold text-xs sm:text-sm flex items-center justify-center gap-1.5 transition-all border border-slate-700/40">
                        <i class="bi bi-calculator text-gold-400"></i>
                        <span><?= __('simulator') ?></span>
                    </a>
                </div>
            </div>

            <?php if (section_enabled('stats')): ?>
            <!-- Stats Ribbon (Fixed Layout & Text Wrapping as requested) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 max-w-4xl mx-auto">
                <!-- 1. Total Shares -->
                <div class="bg-slate-800/60 backdrop-blur-sm border border-slate-700/60 rounded-2xl p-4 sm:p-5 text-center shadow-lg flex flex-col justify-center items-center min-h-[105px]">
                    <div class="flex items-baseline justify-center gap-1.5 mb-1">
                        <span class="text-3xl lg:text-4xl font-black text-brand-400 font-num leading-tight"><?= number_format($totalShares) ?></span>
                        <span class="text-xs font-bold text-brand-300"><?= is_rtl() ? 'سهم' : 'shares' ?></span>
                    </div>
                    <div class="text-xs font-bold text-slate-400"><?= __('stat_shares') ?></div>
                </div>

                <!-- 2. Share Value -->
                <div class="bg-slate-800/60 backdrop-blur-sm border border-slate-700/60 rounded-2xl p-4 sm:p-5 text-center shadow-lg flex flex-col justify-center items-center min-h-[105px]">
                    <div class="flex items-baseline justify-center gap-1.5 mb-1">
                        <span class="text-3xl lg:text-4xl font-black text-gold-400 font-num leading-tight"><?= number_format($shareValue, (floor($shareValue) == $shareValue ? 0 : 2)) ?></span>
                        <span class="text-xs font-bold text-gold-300"><?= __('currency') ?></span>
                    </div>
                    <div class="text-xs font-bold text-slate-400"><?= __('stat_share_val') ?></div>
                </div>

                <!-- 3. Members Count -->
                <div class="bg-slate-800/60 backdrop-blur-sm border border-slate-700/60 rounded-2xl p-4 sm:p-5 text-center shadow-lg flex flex-col justify-center items-center min-h-[105px]">
                    <div class="flex items-baseline justify-center gap-1.5 mb-1">
                        <span class="text-3xl lg:text-4xl font-black text-emerald-400 font-num leading-tight"><?= number_format($totalMembers) ?></span>
                        <span class="text-xs font-bold text-emerald-300"><?= is_rtl() ? 'عضو' : 'members' ?></span>
                    </div>
                    <div class="text-xs font-bold text-slate-400"><?= __('stat_members') ?></div>
                </div>

                <!-- 4. Transparency -->
                <div class="bg-slate-800/60 backdrop-blur-sm border border-slate-700/60 rounded-2xl p-4 sm:p-5 text-center shadow-lg flex flex-col justify-center items-center min-h-[105px]">
                    <div class="flex items-baseline justify-center gap-1 mb-1">
                        <span class="text-3xl lg:text-4xl font-black text-sky-400 font-num leading-tight">100%</span>
                    </div>
                    <div class="text-xs font-bold text-slate-400"><?= __('stat_transparency') ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php endif; ?>

<?php if (section_enabled('features')): ?>
<!-- Pillars Section -->
<section id="features" class="py-16 sm:py-20 lg:py-28 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <h2 class="text-xs font-extrabold uppercase tracking-widest text-brand-600 mb-2"><?= __('pillars_title') ?></h2>
            <h3 class="text-3xl sm:text-4xl font-black text-slate-900 mb-4"><?= __('pillars_sub') ?></h3>
            <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
                <?= __('pillars_desc') ?>
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
            <!-- Pillar 1 -->
            <div class="bg-slate-50 hover:bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 hover:border-brand-500/50 hover:shadow-xl transition-all duration-300 group flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="bi bi-pie-chart-fill"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 mb-3"><?= __('pillar_1_title') ?></h4>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4">
                        <?= __('pillar_1_desc') ?>
                    </p>
                </div>
                <div class="text-xs font-bold text-emerald-700 flex items-center gap-1">
                    <span><?= __('pillar_1_title') ?></span>
                    <i class="bi bi-arrow-<?= is_rtl() ? 'left' : 'right' ?>"></i>
                </div>
            </div>

            <!-- Pillar 2 -->
            <div class="bg-slate-50 hover:bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 hover:border-gold-500/50 hover:shadow-xl transition-all duration-300 group flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 mb-3"><?= __('pillar_2_title') ?></h4>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4">
                        <?= __('pillar_2_desc') ?>
                    </p>
                </div>
                <div class="text-xs font-bold text-amber-700 flex items-center gap-1">
                    <span><?= __('pillar_2_title') ?></span>
                    <i class="bi bi-arrow-<?= is_rtl() ? 'left' : 'right' ?>"></i>
                </div>
            </div>

            <!-- Pillar 3 -->
            <div class="bg-slate-50 hover:bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 hover:border-blue-500/50 hover:shadow-xl transition-all duration-300 group flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="bi bi-bank2"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 mb-3"><?= __('pillar_3_title') ?></h4>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4">
                        <?= __('pillar_3_desc') ?>
                    </p>
                </div>
                <div class="text-xs font-bold text-blue-700 flex items-center gap-1">
                    <span><?= __('pillar_3_title') ?></span>
                    <i class="bi bi-arrow-<?= is_rtl() ? 'left' : 'right' ?>"></i>
                </div>
            </div>

            <!-- Pillar 4 -->
            <div class="bg-slate-50 hover:bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 hover:border-purple-500/50 hover:shadow-xl transition-all duration-300 group flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 mb-3"><?= __('pillar_4_title') ?></h4>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4">
                        <?= __('pillar_4_desc') ?>
                    </p>
                </div>
                <div class="text-xs font-bold text-purple-700 flex items-center gap-1">
                    <span><?= __('pillar_4_title') ?></span>
                    <i class="bi bi-arrow-<?= is_rtl() ? 'left' : 'right' ?>"></i>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (section_enabled('calculator')): ?>
<!-- Interactive Simulator Section (Alpine.js) -->
<section id="calculator" x-data="{
    shareValue: <?= (float)$shareValue ?>,
    foundingRatio: <?= (float)$foundingShareRatio ?>,
    maxLoanRatio: <?= (float)$maxLoanRatio ?>,
    currency: '<?= __('currency') ?>',
    shares: 2,
    loanMonths: 12,
    get monthlySub() { return this.shares * this.shareValue; },
    get foundingTotal() { return this.shares * this.foundingRatio; },
    get maxLoan() { return this.monthlySub * this.maxLoanRatio; },
    get monthlyInstallment() { return (this.maxLoan / this.loanMonths).toFixed(0); }
}" class="py-16 sm:py-20 lg:py-28 bg-slate-900 text-white relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <span class="text-xs font-extrabold uppercase tracking-widest text-gold-400 mb-2 block"><?= __('sim_badge') ?></span>
            <h2 class="text-3xl sm:text-4xl font-black text-white mb-4"><?= __('sim_title') ?></h2>
            <p class="text-slate-400 text-sm sm:text-base leading-relaxed">
                <?= __('sim_desc') ?>
            </p>
        </div>

        <div class="max-w-4xl mx-auto bg-slate-950/80 rounded-3xl p-6 sm:p-10 border border-slate-800 shadow-2xl backdrop-blur-xl">
            <div class="grid md:grid-cols-12 gap-8 items-center">
                <!-- Sliders Column -->
                <div class="md:col-span-7 space-y-6">
                    <!-- Shares Slider -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-sm font-bold text-slate-300"><?= __('sim_shares_label') ?></label>
                            <span class="text-xl font-black text-brand-400 font-num">
                                <span x-text="shares"></span> <?= is_rtl() ? 'أسهم' : 'shares' ?>
                            </span>
                        </div>
                        <input type="range" min="1" max="20" step="1" x-model.number="shares" class="w-full accent-brand-500 cursor-pointer h-2 bg-slate-800 rounded-lg">
                        <div class="flex justify-between text-[11px] text-slate-400 mt-1">
                            <span>1</span>
                            <span>5</span>
                            <span>10</span>
                            <span>15</span>
                            <span>20</span>
                        </div>
                    </div>

                    <!-- Repayment Months Slider -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-sm font-bold text-slate-300"><?= __('sim_loan_months_label') ?></label>
                            <span class="text-xl font-black text-gold-400 font-num">
                                <span x-text="loanMonths"></span> <?= is_rtl() ? 'شهر' : 'months' ?>
                            </span>
                        </div>
                        <input type="range" min="3" max="36" step="1" x-model.number="loanMonths" class="w-full accent-gold-500 cursor-pointer h-2 bg-slate-800 rounded-lg">
                        <div class="flex justify-between text-[11px] text-slate-400 mt-1">
                            <span>3</span>
                            <span>12</span>
                            <span>24</span>
                            <span>36</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 text-xs text-slate-400 leading-relaxed">
                        <i class="bi bi-info-circle-fill text-gold-400 me-1"></i>
                        <?= __('sim_disclaimer') ?>
                    </div>
                </div>

                <!-- Live Results Card -->
                <div class="md:col-span-5 bg-gradient-to-b from-slate-900 to-slate-900/90 rounded-2xl p-6 border border-slate-800 space-y-4">
                    <div>
                        <div class="text-xs font-semibold text-slate-400 mb-1"><?= __('sim_monthly_sub') ?></div>
                        <div class="text-2xl font-black text-brand-400 font-num flex items-baseline gap-1.5">
                            <span x-text="monthlySub.toLocaleString()"></span>
                            <span class="text-xs font-bold text-brand-300" x-text="currency + '<?= is_rtl() ? '/شهر' : '/mo' ?>'"></span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800">
                        <div class="text-xs font-semibold text-slate-400 mb-1"><?= __('sim_founding_total') ?></div>
                        <div class="text-2xl font-black text-sky-400 font-num flex items-baseline gap-1.5">
                            <span x-text="foundingTotal.toLocaleString()"></span>
                            <span class="text-xs font-bold text-sky-300" x-text="currency"></span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800">
                        <div class="text-xs font-semibold text-slate-400 mb-1"><?= __('sim_max_loan') ?></div>
                        <div class="text-2xl sm:text-3xl font-black text-gold-400 font-num flex items-baseline gap-1.5">
                            <span x-text="maxLoan.toLocaleString()"></span>
                            <span class="text-xs font-bold text-gold-300" x-text="currency"></span>
                        </div>
                        <div class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                            <span><?= __('sim_installment') ?>:</span>
                            <b class="text-white font-num" x-text="monthlyInstallment + ' ' + currency + '<?= is_rtl() ? '/شهر' : '/mo' ?>'"></b>
                        </div>
                    </div>

                    <div class="pt-4">
                        <a href="<?= $isLoggedIn ? url('loans/request') : url('login') ?>" class="w-full block text-center py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-500 hover:to-brand-600 text-white font-bold text-sm shadow-glow transition-all">
                            <?= $isLoggedIn ? __('sim_apply_loan') : __('sim_login_to_apply') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (section_enabled('charter') || section_enabled('hadith')): ?>
<!-- Family Charter Section -->
<section id="charter" class="py-16 sm:py-20 lg:py-28 bg-slate-50 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-12 items-center">
            <?php if (section_enabled('charter')): ?>
            <div>
                <span class="text-xs font-extrabold uppercase tracking-widest text-brand-600 mb-2 block"><?= __('charter') ?></span>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mb-6 leading-snug">
                    <?= is_rtl() ? 'تنظيم عادل يحفظ صلة الرحم ويديم البركة' : 'Fair Bylaws Protecting Kinship & Lasting Prosperity' ?>
                </h2>
                <p class="text-slate-600 leading-relaxed mb-6 text-sm sm:text-base">
                    <?= is_rtl() 
                        ? 'يقوم الصندوق على مبادئ التكافل الإسلامي النقي، حيث يُقرض المحتاج دون اشتراط أي زيادة، وتُستثمر المدخرات في منافذ آمنة، ليكون سداً منيعاً يحمي الأسرة من نوائب الدهر.'
                        : 'The Fund is built upon pure mutual solidarity, offering benevolent interest-free loans and securing family savings through structured, transparent governance.' ?>
                </p>

                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold flex-shrink-0 mt-1">
                            <i class="bi bi-check2"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-base"><?= is_rtl() ? 'لا فوائد ربوية على الإطلاق' : 'Zero Interest Financing' ?></h4>
                            <p class="text-slate-500 text-sm"><?= is_rtl() ? 'جميع القروض ميسرة لوجه الله وصلة للرحم، مع مصاريف إدارية رمزية مقطوعة لتغطية التشغيل.' : 'All loans are completely interest-free with nominal fixed administrative expenses.' ?></p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold flex-shrink-0 mt-1">
                            <i class="bi bi-check2"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-base"><?= is_rtl() ? 'حفظ الحقوق وتوثيقها' : 'Documented Financial Rights' ?></h4>
                            <p class="text-slate-500 text-sm"><?= is_rtl() ? 'كل سهم ومبلغ سداد موثق بسجل مالي مركزي يحمي حقوق كل فرد وورثته مستقبلاً.' : 'Every share and payment is permanently registered in an audited central ledger.' ?></p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold flex-shrink-0 mt-1">
                            <i class="bi bi-check2"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-base"><?= is_rtl() ? 'لجنة إشرافية مستقلة' : 'Elected Supervisory Committee' ?></h4>
                            <p class="text-slate-500 text-sm"><?= is_rtl() ? 'لجنة من أعيان وخبراء العائلة تتولى إدارة الطلبات ومراجعة الميزانيات واعتماد التقارير السنوية.' : 'A dedicated family council manages requests, audits budgets, and oversees disbursements.' ?></p>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <a href="<?= url('page/terms') ?>" class="inline-flex items-center gap-2 text-brand-700 font-extrabold hover:text-brand-800 text-sm">
                        <span><?= __('terms') ?></span>
                        <i class="bi bi-arrow-<?= is_rtl() ? 'left' : 'right' ?>"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (section_enabled('hadith')): ?>
            <!-- Visual Quote Card -->
            <div class="<?= !section_enabled('charter') ? 'lg:col-span-2 max-w-2xl mx-auto' : '' ?>">
                <div class="bg-gradient-to-tr from-brand-900 via-slate-900 to-slate-950 rounded-3xl p-8 sm:p-12 text-white shadow-2xl border border-slate-700">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-brand-500/20 border border-brand-500/40 flex items-center justify-center text-2xl text-brand-400">
                            <i class="bi bi-quote"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-lg sm:text-xl text-white"><?= is_rtl() ? 'صلة الرحم والبركة' : 'Family Ties & Blessing' ?></h3>
                            <p class="text-xs text-brand-300"><?= e($siteName) ?></p>
                        </div>
                    </div>

                    <blockquote class="text-base sm:text-lg font-medium leading-relaxed mb-6 text-slate-200">
                        <?= is_rtl() 
                            ? 'قال رسول الله صلى الله عليه وسلم: "مَن سَرَّهُ أَنْ يُبْسَطَ لَهُ فِي رِزْقِهِ، وَأَنْ يُنْسَأَ لَهُ فِي أَثَرِهِ، فَلْيَصِلْ رَحِمَهُ."'
                            : '"Whoever would like his provision to be abundant and his lifespan to be extended, let him maintain the ties of kinship."' ?>
                    </blockquote>

                    <div class="pt-5 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                        <span><?= is_rtl() ? 'صحيح البخاري ومسلم' : 'Sahih Al-Bukhari & Muslim' ?></span>
                        <span class="text-gold-400 font-bold"><?= e($siteName) ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (section_enabled('cta')): ?>
<!-- Call To Action Section -->
<section class="py-14 bg-gradient-to-r from-slate-950 via-brand-950 to-slate-950 text-white border-t border-slate-800 relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-start">
        <div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-brand-500/20 text-brand-300 border border-brand-500/30 mb-2 inline-block">
                <?= is_rtl() ? 'بوابة العائلة الرقمية' : 'Digital Family Gateway' ?>
            </span>
            <h3 class="text-2xl sm:text-3xl font-black text-white mb-2">
                <?= is_rtl() ? 'انضم اليوم وابدأ في بناء مستقبلك المالي التكافلي' : 'Join Today & Build Your Mutual Savings Future' ?>
            </h3>
            <p class="text-xs sm:text-sm text-slate-300 max-w-xl">
                <?= is_rtl() ? 'سجل عضويتك للمشاركة في الاكتتاب، الاستفادة من القروض الحسنة بدون فوائد، ومتابعة حسابك بكل شفافية.' : 'Register now to participate in shares, benefit from 0% loans, and track your equity with transparency.' ?>
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <a href="<?= url('register') ?>" class="px-6 py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-500 hover:to-brand-600 text-white font-bold text-xs sm:text-sm shadow-glow transition-all flex items-center gap-2">
                <i class="bi bi-person-plus-fill"></i>
                <span><?= __('join_now') ?></span>
            </a>
            <a href="<?= url('page/contact') ?>" class="px-5 py-3.5 rounded-xl bg-white/10 hover:bg-white/15 text-slate-200 hover:text-white font-bold text-xs sm:text-sm transition-colors flex items-center gap-2">
                <i class="bi bi-chat-dots-fill text-gold-400"></i>
                <span><?= __('contact_us') ?></span>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Footer with Dynamic Branding, Contacts & Socials -->
<footer class="bg-slate-950 text-slate-400 py-14 border-t border-slate-800 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8 mb-10">
            <!-- Brand & Slogan -->
            <div class="sm:col-span-2">
                <div class="flex items-center gap-3 mb-4">
                    <?php if ($siteLogo): ?>
                        <img src="<?= e($siteLogo) ?>" alt="<?= e($siteName) ?>" class="h-10 w-auto object-contain">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-gold-500 flex items-center justify-center text-white text-xl font-black">
                            <i class="bi bi-<?= e(site_setting('logo_icon', 'safe2-fill')) ?>"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <span class="text-lg font-black text-white block"><?= e($siteName) ?></span>
                        <span class="text-xs text-brand-400 font-semibold"><?= e($siteSlogan) ?></span>
                    </div>
                </div>
                <p class="text-xs sm:text-sm text-slate-400 max-w-sm leading-relaxed mb-4">
                    <?= is_rtl() ? 'نهدف إلى بناء مجتمع أسري مترابط مالياً واجتماعياً، وتوفير الأمان والاستقرار لجميع أفراد العائلة.' : 'Aiming to build a financially secure, united family community with interest-free solidarity.' ?>
                </p>

                <!-- Social Media Icons -->
                <div class="flex items-center gap-2.5">
                    <?php if ($tw = site_setting('social_twitter')): ?>
                        <a href="<?= e($tw) ?>" target="_blank" class="w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:border-slate-700 flex items-center justify-center text-xs transition-colors" title="Twitter / X">
                            <i class="bi bi-twitter-x"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($ig = site_setting('social_instagram')): ?>
                        <a href="<?= e($ig) ?>" target="_blank" class="w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:border-slate-700 flex items-center justify-center text-xs transition-colors" title="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($tg = site_setting('social_telegram')): ?>
                        <a href="<?= e($tg) ?>" target="_blank" class="w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:border-slate-700 flex items-center justify-center text-xs transition-colors" title="Telegram">
                            <i class="bi bi-telegram"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($waLink = site_whatsapp_link()): ?>
                        <a href="<?= e($waLink) ?>" target="_blank" class="w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 text-emerald-400 hover:text-white hover:border-slate-700 flex items-center justify-center text-xs transition-colors" title="WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dynamic Quick Links from Menu -->
            <div>
                <h4 class="text-xs font-extrabold text-white uppercase tracking-wider mb-4"><?= is_rtl() ? 'روابط سريعة' : 'Navigation' ?></h4>
                <ul class="space-y-2 text-xs">
                    <?php foreach ($menuItems as $m): ?>
                        <li>
                            <a href="<?= e($m['url']) ?>" target="<?= e($m['target']) ?>" class="hover:text-brand-400 transition-colors">
                                <?= e($m['title']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Official Contact Column -->
            <div>
                <h4 class="text-xs font-extrabold text-white uppercase tracking-wider mb-4"><?= is_rtl() ? 'التواصل الرسمي' : 'Official Contact' ?></h4>
                <div class="space-y-2.5 text-xs text-slate-400 mb-5">
                    <?php if ($p = site_phone()): ?>
                        <div class="flex items-center gap-2">
                            <i class="bi bi-telephone text-brand-400"></i>
                            <a href="tel:<?= e($p) ?>" class="hover:text-white font-numeric" dir="ltr"><?= e($p) ?></a>
                        </div>
                    <?php endif; ?>
                    <?php if ($em = site_email()): ?>
                        <div class="flex items-center gap-2">
                            <i class="bi bi-envelope text-brand-400"></i>
                            <a href="mailto:<?= e($em) ?>" class="hover:text-white"><?= e($em) ?></a>
                        </div>
                    <?php endif; ?>
                    <?php if ($addr = site_address()): ?>
                        <div class="flex items-start gap-2">
                            <i class="bi bi-geo-alt text-brand-400 mt-0.5"></i>
                            <span><?= e($addr) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="<?= url('admin/login') ?>" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-bold text-slate-300 hover:text-white hover:border-slate-700 transition-all">
                    <i class="bi bi-shield-lock text-brand-400"></i>
                    <span><?= __('admin_portal') ?></span>
                </a>
            </div>
        </div>

        <div class="pt-6 border-t border-slate-900 text-center text-xs text-slate-500 pb-16 md:pb-0">
            &copy; <?= date('Y') ?> <?= __('brand_name') ?>. <?= is_rtl() ? 'جميع الحقوق محفوظة.' : 'All rights reserved.' ?>
        </div>
    </div>
</footer>

<!-- Mobile App Bottom Navigation Bar -->
<div class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 px-2 py-1.5 shadow-2xl">
    <div class="grid grid-cols-5 gap-1 text-center">
        <a href="<?= url('/') ?>" class="flex flex-col items-center justify-center py-1 text-brand-400 font-bold transition-colors">
            <i class="bi bi-house-door-fill text-lg"></i>
            <span class="text-[10px] mt-0.5"><?= __('home') ?></span>
        </a>
        <a href="#features" class="flex flex-col items-center justify-center py-1 text-slate-400 hover:text-brand-300 transition-colors">
            <i class="bi bi-grid text-lg"></i>
            <span class="text-[10px] font-medium mt-0.5"><?= __('features') ?></span>
        </a>
        <a href="#calculator" class="flex flex-col items-center justify-center py-1 text-slate-400 hover:text-brand-300 transition-colors">
            <i class="bi bi-calculator text-lg"></i>
            <span class="text-[10px] font-medium mt-0.5"><?= __('simulator') ?></span>
        </a>
        <a href="<?= url('page/about') ?>" class="flex flex-col items-center justify-center py-1 text-slate-400 hover:text-brand-300 transition-colors">
            <i class="bi bi-info-circle text-lg"></i>
            <span class="text-[10px] font-medium mt-0.5"><?= __('about_us') ?></span>
        </a>
        <?php if ($isLoggedIn): ?>
            <a href="<?= url('home') ?>" class="flex flex-col items-center justify-center py-1 text-brand-400 font-bold">
                <i class="bi bi-speedometer2 text-lg"></i>
                <span class="text-[10px] mt-0.5"><?= __('account') ?></span>
            </a>
        <?php else: ?>
            <a href="<?= url('login') ?>" class="flex flex-col items-center justify-center py-1 text-slate-400 hover:text-brand-300 transition-colors">
                <i class="bi bi-box-arrow-in-left text-lg"></i>
                <span class="text-[10px] font-medium mt-0.5"><?= __('login') ?></span>
            </a>
        <?php endif; ?>
    </div>
</div>
