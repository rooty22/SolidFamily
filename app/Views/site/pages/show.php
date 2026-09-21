<?php
$title = \App\Models\ContentPage::getTitle($page);
$body = \App\Models\ContentPage::getContent($page);
$sections = \App\Models\ContentPage::getSections($page);
$isEn = is_en();
$brandLogo = site_logo_url();
$brandName = site_name();
$brandSlogan = site_slogan();
$menuItems = site_menu_items();
$isSingleLang = is_single_language();
?>

<!-- Internal Page Top Header -->
<header x-data="{ mobileMenu: false }" class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200/80 transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 lg:h-20 flex items-center justify-between gap-4">
        <!-- Logo & Brand -->
        <a href="<?= url('/') ?>" class="flex items-center gap-3 group shrink-0">
            <?php if ($brandLogo): ?>
                <img src="<?= e($brandLogo) ?>" alt="<?= e($brandName) ?>" class="h-9 sm:h-10 w-auto max-w-[140px] object-contain rounded-lg group-hover:scale-105 transition-transform" />
            <?php else: ?>
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-emerald-400 flex items-center justify-center text-white shadow-md shadow-brand-500/20 group-hover:scale-105 transition-transform">
                    <i class="bi bi-safe2-fill text-lg sm:text-xl"></i>
                </div>
            <?php endif; ?>
            <div>
                <span class="text-base font-black tracking-tight text-slate-900 group-hover:text-brand-600 transition-colors">
                    <?= e($brandName) ?>
                </span>
                <span class="block text-[10px] font-semibold text-brand-600 -mt-0.5 tracking-wider uppercase">
                    <?= e($brandSlogan) ?>
                </span>
            </div>
        </a>

        <!-- Desktop Navigation Links -->
        <nav class="hidden lg:flex items-center gap-1 xl:gap-2.5 text-xs xl:text-sm font-semibold text-slate-600">
            <?php foreach ($menuItems as $mItem): ?>
                <?php 
                    $mTitle = $mItem['title'] ?? ($isEn ? (!empty($mItem['title_en']) ? $mItem['title_en'] : ($mItem['title_ar'] ?? '')) : ($mItem['title_ar'] ?? ''));
                    $mTarget = !empty($mItem['target']) ? $mItem['target'] : '_self';
                    $mUrl = !empty($mItem['url']) ? (str_starts_with($mItem['url'], 'http') ? $mItem['url'] : url($mItem['url'])) : url('/');
                    $isActive = (isset($page['slug']) && str_ends_with($mUrl, $page['slug']));
                ?>
                <a href="<?= e($mUrl) ?>" target="<?= e($mTarget) ?>" class="px-2.5 py-1.5 rounded-xl transition-all whitespace-nowrap <?= $isActive ? 'text-brand-600 font-bold bg-brand-50' : 'hover:text-brand-600 hover:bg-slate-50' ?>"><?= e($mTitle) ?></a>
            <?php endforeach; ?>
        </nav>

        <!-- Actions: Language & Auth -->
        <div class="hidden lg:flex items-center gap-2 xl:gap-2.5 shrink-0">
            <!-- Language Toggle Button (same as Home; hidden if single-language mode is active) -->
            <?php if (!$isSingleLang): ?>
                <a href="<?= url('lang/' . ($isEn ? 'ar' : 'en')) ?>"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 border border-slate-200 transition-all shrink-0">
                    <i class="bi bi-translate text-brand-600"></i>
                    <span><?= $isEn ? 'العربية' : 'English' ?></span>
                </a>
            <?php endif; ?>

            <!-- Login / Member Portal -->
            <?php if (!empty($isLoggedIn)): ?>
                <a href="<?= url('member/dashboard') ?>" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 shadow-sm shadow-brand-600/20 inline-flex items-center gap-1.5 transition-all shrink-0">
                    <i class="bi bi-speedometer2"></i>
                    <span><?= __('member_portal') ?></span>
                </a>
            <?php else: ?>
                <a href="<?= url('login') ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 inline-flex items-center gap-1.5 transition-colors border border-slate-200 shrink-0">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span><?= __('login') ?></span>
                </a>
                <a href="<?= url('register') ?>" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 shadow-sm shadow-brand-600/20 inline-flex items-center gap-1.5 transition-all shrink-0">
                    <i class="bi bi-person-plus"></i>
                    <span><?= __('register') ?></span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile / Tablet Actions -->
        <div class="lg:hidden flex items-center gap-2">
            <?php if (!$isSingleLang): ?>
                <a href="<?= url('lang/' . ($isEn ? 'ar' : 'en')) ?>" 
                   class="px-2.5 py-1.5 rounded-lg bg-slate-100 text-[11px] font-bold text-slate-700 border border-slate-200">
                    <?= $isEn ? 'عربي' : 'EN' ?>
                </a>
            <?php endif; ?>
            <button @click="mobileMenu = !mobileMenu" class="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none">
                <i class="bi text-2xl leading-none" :class="mobileMenu ? 'bi-x-lg' : 'bi-list'"></i>
            </button>
        </div>
    </div>

    <!-- Mobile menu panel -->
    <div x-show="mobileMenu" x-transition class="lg:hidden bg-white border-b border-slate-200 px-4 pt-2 pb-5 space-y-2 shadow-xl">
        <?php foreach ($menuItems as $mItem): ?>
            <?php 
                $mTitle = $mItem['title'] ?? ($isEn ? (!empty($mItem['title_en']) ? $mItem['title_en'] : ($mItem['title_ar'] ?? '')) : ($mItem['title_ar'] ?? ''));
                $mTarget = !empty($mItem['target']) ? $mItem['target'] : '_self';
                $mUrl = !empty($mItem['url']) ? (str_starts_with($mItem['url'], 'http') ? $mItem['url'] : url($mItem['url'])) : url('/');
            ?>
            <a href="<?= e($mUrl) ?>" target="<?= e($mTarget) ?>" @click="mobileMenu = false" class="block py-2 text-slate-700 hover:text-brand-600 font-semibold text-sm">
                <?= e($mTitle) ?>
            </a>
        <?php endforeach; ?>
        <div class="pt-3 border-t border-slate-100 grid grid-cols-2 gap-2">
            <?php if (!empty($isLoggedIn)): ?>
                <a href="<?= url('member/dashboard') ?>" class="col-span-2 text-center py-2.5 rounded-xl bg-brand-600 text-white font-bold text-xs">
                    <?= __('member_portal') ?>
                </a>
            <?php else: ?>
                <a href="<?= url('login') ?>" class="text-center py-2 rounded-xl bg-slate-100 text-slate-800 font-bold text-xs">
                    <?= __('login') ?>
                </a>
                <a href="<?= url('register') ?>" class="text-center py-2 rounded-xl bg-brand-600 text-white font-bold text-xs">
                    <?= __('register') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Main Page Body -->
