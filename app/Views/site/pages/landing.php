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

<?php
// Home page sections in the order the admin arranged them (Admin > Pages > Home page).
$homeSections = array_values(array_filter(\App\Models\HomePage::sections(), fn($s) => !empty($s['enabled'])));
$hasSection = fn(string $type): bool => in_array($type, array_column($homeSections, 'type'), true);
$skipNext = false;
foreach ($homeSections as $i => $sec):
    if ($skipNext) { $skipNext = false; continue; }
    // A quote section right after the charter sits beside it, as the page was designed.
    $pairedQuote = null;
    if ($sec['type'] === 'charter' && ($homeSections[$i + 1]['type'] ?? '') === 'hadith') {
        $pairedQuote = $homeSections[$i + 1];
        $skipNext = true;
    }
    $t = fn(string $field, ?array $src = null): string => e(\App\Models\HomePage::t($src ?? $sec, $field));
    $tb = fn(string $field, ?array $src = null): string => nl2br(e(\App\Models\HomePage::t($src ?? $sec, $field)));
    include __DIR__ . '/home/' . $sec['type'] . '.php';
endforeach;
?>

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

                <?php include base_dir() . "/app/Views/site/partials/social-links.php"; ?>
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

                <a href="<?= url('admin/login') ?>" class="!hidden inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-bold text-slate-300 hover:text-white hover:border-slate-700 transition-all">
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
