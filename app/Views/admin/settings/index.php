<?php
$currentMenu = [];
if (!empty($settings['navigation_menu_json'])) {
    $decoded = json_decode($settings['navigation_menu_json'], true);
    if (is_array($decoded)) {
        $currentMenu = $decoded;
    }
}
$siteLogo    = site_logo_url();
$siteFavicon = \App\Models\Setting::get('site_favicon');
$siteFaviconUrl = $siteFavicon
    ? (str_starts_with($siteFavicon, 'http') ? $siteFavicon : asset($siteFavicon))
    : null;
?>

<div x-data="settingsManager()" class="space-y-6">
    <!-- Top Action Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-brand-600 to-emerald-400 text-white flex items-center justify-center text-xl shadow-md shadow-brand-500/20">
                <i class="bi bi-sliders"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-900"><?= is_rtl() ? 'إعدادات النظام والمنصة' : 'System & Platform Settings' ?></h1>
                <p class="text-xs text-slate-500 mt-0.5"><?= is_rtl() ? 'تحكم شامل بالهوية، الشعار، التواصل، سكاشن الرئيسية، القوائم، السيو، واللغات' : 'Comprehensive management of branding, logo, contact, homepage sections, menus, SEO, and languages' ?></p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="<?= url('/') ?>" target="_blank" class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 inline-flex items-center gap-2 transition-colors">
                <i class="bi bi-box-arrow-up-right"></i>
                <span><?= is_rtl() ? 'معاينة الموقع العام' : 'Preview Public Site' ?></span>
            </a>
            <button type="button" @click="submitSettings()" class="px-5 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-600/30 inline-flex items-center gap-2 transition-all">
                <i class="bi bi-check2-circle text-base"></i>
                <span><?= is_rtl() ? 'حفظ التعديلات' : 'Save Settings' ?></span>
            </button>
        </div>
    </div>

    <!-- Main Settings Form -->
    <form x-ref="settingsForm" method="post" action="<?= url('admin/settings') ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>
        <input type="hidden" name="navigation_menu_json" x-ref="menuJsonInput">

        <!-- Navigation Tabs Bar -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="flex flex-wrap items-center gap-1.5 p-2 border-b border-slate-200/80 bg-slate-50/60 overflow-x-auto">
                <button type="button" @click="tab = 'identity'" :class="tab === 'identity' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm transition-all inline-flex items-center gap-2 shrink-0">
                    <i class="bi bi-palette-fill"></i>
                    <span><?= is_rtl() ? 'الهوية والشعار' : 'Branding & Logo' ?></span>
                </button>
                <button type="button" @click="tab = 'contact'" :class="tab === 'contact' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm transition-all inline-flex items-center gap-2 shrink-0">
                    <i class="bi bi-telephone-fill"></i>
                    <span><?= is_rtl() ? 'بيانات التواصل والشبكات' : 'Contact & Socials' ?></span>
                </button>
                <button type="button" @click="tab = 'sections'" :class="tab === 'sections' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm transition-all inline-flex items-center gap-2 shrink-0">
                    <i class="bi bi-eye-fill"></i>
                    <span><?= is_rtl() ? 'سكاشن الرئيسية' : 'Homepage Sections' ?></span>
                </button>
                <button type="button" @click="tab = 'menu'" :class="tab === 'menu' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm transition-all inline-flex items-center gap-2 shrink-0">
                    <i class="bi bi-list-nested"></i>
                    <span><?= is_rtl() ? 'إدارة القائمة والروابط' : 'Navigation Menu' ?></span>
                </button>
                <button type="button" @click="tab = 'seo'" :class="tab === 'seo' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm transition-all inline-flex items-center gap-2 shrink-0">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span><?= is_rtl() ? 'السيو والتحليلات' : 'SEO & Analytics' ?></span>
                </button>
                <button type="button" @click="tab = 'language'" :class="tab === 'language' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm transition-all inline-flex items-center gap-2 shrink-0">
                    <i class="bi bi-translate"></i>
                    <span><?= is_rtl() ? 'خيارات اللغة والنظام' : 'Language & Display' ?></span>
                </button>
                <button type="button" @click="tab = 'financial'" :class="tab === 'financial' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm transition-all inline-flex items-center gap-2 shrink-0">
                    <i class="bi bi-cash-stack"></i>
                    <span><?= is_rtl() ? 'الضوابط المالية' : 'Financial Policy' ?></span>
                </button>
                <button type="button" @click="tab = 'otp'" :class="tab === 'otp' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm transition-all inline-flex items-center gap-2 shrink-0">
                    <i class="bi bi-shield-lock-fill text-amber-500"></i>
                    <span><?= is_rtl() ? 'رمز التحقق وبوابات SMS' : 'OTP & SMS Gateway' ?></span>
                </button>
            </div>

            <!-- TAB 1: Branding & Identity -->
            <div x-show="tab === 'identity'" class="p-6 space-y-6">
                <!-- Logo Uploader Card -->
                <div class="bg-slate-50/70 border border-slate-200 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">شعار الصندوق (Logo)</h3>
                            <p class="text-xs text-slate-500 mt-0.5">ارفع صورة الشعار الرسمية لتظهر فورياً في الهيدر والفوتر والصفحات الداخلية</p>
                        </div>
                        <?php if ($siteLogo): ?>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                الشعار مفعل حالياً
                            </span>
                        <?php else: ?>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">
                                يستخدم الشعار الافتراضي
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-center">
                        <!-- Current Logo Preview -->
                        <div class="md:col-span-4 flex flex-col items-center justify-center p-4 bg-white rounded-xl border border-slate-200 min-h-[120px]">
                            <?php if ($siteLogo): ?>
                                <img src="<?= e($siteLogo) ?>" alt="Logo Preview" class="max-h-16 max-w-full object-contain mb-2">
                                <label class="flex items-center gap-1.5 text-xs text-rose-600 cursor-pointer hover:text-rose-700 mt-1">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded text-rose-600">
                                    <span>حذف الشعار والعودة للأيقونة الافتراضية</span>
                                </label>
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-brand-600 to-emerald-400 flex items-center justify-center text-white text-2xl shadow-sm mb-1">
                                    <i class="bi bi-<?= e($settings['logo_icon'] ?? 'safe2-fill') ?>"></i>
                                </div>
                                <span class="text-xs text-slate-400">الأيقونة الافتراضية المعتمدة</span>
                            <?php endif; ?>
                        </div>

                        <!-- Upload Input -->
                        <div class="md:col-span-8 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">اختيار ملف شعار جديد (PNG, SVG, JPG, WebP)</label>
                                <input type="file" name="logo_file" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-300 rounded-xl p-1 bg-white">
                                <p class="text-[11px] text-slate-400 mt-1">يُفضل استخدام خلفية شفافة PNG أو SVG بحجم أقصى 2MB.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">أو رابط الشعار عبر الإنترنت (URL)</label>
                                <input type="text" name="site_logo_url" placeholder="https://example.com/logo.png" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Favicon Uploader Card -->
                <div class="bg-slate-50/70 border border-slate-200 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">أيقونة التبويب (Favicon)</h3>
                            <p class="text-xs text-slate-500 mt-0.5">تظهر في تبويب المتصفح وعند إضافة الموقع للشاشة الرئيسية — يُفضل PNG أو ICO بحجم 32×32 أو 64×64</p>
                        </div>
                        <?php if ($siteFavicon): ?>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                Favicon مفعل حالياً
                            </span>
                        <?php else: ?>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">
                                لا يوجد Favicon
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-center">
                        <!-- Current Favicon Preview -->
                        <div class="md:col-span-4 flex flex-col items-center justify-center p-4 bg-white rounded-xl border border-slate-200 min-h-[100px]">
                            <?php if ($siteFaviconUrl): ?>
                                <img src="<?= e($siteFaviconUrl) ?>" alt="Favicon Preview" class="w-12 h-12 object-contain mb-2 rounded">
                                <label class="flex items-center gap-1.5 text-xs text-rose-600 cursor-pointer hover:text-rose-700 mt-1">
                                    <input type="checkbox" name="remove_favicon" value="1" class="rounded text-rose-600">
                                    <span>حذف الـ Favicon الحالي</span>
                                </label>
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-xl bg-slate-100 border border-dashed border-slate-300 flex items-center justify-center text-slate-400 text-2xl mb-1">
                                    <i class="bi bi-image"></i>
                                </div>
                                <span class="text-xs text-slate-400">لا توجد أيقونة مرفوعة</span>
                            <?php endif; ?>
                        </div>

                        <!-- Upload Input -->
                        <div class="md:col-span-8 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">رفع ملف Favicon جديد (ICO, PNG, SVG, WebP)</label>
                                <input type="file" name="favicon_file" accept=".ico,image/x-icon,image/png,image/svg+xml,image/webp" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 border border-slate-300 rounded-xl p-1 bg-white">
                                <p class="text-[11px] text-slate-400 mt-1">الحجم الأقصى 512KB — يُنصح باستخدام PNG بحجم 32×32 أو 64×64 بكسل.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">أو رابط Favicon عبر الإنترنت (URL)</label>
                                <input type="text" name="site_favicon_url" placeholder="https://example.com/favicon.ico" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Names & Slogans in AR & EN -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">اسم الصندوق / الموقع (عربي) <span class="text-rose-500">*</span></label>
                        <input type="text" name="site_name" value="<?= e($settings['site_name'] ?? 'صندوق عائلي') ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-bold">
                    </div>
                    <div dir="ltr">
                        <label class="block text-xs font-bold text-slate-800 mb-1">Site / Fund Name (English)</label>
                        <input type="text" name="site_name_en" value="<?= e($settings['site_name_en'] ?? 'Family Solidarity Fund') ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">الشعار اللفظي / الوصف المختصر (عربي)</label>
                        <input type="text" name="site_slogan" value="<?= e($settings['site_slogan'] ?? 'المنظومة المالية والتكافلية للأسرة') ?>" class="w-full px-4 py-2 rounded-xl border border-slate-300 text-xs">
                    </div>
                    <div dir="ltr">
                        <label class="block text-xs font-bold text-slate-800 mb-1">Slogan / Subtitle (English)</label>
                        <input type="text" name="site_slogan_en" value="<?= e($settings['site_slogan_en'] ?? 'Family Financial & Solidarity Ecosystem') ?>" class="w-full px-4 py-2 rounded-xl border border-slate-300 text-xs">
                    </div>
                </div>
            </div>

            <!-- TAB 2: Contact & Social Info -->
            <div x-show="tab === 'contact'" class="p-6 space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">بيانات التواصل الرسمية</h3>
                    <p class="text-xs text-slate-500">تظهر هذه المعلومات في فوتر الموقع، صفحة اتصل بنا، وإشعارات المشتركين.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">رقم الهاتف الرسمي</label>
                        <div class="relative">
                            <i class="bi bi-telephone absolute inset-y-0 <?= is_rtl() ? 'right-3' : 'left-3' ?> flex items-center text-slate-400"></i>
                            <input type="text" name="official_phone" value="<?= e($settings['official_phone'] ?? '') ?>" class="w-full text-xs py-2.5 <?= is_rtl() ? 'pr-9 pl-3' : 'pl-9 pr-3' ?> rounded-xl border border-slate-300 font-numeric">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">البريد الإلكتروني الرسمي</label>
                        <div class="relative">
                            <i class="bi bi-envelope absolute inset-y-0 <?= is_rtl() ? 'right-3' : 'left-3' ?> flex items-center text-slate-400"></i>
                            <input type="email" name="official_email" value="<?= e($settings['official_email'] ?? '') ?>" class="w-full text-xs py-2.5 <?= is_rtl() ? 'pr-9 pl-3' : 'pl-9 pr-3' ?> rounded-xl border border-slate-300">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">رقم الواتساب المباشر</label>
                        <div class="relative">
                            <i class="bi bi-whatsapp absolute inset-y-0 <?= is_rtl() ? 'right-3' : 'left-3' ?> flex items-center text-emerald-500"></i>
                            <input type="text" name="official_whatsapp" value="<?= e($settings['official_whatsapp'] ?? '') ?>" placeholder="+9665xxxxxxxx" class="w-full text-xs py-2.5 <?= is_rtl() ? 'pr-9 pl-3' : 'pl-9 pr-3' ?> rounded-xl border border-slate-300 font-numeric">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">العنوان والمقر (عربي)</label>
                        <input type="text" name="official_address" value="<?= e($settings['official_address'] ?? 'المملكة العربية السعودية - الرياض') ?>" class="w-full text-xs py-2.5 px-3 rounded-xl border border-slate-300">
                    </div>
                    <div dir="ltr">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Address (English)</label>
                        <input type="text" name="official_address_en" value="<?= e($settings['official_address_en'] ?? 'Riyadh, Kingdom of Saudi Arabia') ?>" class="w-full text-xs py-2.5 px-3 rounded-xl border border-slate-300">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-slate-800 mb-3">حسابات التواصل الاجتماعي</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">حساب تويتر / منصة X</label>
                            <input type="text" name="social_twitter" value="<?= e($settings['social_twitter'] ?? '') ?>" placeholder="https://x.com/username" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">حساب انستغرام</label>
                            <input type="text" name="social_instagram" value="<?= e($settings['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/username" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">قناة تيليجرام</label>
                            <input type="text" name="social_telegram" value="<?= e($settings['social_telegram'] ?? '') ?>" placeholder="https://t.me/channel" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300">
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Homepage Sections Visibility -->
            <div x-show="tab === 'sections'" class="p-6 space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">التحكم في ظهور وإخفاء سكاشن الصفحة الرئيسية</h3>
                    <p class="text-xs text-slate-500">يمكنك تفعيل أو إخفاء أي سكشن من الصفحة الرئيسية بضغطة زر دون المساس بالبيانات.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Section 1: Hero -->
                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                                <i class="bi bi-image"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">سكشن الهيرو والبنر الترحيبي (Hero)</h4>
                                <p class="text-[11px] text-slate-500">العنوان الرئيسي وأزرار الانضمام والدخول</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="section_hero_enabled" value="1" <?= ($settings['section_hero_enabled'] ?? '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <!-- Section 2: Stats -->
                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                                <i class="bi bi-speedometer"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">شريط الإحصائيات والأرقام (Stats)</h4>
                                <p class="text-[11px] text-slate-500">إجمالي الأسهم، رأس المال، والأعضاء</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="section_stats_enabled" value="1" <?= ($settings['section_stats_enabled'] ?? '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <!-- Section 3: Features -->
                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                                <i class="bi bi-stars"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">سكشن مزايا وركائز الصندوق (Features)</h4>
                                <p class="text-[11px] text-slate-500">بطاقات القروض الحسنة والادخار والحوكمة</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="section_features_enabled" value="1" <?= ($settings['section_features_enabled'] ?? '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <!-- Section 4: Calculator -->
                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg">
                                <i class="bi bi-calculator"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">حاسبة القروض والاشتراكات (Simulator)</h4>
                                <p class="text-[11px] text-slate-500">حاسبة الأسهم التفاعلية وتقدير الأقساط</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="section_calculator_enabled" value="1" <?= ($settings['section_calculator_enabled'] ?? '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <!-- Section 5: Charter -->
                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                                <i class="bi bi-file-earmark-ruled"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">سكشن ميثاق ولائحة الصندوق (Charter)</h4>
                                <p class="text-[11px] text-slate-500">ملخص مبادئ العدالة والتكافل الأسري</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="section_charter_enabled" value="1" <?= ($settings['section_charter_enabled'] ?? '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <!-- Section 6: Hadith -->
                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg">
                                <i class="bi bi-quote"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">كرت حديث صلة الرحم والبركة (Hadith)</h4>
                                <p class="text-[11px] text-slate-500">الحديث النبوي الشريف في فضل صلة الرحم</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="section_hadith_enabled" value="1" <?= ($settings['section_hadith_enabled'] ?? '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <!-- Section 7: CTA -->
                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition-all md:col-span-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                                <i class="bi bi-megaphone"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">بانر الدعوة للانضمام والتواصل (CTA Box)</h4>
                                <p class="text-[11px] text-slate-500">دعوة المشتركين الجدد وزر التواصل السريع في أسفل الصفحة</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="section_cta_enabled" value="1" <?= ($settings['section_cta_enabled'] ?? '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- TAB 4: Dynamic Navigation Menu Builder -->
            <div x-show="tab === 'menu'" class="p-6 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">إدارة القائمة الرئيسية وروابط الموقع (Menu Builder)</h3>
                        <p class="text-xs text-slate-500 mt-0.5">تحكم في الروابط المعروضة في الهيدر، أضف صفحات داخلية أو روابط خارجية مع ترجمتها بالعربية والإنجليزية</p>
                    </div>
                    <button type="button" @click="addMenuItem()" class="px-4 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 inline-flex items-center gap-1.5 transition-colors self-start sm:self-auto">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>إضافة رابط جديد للقائمة</span>
                    </button>
                </div>

                <!-- Fast Page Adder helper -->
                <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl flex flex-wrap items-center gap-3">
                    <span class="text-xs font-bold text-slate-700">إضافة صفحة جاهزة:</span>
                    <select x-ref="pageSelect" class="text-xs py-1.5 px-3 rounded-lg border border-slate-300 bg-white">
                        <option value="">اختر صفحة من النظام...</option>
                        <?php foreach ($pages as $p): ?>
                            <option value="<?= e($p['slug']) ?>" data-title-ar="<?= e($p['title']) ?>" data-title-en="<?= e($p['title_en'] ?? $p['title']) ?>">
                                <?= e($p['title']) ?> (/page/<?= e($p['slug']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" @click="addPageToMenu($refs.pageSelect)" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-slate-700 hover:bg-slate-100 border border-slate-300">
                        إدراج في القائمة
                    </button>
                </div>

                <!-- Menu Items List -->
                <div class="space-y-3">
                    <template x-for="(item, idx) in menuItems" :key="idx">
                        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm transition-all hover:border-slate-300 space-y-3">
                            <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center" x-text="idx + 1"></span>
                                    <span class="text-xs font-bold text-slate-800" x-text="item.title_ar || ('عنصر #' + (idx + 1))"></span>
                                    <span class="text-[10px] text-slate-400 font-mono" x-text="'(' + item.url + ')'"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer">
                                        <input type="checkbox" x-model="item.enabled" class="rounded text-emerald-600">
                                        <span x-text="item.enabled ? 'مفعل' : 'معطل'"></span>
                                    </label>
                                    <button type="button" @click="moveMenuUp(idx)" :disabled="idx === 0" class="p-1 rounded text-slate-400 hover:text-slate-700 disabled:opacity-30">
                                        <i class="bi bi-arrow-up"></i>
                                    </button>
                                    <button type="button" @click="moveMenuDown(idx)" :disabled="idx === menuItems.length - 1" class="p-1 rounded text-slate-400 hover:text-slate-700 disabled:opacity-30">
                                        <i class="bi bi-arrow-down"></i>
                                    </button>
                                    <button type="button" @click="removeMenuItem(idx)" class="p-1 rounded text-rose-500 hover:text-rose-700 ml-1">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">اختيار من الصفحات (Select Page)</label>
                                    <select @change="onPageSelected($event, item)" class="w-full text-xs py-2 px-2.5 rounded-lg border border-emerald-300 bg-emerald-50/40 text-slate-800 font-medium">
                                        <option value="">-- اختيار صفحة من النظام --</option>
                                        <option value="/" data-ar="الرئيسية" data-en="Home" :selected="item.url === '/'">🏠 الرئيسية (Home)</option>
                                        <option value="/#features" data-ar="المميزات" data-en="Features" :selected="item.url === '/#features'">⭐ المميزات (Features)</option>
                                        <option value="/#calculator" data-ar="حاسبة القروض" data-en="Calculator" :selected="item.url === '/#calculator'">🧮 حاسبة القروض (Calculator)</option>
                                        <option value="/#charter" data-ar="ميثاق الصندوق" data-en="Charter" :selected="item.url === '/#charter'">📜 ميثاق الصندوق (Charter)</option>
                                        <?php if (!empty($pages)): ?>
                                            <?php foreach ($pages as $p): ?>
                                                <option value="/page/<?= e($p['slug']) ?>" data-ar="<?= e($p['title']) ?>" data-en="<?= e($p['title_en'] ?? $p['title']) ?>" :selected="item.url === '/page/<?= e($p['slug']) ?>'">
                                                    📄 <?= e($p['title']) ?> (/page/<?= e($p['slug']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <option value="custom">🔗 رابط مخصص خارجي...</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">العنوان بالعربية <span class="text-rose-500">*</span></label>
                                    <input type="text" x-model="item.title_ar" placeholder="مثال: الرئيسية" class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300">
                                </div>
                                <div class="sm:col-span-3" dir="ltr">
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Title in English</label>
                                    <input type="text" x-model="item.title_en" placeholder="e.g. Home" class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">الرابط / المسار (URL)</label>
                                    <input type="text" x-model="item.url" placeholder="/page/about أو https://..." class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 font-mono" dir="ltr">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- TAB 5: SEO & Webmaster Suite -->
            <div x-show="tab === 'seo'" class="p-6 space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">التهيئة لمحركات البحث والسيو (SEO Suite)</h3>
                    <p class="text-xs text-slate-500">إعداد الكلمات المفتاحية والأوصاف ومشاركات وسائل التواصل (OpenGraph) وأكواد التتبع.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">عنوان الميتا الافتراضي (SEO Meta Title - AR)</label>
                        <input type="text" name="seo_meta_title" value="<?= e($settings['seo_meta_title'] ?? '') ?>" class="w-full text-xs py-2.5 px-3 rounded-xl border border-slate-300 font-bold">
                    </div>
                    <div dir="ltr">
                        <label class="block text-xs font-bold text-slate-800 mb-1">SEO Meta Title (English)</label>
                        <input type="text" name="seo_meta_title_en" value="<?= e($settings['seo_meta_title_en'] ?? '') ?>" class="w-full text-xs py-2.5 px-3 rounded-xl border border-slate-300 font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">وصف الميتا لمحركات البحث (Meta Description - AR)</label>
                        <textarea name="seo_meta_description" rows="3" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300"><?= e($settings['seo_meta_description'] ?? '') ?></textarea>
                    </div>
                    <div dir="ltr">
                        <label class="block text-xs font-bold text-slate-800 mb-1">Meta Description (English)</label>
                        <textarea name="seo_meta_description_en" rows="3" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300"><?= e($settings['seo_meta_description_en'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">الكلمات المفتاحية (Keywords - AR)</label>
                        <input type="text" name="seo_meta_keywords" value="<?= e($settings['seo_meta_keywords'] ?? '') ?>" placeholder="كلمة 1, كلمة 2..." class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300">
                    </div>
                    <div dir="ltr">
                        <label class="block text-xs font-bold text-slate-800 mb-1">Keywords (English)</label>
                        <input type="text" name="seo_meta_keywords_en" value="<?= e($settings['seo_meta_keywords_en'] ?? '') ?>" placeholder="keyword1, keyword2..." class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-3 border-t border-slate-100">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">رابط صورة المشاركة الاجتماعية (OpenGraph Image URL)</label>
                        <input type="text" name="seo_og_image" value="<?= e($settings['seo_og_image'] ?? '') ?>" placeholder="https://example.com/og-banner.jpg" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300" dir="ltr">
                        <p class="text-[11px] text-slate-400 mt-1">تظهر عند مشاركة رابط الموقع في واتساب وتويتر وفيسبوك (مقاس 1200x630px).</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">معرف تحليلات جوجل (Google Analytics GA4 ID)</label>
                        <input type="text" name="seo_google_analytics" value="<?= e($settings['seo_google_analytics'] ?? '') ?>" placeholder="G-XXXXXXXXXX" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 font-mono" dir="ltr">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">أكواد إضافية في رأس الصفحة (&lt;head&gt; Scripts)</label>
                        <textarea name="seo_custom_header_scripts" rows="3" placeholder="&lt;script&gt;...&lt;/script&gt;" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 font-mono" dir="ltr"><?= e($settings['seo_custom_header_scripts'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- TAB 6: Language & System Controls -->
            <div x-show="tab === 'language'" class="p-6 space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">إعدادات اللغات وعزل لوحة التحكم</h3>
                    <p class="text-xs text-slate-500">تحكم كامل في وضع اللغات بالموقع وإمكانية تشغيل لغة واحدة أو اللغتين معاً.</p>
                </div>

                <!-- Independent Language Alert Box -->
                <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-200 text-indigo-900 text-xs leading-relaxed flex items-start gap-3">
                    <i class="bi bi-shield-check text-indigo-600 text-lg shrink-0 mt-0.5"></i>
                    <div>
                        <span class="font-bold block mb-1">استقلالية لغة لوحة التحكم عن الموقع العام:</span>
                        تم فصل لغة لوحة الإدارة تماماً عن لغة الموقع العام. يمكنك تصفح الداشبورد بالإنجليزية بينما يظل الموقع العام بالعربية، أو العكس، بدون أي تأثير متبادل.
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
                    <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-3">
                        <label class="block text-xs font-bold text-slate-800">وضع لغات الموقع العام (Language Mode)</label>
                        <div class="space-y-2 text-xs">
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                <input type="radio" name="site_language_mode" value="multi" <?= ($settings['site_language_mode'] ?? 'multi') === 'multi' ? 'checked' : '' ?> class="text-emerald-600">
                                <div>
                                    <span class="font-bold text-slate-800 block">ثنائي اللغة (Multi-language) - مستحسن</span>
                                    <span class="text-slate-400 text-[11px]">إتاحة العربية والإنجليزية للزائر مع ظهور زر التبديل السريع.</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                <input type="radio" name="site_language_mode" value="single" <?= ($settings['site_language_mode'] ?? 'multi') === 'single' ? 'checked' : '' ?> class="text-emerald-600">
                                <div>
                                    <span class="font-bold text-slate-800 block">لغة واحدة فقط (Single Language Mode)</span>
                                    <span class="text-slate-400 text-[11px]">قفل الموقع على لغة واحدة فقط وإخفاء زر التبديل من الزوار.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-3">
                        <label class="block text-xs font-bold text-slate-800">اللغة الافتراضية للموقع (Default Language)</label>
                        <select name="site_default_language" class="w-full text-xs py-2.5 px-3 rounded-xl border border-slate-300 bg-white">
                            <option value="ar" <?= ($settings['site_default_language'] ?? 'ar') === 'ar' ? 'selected' : '' ?>>🇸🇦 اللغة العربية (Arabic) - افتراضي</option>
                            <option value="en" <?= ($settings['site_default_language'] ?? 'ar') === 'en' ? 'selected' : '' ?>>🇬🇧 English (الإنجليزية)</option>
                        </select>
                        <p class="text-[11px] text-slate-400">في حال تفعيل خيار "لغة واحدة فقط"، سيتم اعتماد هذه اللغة تلقائياً لجميع الزوار.</p>
                    </div>
                </div>
            </div>

            <!-- TAB 7: Financial Rules -->
            <div x-show="tab === 'financial'" class="p-6 space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">الضوابط والسياسات المالية للصندوق</h3>
                    <p class="text-xs text-slate-500">المحددات الحسابية لأسهم الصندوق وسقف القروض والمواعيد الشهرية.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5"><?= is_rtl() ? 'قيمة السهم الواحد الافتراضية' : 'Default Share Value' ?></label>
                        <div class="flex rounded-xl overflow-hidden border border-slate-300 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 bg-white shadow-xs transition-all">
                            <input type="number" step="0.01" min="1" name="share_value" value="<?= e($settings['share_value'] ?? '1000') ?>" required class="flex-1 min-w-0 text-xs py-2.5 px-3.5 border-0 focus:outline-none font-numeric font-bold text-slate-900 bg-transparent">
                            <span class="inline-flex items-center px-3.5 bg-slate-100 text-slate-600 text-xs font-bold border-s border-slate-200 shrink-0 select-none">
                                <?= __('currency') ?>
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5"><?= is_rtl() ? 'مبلغ التأسيس الإضافي لكل سهم' : 'Founding Fee Per Share' ?></label>
                        <div class="flex rounded-xl overflow-hidden border border-slate-300 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 bg-white shadow-xs transition-all">
                            <input type="number" step="0.01" min="0" name="founding_fee_per_share" value="<?= e($settings['founding_fee_per_share'] ?? '500') ?>" required class="flex-1 min-w-0 text-xs py-2.5 px-3.5 border-0 focus:outline-none font-numeric font-bold text-slate-900 bg-transparent">
                            <span class="inline-flex items-center px-3.5 bg-slate-100 text-slate-600 text-xs font-bold border-s border-slate-200 shrink-0 select-none">
                                <?= __('currency') ?>
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5"><?= is_rtl() ? 'نسبة المصاريف الإدارية للقروض %' : 'Loan Admin Fee %' ?></label>
                        <div class="flex rounded-xl overflow-hidden border border-slate-300 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 bg-white shadow-xs transition-all">
                            <input type="number" step="0.01" min="0" max="100" name="loan_admin_fee_percent" value="<?= e($settings['loan_admin_fee_percent'] ?? '2') ?>" class="flex-1 min-w-0 text-xs py-2.5 px-3.5 border-0 focus:outline-none font-numeric font-bold text-slate-900 bg-transparent">
                            <span class="inline-flex items-center px-3.5 bg-slate-100 text-slate-600 text-xs font-bold border-s border-slate-200 shrink-0 select-none">
                                %
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5"><?= is_rtl() ? 'مضاعف سقف التمويل الأقصى' : 'Max Loan Ceiling Multiplier' ?></label>
                        <div class="flex rounded-xl overflow-hidden border border-slate-300 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 bg-white shadow-xs transition-all">
                            <input type="number" step="0.5" min="1" max="50" name="max_loan_ratio" value="<?= e($settings['max_loan_ratio'] ?? '10') ?>" class="flex-1 min-w-0 text-xs py-2.5 px-3.5 border-0 focus:outline-none font-numeric font-bold text-slate-900 bg-transparent">
                            <span class="inline-flex items-center px-3 bg-slate-100 text-slate-600 text-xs font-bold border-s border-slate-200 shrink-0 select-none">
                                <?= is_rtl() ? 'ضعف الأسهم' : 'x Shares' ?>
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5"><?= is_rtl() ? 'يوم استحقاق الاشتراك الشهري' : 'Monthly Subscription Due Day' ?></label>
                        <div class="flex rounded-xl overflow-hidden border border-slate-300 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 bg-white shadow-xs transition-all">
                            <input type="number" min="1" max="28" name="subscription_due_day" value="<?= e($settings['subscription_due_day'] ?? '10') ?>" required class="flex-1 min-w-0 text-xs py-2.5 px-3.5 border-0 focus:outline-none font-numeric font-bold text-slate-900 bg-transparent">
                            <span class="inline-flex items-center px-3 bg-slate-100 text-slate-600 text-xs font-bold border-s border-slate-200 shrink-0 select-none">
                                <?= is_rtl() ? 'من كل شهر' : 'of Month' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 8: OTP & SMS Gateway Integration -->
            <div x-show="tab === 'otp'" class="p-6 space-y-6">
                <!-- Mode Selector Cards -->
                <div class="space-y-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900"><?= is_rtl() ? 'وضع التحقق برمز الجوال (OTP)' : 'OTP Operation Mode' ?></h3>
                        <p class="text-xs text-slate-500 mt-0.5"><?= is_rtl() ? 'اختر آلية عمل رمز التحقق عند تسجيل الحسابات واستعادة كلمة المرور' : 'Choose how verification codes operate across registrations and password resets' ?></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                        <!-- Demo Mode -->
                        <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all bg-white"
                               :class="otpMode === 'demo' ? 'border-amber-500 bg-amber-50/20 shadow-sm ring-2 ring-amber-500/20' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="otp_mode" value="demo" x-model="otpMode" class="sr-only">
                            <div class="flex items-center justify-between mb-2">
                                <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg">
                                    <i class="bi bi-laptop"></i>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-amber-100 text-amber-800">موصى به للتجربة</span>
                            </div>
                            <span class="text-sm font-bold text-slate-900 mb-1">وضع ديمو تجريبي (محاكاة)</span>
                            <p class="text-xs text-slate-500 leading-relaxed">يظهر الرمز تلقائياً للمستخدم على الشاشة للتجربة الفورية ونقرة تعبئة واحدة دون إرسال رسائل SMS فعلية وبلا تكلفة.</p>
                        </label>

                        <!-- Live SMS Gateway -->
                        <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all bg-white"
                               :class="otpMode === 'live' ? 'border-emerald-600 bg-emerald-50/20 shadow-sm ring-2 ring-emerald-600/20' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="otp_mode" value="live" x-model="otpMode" class="sr-only">
                            <div class="flex items-center justify-between mb-2">
                                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg">
                                    <i class="bi bi-broadcast"></i>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-100 text-emerald-800">إرسال فعلي</span>
                            </div>
                            <span class="text-sm font-bold text-slate-900 mb-1">بوابة رسائل SMS فعلية</span>
                            <p class="text-xs text-slate-500 leading-relaxed">إرسال رسائل SMS حقيقية إلى جوال المشترك مباشرة عبر المزود المعتمد (مثل تقنيات، يوني فونيك، فور جوالي).</p>
                        </label>

                        <!-- Disabled Mode -->
                        <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition-all bg-white"
                               :class="otpMode === 'disabled' ? 'border-rose-500 bg-rose-50/20 shadow-sm ring-2 ring-rose-500/20' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="otp_mode" value="disabled" x-model="otpMode" class="sr-only">
                            <div class="flex items-center justify-between mb-2">
                                <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-lg">
                                    <i class="bi bi-x-circle"></i>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-rose-100 text-rose-800">تعطيل التحقق</span>
                            </div>
                            <span class="text-sm font-bold text-slate-900 mb-1">تعطيل رمز التحقق</span>
                            <p class="text-xs text-slate-500 leading-relaxed">تخطي خطوة التحقق برمز الجوال وتفعيل حساب المشترك مباشرة بعد إدخال النموذج.</p>
                        </label>
                    </div>
                </div>

                <!-- Parameters & Timers Grid -->
                <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-5 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i class="bi bi-stopwatch text-emerald-600"></i>
                        <span>توقيتات وضوابط الرمز</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Resend Cooldown (50 Seconds) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                <span>مهلة إعادة الإرسال (بالثواني)</span>
                                <span class="ms-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold">المطلوبة: 50 ثانية</span>
                            </label>
                            <div class="flex rounded-xl overflow-hidden border border-slate-300 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 bg-white shadow-xs">
                                <input type="number" min="10" max="300" name="otp_resend_seconds" value="<?= e($settings['otp_resend_seconds'] ?? '50') ?>" required class="flex-1 min-w-0 text-xs py-2.5 px-3.5 border-0 focus:outline-none font-numeric font-bold text-slate-900 bg-transparent">
                                <span class="inline-flex items-center px-3.5 bg-slate-100 text-slate-600 text-xs font-bold border-s border-slate-200 shrink-0 select-none">ثانية</span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">المدة التي يجب على المستخدم انتظارها قبل النقر على إعادة الإرسال.</p>
                        </div>

                        <!-- OTP Length (4 vs 6 digits) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">عدد خانات الرمز (OTP Length)</label>
                            <select name="otp_length" class="w-full rounded-xl border border-slate-300 text-xs py-2.5 px-3.5 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-white shadow-xs font-bold text-slate-900">
                                <option value="4" <?= ($settings['otp_length'] ?? '4') == '4' ? 'selected' : '' ?>>4 أرقام (الافتراضي - موصى به للسهولة)</option>
                                <option value="6" <?= ($settings['otp_length'] ?? '4') == '6' ? 'selected' : '' ?>>6 أرقام (حماية متقدمة)</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">تتغير مربعات الإدخال في واجهة العميل تلقائياً حسب هذا العدد.</p>
                        </div>

                        <!-- Expiry Minutes -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">مدة صلاحية الرمز (بالدقائق)</label>
                            <div class="flex rounded-xl overflow-hidden border border-slate-300 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 bg-white shadow-xs">
                                <input type="number" min="1" max="60" name="otp_expiry_minutes" value="<?= e($settings['otp_expiry_minutes'] ?? '10') ?>" required class="flex-1 min-w-0 text-xs py-2.5 px-3.5 border-0 focus:outline-none font-numeric font-bold text-slate-900 bg-transparent">
                                <span class="inline-flex items-center px-3.5 bg-slate-100 text-slate-600 text-xs font-bold border-s border-slate-200 shrink-0 select-none">دقائق</span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">تنتهي صلاحية الرمز بعدها ويلزم طلب رمز جديد.</p>
                        </div>
                    </div>
                </div>

                <!-- SMS Provider Gateway Configuration -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <i class="bi bi-hdd-network text-emerald-600"></i>
                                <span>إعدادات بوابة الرسائل القصيرة (SMS Gateway)</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">اختر مزود خدمة الرسائل وأدخل بيانات الاعتماد (تُستخدم عند اختيار وضع الإرسال الفعلي)</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Provider Select -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">مزود خدمة الرسائل (SMS Gateway)</label>
                            <select name="sms_provider" x-model="smsProvider" class="w-full rounded-xl border border-slate-300 text-xs py-2.5 px-3.5 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-white shadow-xs font-bold text-slate-900">
                                <option value="taqnyat">تقنيات (Taqnyat.sa - موصى به في السعودية)</option>
                                <option value="unifonic">يوني فونيك (Unifonic Cloud)</option>
                                <option value="4jawaly">فور جوالي (4jawaly.com)</option>
                                <option value="msegat">مسجات (Msegat.com)</option>
                                <option value="twilio">تويليو (Twilio)</option>
                                <option value="custom">رابط مخصص (Custom Webhook / HTTP API)</option>
                            </select>
                        </div>

                        <!-- Sender Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">اسم المرسل المعتمد (Sender Name / ID)</label>
                            <input type="text" name="sms_sender_name" value="<?= e($settings['sms_sender_name'] ?? site_name()) ?>" placeholder="مثال: SANDOUK" class="w-full rounded-xl border border-slate-300 text-xs py-2.5 px-3.5 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-white shadow-xs font-bold text-slate-900">
                            <p class="text-[11px] text-slate-400 mt-1">الاسم المعتمد لحسابك في هيئة الاتصالات ومزود الخدمة.</p>
                        </div>

                        <!-- API Key / Token -->
                        <div class="md:col-span-2" x-show="smsProvider !== 'unifonic'">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">مفتاح الواجهة البرمجية (API Key / Bearer Token)</label>
                            <input type="password" name="sms_api_key" value="<?= e($settings['sms_api_key'] ?? '') ?>" placeholder="أدخل مفتاح الـ API الخاص بحسابك في المزود" class="w-full rounded-xl border border-slate-300 text-xs py-2.5 px-3.5 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-white shadow-xs font-mono text-slate-900">
                        </div>

                        <!-- App SID (Unifonic / Twilio) -->
                        <div class="md:col-span-2" x-show="smsProvider === 'unifonic' || smsProvider === 'twilio'">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">معرّف التطبيق أو الحساب (AppSid / Account SID)</label>
                            <input type="text" name="sms_app_sid" value="<?= e($settings['sms_app_sid'] ?? '') ?>" placeholder="أدخل AppSid الخاص بـ Unifonic أو Account SID الخاص بـ Twilio" class="w-full rounded-xl border border-slate-300 text-xs py-2.5 px-3.5 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-white shadow-xs font-mono text-slate-900">
                        </div>

                        <!-- Username & Password (4jawaly / Msegat) -->
                        <div x-show="smsProvider === '4jawaly' || smsProvider === 'msegat'">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">اسم المستخدم في البوابة</label>
                            <input type="text" name="sms_username" value="<?= e($settings['sms_username'] ?? '') ?>" placeholder="اسم الحساب" class="w-full rounded-xl border border-slate-300 text-xs py-2.5 px-3.5 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-white shadow-xs font-bold text-slate-900">
                        </div>

                        <div x-show="smsProvider === '4jawaly'">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">كلمة مرور البوابة</label>
                            <input type="password" name="sms_password" value="<?= e($settings['sms_password'] ?? '') ?>" placeholder="كلمة المرور" class="w-full rounded-xl border border-slate-300 text-xs py-2.5 px-3.5 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-white shadow-xs text-slate-900">
                        </div>

                        <!-- Custom URL -->
                        <div class="md:col-span-2" x-show="smsProvider === 'custom'">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">رابط الـ API المخصص (Custom Webhook Endpoint)</label>
                            <input type="text" name="sms_custom_url" value="<?= e($settings['sms_custom_url'] ?? '') ?>" placeholder="https://api.example.com/send?to={mobile}&msg={message}&sender={sender}" class="w-full rounded-xl border border-slate-300 text-xs py-2.5 px-3.5 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-white shadow-xs font-mono text-slate-900">
                            <p class="text-[11px] text-slate-400 mt-1">المتغيرات المدعومة: <code class="text-emerald-700">{mobile}</code>, <code class="text-emerald-700">{message}</code>, <code class="text-emerald-700">{sender}</code>, <code class="text-emerald-700">{key}</code></p>
                        </div>
                    </div>
                </div>

                <!-- Live Test SMS Sender Tool -->
                <div class="bg-gradient-to-tr from-slate-900 to-slate-800 text-white rounded-2xl p-5 space-y-3 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-base">
                                <i class="bi bi-send-check-fill"></i>
                            </div>
                            <div>
                                <h4 class="text-xs sm:text-sm font-bold">أداة فحص وتجربة إرسال رسالة SMS</h4>
                                <p class="text-[11px] text-slate-400">أدخل رقم جوالك لتجربة فحص ربط البوابة والتأكد من وصول الرسالة</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-2 pt-1">
                        <div class="w-full sm:w-80">
                            <input type="text" x-model="testMobile" placeholder="مثال: 0501234567" dir="ltr" class="w-full rounded-xl border border-slate-700 bg-slate-950/60 text-white text-xs py-2.5 px-3.5 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 font-numeric placeholder:text-slate-500">
                        </div>
                        <button type="button" @click="sendTestSms()" :disabled="isTestingSms" class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 transition-all inline-flex items-center justify-center gap-2 shrink-0 shadow-sm">
                            <i class="bi bi-send" x-show="!isTestingSms"></i>
                            <span class="spinner-border spinner-border-sm" x-show="isTestingSms"></span>
                            <span x-text="isTestingSms ? 'جارٍ الإرسال والتجربة...' : 'إرسال رسالة فحص تجريبية'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Bottom Actions -->
        <div class="flex items-center justify-between p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs text-slate-400">تأكد من مراجعة التعديلات قبل الحفظ النهائي</span>
            <button type="button" @click="submitSettings()" class="px-7 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-600/30 inline-flex items-center gap-2 transition-all">
                <i class="bi bi-check2-circle text-lg"></i>
                <span>حفظ جميع الإعدادات والتغييرات</span>
            </button>
        </div>
    </form>
</div>

<script>
function settingsManager() {
    return {
        tab: 'identity',
        otpMode: '<?= e($settings['otp_mode'] ?? 'demo') ?>',
        smsProvider: '<?= e($settings['sms_provider'] ?? 'taqnyat') ?>',
        testMobile: '',
        isTestingSms: false,
        menuItems: <?= json_encode($currentMenu, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
        addMenuItem() {
            this.menuItems.push({
                id: 'custom_' + Date.now(),
                title_ar: 'رابط جديد',
                title_en: 'New Link',
                url: '/',
                target: '_self',
                enabled: true
            });
            this.tab = 'menu';
        },
        onPageSelected(event, item) {
            const sel = event.target;
            const val = sel.value;
            if (!val || val === 'custom') return;
            const opt = sel.options[sel.selectedIndex];
            const ar = opt.getAttribute('data-ar');
            const en = opt.getAttribute('data-en');
            item.url = val;
            if (ar) item.title_ar = ar;
            if (en) item.title_en = en;
        },
        addPageToMenu(selectEl) {
            const val = selectEl.value;
            if (!val) return;
            const opt = selectEl.options[selectEl.selectedIndex];
            const titleAr = opt.getAttribute('data-title-ar') || val;
            const titleEn = opt.getAttribute('data-title-en') || val;
            this.menuItems.push({
                id: 'page_' + val,
                title_ar: titleAr,
                title_en: titleEn,
                url: '/page/' + val,
                target: '_self',
                enabled: true
            });
            selectEl.value = '';
        },
        removeMenuItem(idx) {
            if (confirm('تأكيد حذف هذا الرابط من القائمة؟')) {
                this.menuItems.splice(idx, 1);
            }
        },
        moveMenuUp(idx) {
            if (idx > 0) {
                const itm = this.menuItems.splice(idx, 1)[0];
                this.menuItems.splice(idx - 1, 0, itm);
            }
        },
        moveMenuDown(idx) {
            if (idx < this.menuItems.length - 1) {
                const itm = this.menuItems.splice(idx, 1)[0];
                this.menuItems.splice(idx + 1, 0, itm);
            }
        },
        sendTestSms() {
            if (!this.testMobile || this.testMobile.length < 9) {
                Swal.fire({
                    icon: 'warning',
                    title: 'تنبيه',
                    text: 'الرجاء إدخال رقم جوال صحيح للاختبار (مثال: 0501234567).',
                    confirmButtonText: 'حسناً',
                    confirmButtonColor: '#059669'
                });
                return;
            }

            this.isTestingSms = true;
            const formData = new FormData();
            formData.append('_csrf', '<?= \App\Core\Session::csrfToken() ?>');
            formData.append('test_mobile', this.testMobile);

            fetch('<?= url('admin/settings/test-sms') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                this.isTestingSms = false;
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم الاختبار بنجاح!',
                        html: '<p class="text-sm text-slate-600">' + data.message + '</p>' +
                              (data.provider ? '<span class="inline-block mt-2 px-3 py-1 bg-emerald-100 text-emerald-800 rounded-lg text-xs font-bold">المزود: ' + data.provider + '</span>' : ''),
                        confirmButtonText: 'ممتاز',
                        confirmButtonColor: '#059669'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'فشل إرسال رسالة الفحص',
                        text: data.message || 'حدث خطأ أثناء محاولة الاتصال بالبوابة.',
                        confirmButtonText: 'إغلاق',
                        confirmButtonColor: '#e11d48'
                    });
                }
            })
            .catch(err => {
                this.isTestingSms = false;
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ بالاتصال',
                    text: 'تعذر التواصل مع الخادم، يرجى المحاولة لاحقاً.',
                    confirmButtonText: 'إغلاق',
                    confirmButtonColor: '#e11d48'
                });
            });
        },
        submitSettings() {
            this.$refs.menuJsonInput.value = JSON.stringify(this.menuItems);
            this.$refs.settingsForm.submit();
        }
    };
}
</script>