<main class="flex-grow pb-24 md:pb-16">
    <!-- Breadcrumb & Hero Banner -->
    <section class="relative bg-gradient-to-b from-slate-900 via-navy-950 to-slate-900 text-white py-14 sm:py-20 overflow-hidden">
        <!-- Ambient background glows -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-24 <?= is_rtl() ? 'right-1/4' : 'left-1/4' ?> w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 <?= is_rtl() ? 'left-1/4' : 'right-1/4' ?> w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 relative z-10 text-center">
            <!-- Breadcrumbs -->
            <div class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 bg-white/5 border border-white/10 px-3.5 py-1.5 rounded-full mb-5">
                <a href="<?= url('/') ?>" class="hover:text-white transition-colors"><?= __('home') ?></a>
                <i class="bi <?= is_rtl() ? 'bi-chevron-left' : 'bi-chevron-right' ?> text-[10px] opacity-60"></i>
                <span class="text-brand-400"><?= e($title) ?></span>
            </div>

            <!-- Page Main Title -->
            <h1 class="text-2xl sm:text-4xl font-black tracking-tight text-white mb-4">
                <?= e($title) ?>
            </h1>

            <div class="flex items-center justify-center gap-4 text-xs text-slate-400">
                <span class="inline-flex items-center gap-1.5">
                    <i class="bi bi-clock-history text-brand-400"></i>
                    <span><?= $isEn ? 'Last updated: ' . date('M d, Y', strtotime($page['updated_at'])) : 'آخر تحديث: ' . date_ar($page['updated_at'], 'Y/m/d') ?></span>
                </span>
                <span class="w-1 h-1 rounded-full bg-slate-600"></span>
                <span class="inline-flex items-center gap-1.5">
                    <i class="bi bi-shield-check text-brand-400"></i>
                    <span><?= $isEn ? 'Official Verified Document' : 'وثيقة رسمية معتمدة' ?></span>
                </span>
            </div>
        </div>
    </section>

    <!-- Content Area Container -->
    <div class="max-w-5xl mx-auto px-4 sm:px-6 -mt-8 relative z-20 space-y-8">
        <!-- Lead Content Card -->
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xl shadow-slate-200/50 p-6 sm:p-10 relative overflow-hidden">
            <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-brand-500 via-emerald-400 to-teal-500"></div>
            
            <div class="prose prose-slate max-w-none">
                <div class="text-base sm:text-lg text-slate-700 leading-relaxed font-normal whitespace-pre-line">
                    <?= nl2br(e($body)) ?>
                </div>
            </div>
        </div>

        <!-- Dynamic Sections Grid -->
        <?php if (!empty($sections)): ?>
            <div class="space-y-4">
                <div class="flex items-center justify-between px-1">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 tracking-tight">
                            <?= $isEn ? 'Detailed Articles & Provisions' : 'البنود والتفاصيل التنظيمية' ?>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?= $isEn ? 'Specific regulations and operational parameters of this document' : 'محددات وقواعد تفصيلية موثقة وفق اللائحة' ?>
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold font-numeric">
                        <?= count($sections) ?> <?= $isEn ? 'items' : 'بنود' ?>
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <?php foreach ($sections as $index => $sec): ?>
                        <?php
                            $secBadge = $isEn ? (!empty($sec['badge_en']) ? $sec['badge_en'] : ($sec['badge'] ?? '')) : ($sec['badge'] ?? '');
                            $secTitle = $isEn ? (!empty($sec['title_en']) ? $sec['title_en'] : ($sec['title_ar'] ?? '')) : ($sec['title_ar'] ?? '');
                            $secBody  = $isEn ? (!empty($sec['body_en']) ? $sec['body_en'] : ($sec['body_ar'] ?? '')) : ($sec['body_ar'] ?? '');
                            $icon     = !empty($sec['icon']) ? $sec['icon'] : 'check-circle';
                        ?>
                        <div class="group bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md hover:border-brand-300/80 transition-all duration-300 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-3 mb-4">
                                    <div class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-xl group-hover:scale-110 group-hover:bg-brand-600 group-hover:text-white transition-all duration-300">
                                        <i class="bi bi-<?= e($icon) ?>"></i>
                                    </div>
                                    <?php if (!empty($secBadge)): ?>
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 group-hover:bg-brand-50 group-hover:text-brand-700 transition-colors">
                                            <?= e($secBadge) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="text-base font-bold text-slate-900 group-hover:text-brand-700 transition-colors mb-2">
                                    <?= e($secTitle) ?>
                                </h3>

                                <p class="text-sm text-slate-600 leading-relaxed">
                                    <?= nl2br(e($secBody)) ?>
                                </p>
                            </div>

                            <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                                <span><?= $isEn ? 'Section' : 'بند' ?> #<?= $index + 1 ?></span>
                                <i class="bi bi-shield-check text-brand-500"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Dynamic Interactive Form (Enabled via Dashboard) -->
        <?php if (!empty($formConfig) && !empty($formConfig['enabled'])): ?>
            <?php
                $fTitle = $isEn ? (!empty($formConfig['title_en']) ? $formConfig['title_en'] : ($formConfig['title_ar'] ?? '')) : ($formConfig['title_ar'] ?? '');
                $fDesc  = $isEn ? (!empty($formConfig['desc_en']) ? $formConfig['desc_en'] : ($formConfig['desc_ar'] ?? '')) : ($formConfig['desc_ar'] ?? '');
                $flashSuccess = flash('success');
                $flashError = flash('error');
            ?>
            <div id="form" class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200 shadow-xl p-6 sm:p-10 relative overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-emerald-500 via-teal-400 to-brand-600"></div>

                <!-- Form Header -->
                <div class="text-center sm:text-start max-w-2xl mb-8">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold mb-3 border border-emerald-200/80">
                        <i class="bi bi-chat-heart-fill text-emerald-600"></i>
                        <span><?= $isEn ? 'Direct Communication Portal' : 'بوابة التواصل والاستفسار المباشر' ?></span>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mb-2">
                        <?= e($fTitle ?: ($isEn ? 'Get in Touch' : 'تواصل معنا')) ?>
                    </h3>
                    <?php if (!empty($fDesc)): ?>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            <?= e($fDesc) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Flash Messages -->
                <?php if ($flashSuccess): ?>
                    <div class="alert-modern alert-success-modern mb-6 flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-bold">
                        <i class="bi bi-check-circle-fill text-xl text-emerald-600 shrink-0"></i>
                        <span><?= e($flashSuccess) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($flashError): ?>
                    <div class="alert-modern alert-danger-modern mb-6 flex items-center gap-3 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-bold">
                        <i class="bi bi-exclamation-triangle-fill text-xl text-rose-600 shrink-0"></i>
                        <span><?= e($flashError) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Form Body -->
                <form method="post" action="<?= url('page/' . $page['slug'] . '/form') ?>" class="space-y-4">
                    <?= csrf_field() ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Full Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                <?= $isEn ? 'Full Name' : 'الاسم الكامل' ?> <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 <?= is_rtl() ? 'right-0 pr-3.5' : 'left-0 pl-3.5' ?> flex items-center pointer-events-none text-slate-400">
                                    <i class="bi bi-person text-base"></i>
                                </span>
                                <input type="text" name="name" value="<?= e($currentMember['name'] ?? old('name')) ?>" required 
                                       placeholder="<?= $isEn ? 'Enter your name' : 'أدخل اسمك الكريم' ?>"
                                       class="w-full text-xs py-2.5 <?= is_rtl() ? 'pr-10 pl-3' : 'pl-10 pr-3' ?> rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 font-medium">
                            </div>
                        </div>

                        <!-- Phone / Mobile -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                <?= $isEn ? 'Mobile Phone' : 'رقم الجوال' ?> <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 <?= is_rtl() ? 'right-0 pr-3.5' : 'left-0 pl-3.5' ?> flex items-center pointer-events-none text-slate-400">
                                    <i class="bi bi-telephone text-base"></i>
                                </span>
                                <input type="text" name="phone" value="<?= e($currentMember['mobile'] ?? old('phone')) ?>" required 
                                       placeholder="05xxxxxxxx"
                                       class="w-full text-xs py-2.5 <?= is_rtl() ? 'pr-10 pl-3' : 'pl-10 pr-3' ?> rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 font-numeric font-medium" dir="ltr">
                            </div>
                        </div>

                        <!-- Optional Email -->
                        <?php if (!empty($formConfig['require_email'])): ?>
                            <div class="sm:col-span-1">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    <?= $isEn ? 'Email Address' : 'البريد الإلكتروني' ?>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 <?= is_rtl() ? 'right-0 pr-3.5' : 'left-0 pl-3.5' ?> flex items-center pointer-events-none text-slate-400">
                                        <i class="bi bi-envelope text-base"></i>
                                    </span>
                                    <input type="email" name="email" value="<?= e($currentMember['email'] ?? old('email')) ?>" 
                                           placeholder="user@example.com"
                                           class="w-full text-xs py-2.5 <?= is_rtl() ? 'pr-10 pl-3' : 'pl-10 pr-3' ?> rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 font-medium" dir="ltr">
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Optional Subject -->
                        <?php if (!empty($formConfig['require_subject'])): ?>
                            <div class="<?= !empty($formConfig['require_email']) ? 'sm:col-span-1' : 'sm:col-span-2' ?>">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    <?= $isEn ? 'Inquiry Topic / Subject' : 'الموضوع أو نوع الطلب' ?>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 <?= is_rtl() ? 'right-0 pr-3.5' : 'left-0 pl-3.5' ?> flex items-center pointer-events-none text-slate-400">
                                        <i class="bi bi-tag text-base"></i>
                                    </span>
                                    <input type="text" name="subject" value="<?= old('subject') ?>" 
                                           placeholder="<?= $isEn ? 'e.g. Question regarding shares' : 'مثال: استفسار بخصوص الأسهم أو القروض' ?>"
                                           class="w-full text-xs py-2.5 <?= is_rtl() ? 'pr-10 pl-3' : 'pl-10 pr-3' ?> rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 font-medium">
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Message Body -->
                        <div class="col-span-full">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                <?= $isEn ? 'Your Message / Inquiry Details' : 'نص الرسالة أو الاستفسار بالتفصيل' ?> <span class="text-rose-500">*</span>
                            </label>
                            <textarea name="message" rows="4" required 
                                      placeholder="<?= $isEn ? 'Write your message here...' : 'اكتب رسالتك أو استفسارك هنا بكل وضوح...' ?>"
                                      class="w-full text-xs p-3.5 rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 font-medium leading-relaxed"></textarea>
                        </div>
                    </div>

                    <!-- Submit & Privacy Note -->
                    <div class="pt-3 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <i class="bi bi-shield-lock-fill text-brand-600 text-sm"></i>
                            <span><?= $isEn ? 'Protected & confidential within family fund committee.' : 'رسالتكم تصل مباشرة للجنة الصندوق بسرية وأمان تام.' ?></span>
                        </div>
                        <button type="submit" class="w-full sm:w-auto px-7 py-3 rounded-xl text-xs sm:text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 shadow-md shadow-brand-600/30 inline-flex items-center justify-center gap-2 transition-all">
                            <i class="bi bi-send-fill"></i>
                            <span><?= $isEn ? 'Send Message Now' : 'إرسال الرسالة الآن' ?></span>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Support / Help Callout Box -->
        <div class="bg-gradient-to-r from-slate-900 to-navy-900 rounded-2xl sm:rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden shadow-xl">
            <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-brand-500/10 rounded-full blur-2xl"></div>
            <div class="flex flex-col sm:flex-row items-center justify-between gap-6 relative z-10">
                <div class="text-center sm:text-start">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-brand-500/20 text-brand-300 border border-brand-500/30 mb-2 inline-block">
                        <?= $isEn ? 'Assistance & Inquiries' : 'الدعم والاستفسارات' ?>
                    </span>
                    <h3 class="text-lg sm:text-xl font-bold text-white mb-1">
                        <?= $isEn ? 'Have any questions about this document?' : 'هل لديك أي استفسار حول بنود هذه الصفحة؟' ?>
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-300">
                        <?= $isEn ? 'Our administrative committee is available to clarify any terms or regulations.' : 'لجنة الصندوق جاهزة للإجابة على جميع تساؤلاتكم ومقترحاتكم.' ?>
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <a href="<?= url('page/contact') ?>" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-brand-600 hover:bg-brand-500 shadow-md shadow-brand-600/30 inline-flex items-center gap-2 transition-all">
                        <i class="bi bi-chat-dots-fill"></i>
                        <span><?= $isEn ? 'Contact Us' : 'تواصل معنا' ?></span>
                    </a>
                    <a href="<?= url('/') ?>" class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-slate-300 hover:text-white bg-white/10 hover:bg-white/15 transition-colors">
                        <?= $isEn ? 'Back to Home' : 'الرئيسية' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Footer -->
<footer class="bg-slate-950 border-t border-slate-800 text-slate-400 py-12 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <!-- Brand & Mission -->
            <div class="md:col-span-2">
                <div class="flex items-center gap-3 mb-3">
                    <?php if ($brandLogo): ?>
                        <img src="<?= e($brandLogo) ?>" alt="<?= e($brandName) ?>" class="h-10 w-auto object-contain">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-emerald-400 flex items-center justify-center text-white text-xl shadow-md">
                            <i class="bi bi-safe2-fill"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <span class="text-lg font-black text-white block"><?= e($brandName) ?></span>
                        <span class="text-xs text-brand-400 font-semibold"><?= e($brandSlogan) ?></span>
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

            <!-- Dynamic Quick Links -->
            <div>
                <h4 class="text-xs font-extrabold text-white uppercase tracking-wider mb-4"><?= is_rtl() ? 'روابط سريعة' : 'Navigation' ?></h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="<?= url('/') ?>" class="hover:text-brand-400 transition-colors"><?= __('home') ?></a></li>
                    <?php foreach ($menuItems as $mItem): ?>
                        <?php 
                            $mTitle = $mItem['title'] ?? ($isEn ? (!empty($mItem['title_en']) ? $mItem['title_en'] : ($mItem['title_ar'] ?? '')) : ($mItem['title_ar'] ?? ''));
                            $mTarget = !empty($mItem['target']) ? $mItem['target'] : '_self';
                            $mUrl = !empty($mItem['url']) ? (str_starts_with($mItem['url'], 'http') ? $mItem['url'] : url($mItem['url'])) : url('/');
                        ?>
                        <li>
                            <a href="<?= e($mUrl) ?>" target="<?= e($mTarget) ?>" class="hover:text-brand-400 transition-colors"><?= e($mTitle) ?></a>
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
            &copy; <?= date('Y') ?> <?= e($brandName) ?>. <?= is_rtl() ? 'جميع الحقوق محفوظة.' : 'All rights reserved.' ?>
        </div>
    </div>
</footer>

<!-- Mobile App Bottom Navigation Bar -->
<div class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 px-2 py-1.5 shadow-2xl">
    <div class="grid grid-cols-5 gap-1 text-center">
        <a href="<?= url('/') ?>" class="flex flex-col items-center justify-center py-1 text-slate-400 hover:text-brand-300 transition-colors">
            <i class="bi bi-house-door text-lg"></i>
            <span class="text-[10px] font-medium mt-0.5"><?= __('home') ?></span>
        </a>
        <a href="<?= url('/#features') ?>" class="flex flex-col items-center justify-center py-1 text-slate-400 hover:text-brand-300 transition-colors">
            <i class="bi bi-grid text-lg"></i>
            <span class="text-[10px] font-medium mt-0.5"><?= __('features') ?></span>
        </a>
        <a href="<?= url('/#calculator') ?>" class="flex flex-col items-center justify-center py-1 text-slate-400 hover:text-brand-300 transition-colors">
            <i class="bi bi-calculator text-lg"></i>
            <span class="text-[10px] font-medium mt-0.5"><?= __('simulator') ?></span>
        </a>
        <a href="<?= url('page/about') ?>" class="flex flex-col items-center justify-center py-1 <?= (isset($page['slug']) && $page['slug'] === 'about') ? 'text-brand-400 font-bold' : 'text-slate-400 hover:text-brand-300' ?> transition-colors">
            <i class="bi bi-info-circle text-lg"></i>
            <span class="text-[10px] font-medium mt-0.5"><?= __('about_us') ?></span>
        </a>
        <?php if (!empty($isLoggedIn)): ?>
            <a href="<?= url('member/dashboard') ?>" class="flex flex-col items-center justify-center py-1 text-brand-400 font-bold">
                <i class="bi bi-person-circle text-lg"></i>
                <span class="text-[10px] font-bold mt-0.5"><?= __('account') ?></span>
            </a>
        <?php else: ?>
            <a href="<?= url('login') ?>" class="flex flex-col items-center justify-center py-1 text-slate-400 hover:text-brand-300 transition-colors">
                <i class="bi bi-box-arrow-in-left text-lg"></i>
                <span class="text-[10px] font-medium mt-0.5"><?= __('login') ?></span>
            </a>
        <?php endif; ?>
    </div>
</div>
