<?php
$sections = [];
if (!empty($page['sections_json'])) {
    $decoded = json_decode($page['sections_json'], true);
    if (is_array($decoded)) {
        $sections = $decoded;
    }
}
$formConfig = \App\Models\ContentPage::getFormConfig($page);
?>

<div x-data="contentEditor()" class="space-y-6">
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?= url('admin/content') ?>" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors">
                <i class="bi <?= is_rtl() ? 'bi-arrow-right' : 'bi-arrow-left' ?> text-lg"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-900"><?= e($page['title']) ?></h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <?= e($page['slug']) ?>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">تحكم كامل بمحتوى الصفحة والأقسام والنموذج التفاعلي باللغتين</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="<?= url('page/' . $page['slug']) ?>" target="_blank" class="px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 inline-flex items-center gap-2 transition-colors">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>معاينة في الموقع</span>
            </a>
            <button type="button" @click="submitForm()" class="px-5 py-2 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-600/30 inline-flex items-center gap-2 transition-all">
                <i class="bi bi-check2-circle text-base"></i>
                <span>حفظ التعديلات</span>
            </button>
        </div>
    </div>

    <!-- Main Form -->
    <form x-ref="pageForm" method="post" action="<?= url('admin/content/' . $page['slug']) ?>" class="space-y-6">
        <?= csrf_field() ?>
        <input type="hidden" name="sections_json" x-ref="sectionsJsonInput">

        <!-- Tab Selector: Arabic / English / Sections / Form -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50/50 p-2 flex flex-wrap items-center gap-2">
                <button type="button" @click="activeTab = 'ar'" :class="activeTab === 'ar' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-4 py-2 rounded-xl text-sm transition-all inline-flex items-center gap-2">
                    <span>🇸🇦 المحتوى بالعربية</span>
                </button>
                <button type="button" @click="activeTab = 'en'" :class="activeTab === 'en' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-4 py-2 rounded-xl text-sm transition-all inline-flex items-center gap-2">
                    <span>🇬🇧 English Content</span>
                </button>
                <button type="button" @click="activeTab = 'sections'" :class="activeTab === 'sections' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-4 py-2 rounded-xl text-sm transition-all inline-flex items-center gap-2">
                    <i class="bi bi-grid-1x2"></i>
                    <span>الأقسام الفرعية التفاعلية</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800" x-text="sections.length"></span>
                </button>
                <button type="button" @click="activeTab = 'form'" :class="activeTab === 'form' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-4 py-2 rounded-xl text-sm transition-all inline-flex items-center gap-2">
                    <i class="bi bi-ui-checks-grid"></i>
                    <span>النموذج التفاعلي (Dynamic Form)</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $formConfig['enabled'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' ?>">
                        <?= $formConfig['enabled'] ? 'مفعل' : 'معطل' ?>
                    </span>
                </button>
            </div>

            <!-- TAB 1: Arabic Content -->
            <div x-show="activeTab === 'ar'" class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">عنوان الصفحة (عربي) <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" value="<?= e($page['title']) ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-slate-800 font-medium transition-all" placeholder="مثال: من نحن">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">المقدمة / المحتوى الرئيسي (عربي) <span class="text-rose-500">*</span></label>
                    <textarea name="content" rows="6" required class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-slate-800 leading-relaxed transition-all" placeholder="اكتب نبذة أو مقدمة الصفحة هنا..."><?= e($page['content']) ?></textarea>
                    <p class="text-xs text-slate-400 mt-1">يظهر هذا النص كمقدمة بارزة وأساسية في أعلى الصفحة الداخلية.</p>
                </div>
            </div>

            <!-- TAB 2: English Content -->
            <div x-show="activeTab === 'en'" class="p-6 space-y-5" dir="ltr">
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Page Title (English)</label>
                    <input type="text" name="title_en" value="<?= e($page['title_en'] ?? '') ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-slate-800 font-medium transition-all" placeholder="e.g. About Us">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Main Content / Intro (English)</label>
                    <textarea name="content_en" rows="6" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-slate-800 leading-relaxed transition-all" placeholder="Write page introduction or body here..."><?= e($page['content_en'] ?? '') ?></textarea>
                    <p class="text-xs text-slate-400 mt-1">Displays when the visitor switches the language to English.</p>
                </div>
            </div>

            <!-- TAB 3: Dynamic Sections Builder -->
            <div x-show="activeTab === 'sections'" class="p-6 space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">الأقسام والبطاقات التفصيلية</h3>
                        <p class="text-xs text-slate-500 mt-0.5">يمكنك إضافة بنود، مميزات، مواد تنظيمية، أو تفاصيل تواصل تظهر كشبكة بطاقات فخمة.</p>
                    </div>
                    <button type="button" @click="addSection()" class="px-4 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 inline-flex items-center gap-2 transition-colors">
                        <i class="bi bi-plus-circle-fill text-sm"></i>
                        <span>إضافة قسم جديد</span>
                    </button>
                </div>

                <!-- Empty State -->
                <template x-if="sections.length === 0">
                    <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-2xl">
                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                            <i class="bi bi-grid text-xl"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-700">لا توجد أقسام فرعية مضافة بعد</p>
                        <p class="text-xs text-slate-400 mt-1 mb-4">أضف أقساماً لعرض المبادئ، الشروط، أو المميزات بتصميم بطاقات جذاب</p>
                        <button type="button" @click="addSection()" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 inline-flex items-center gap-2">
                            <i class="bi bi-plus"></i>
                            <span>إضافة أول قسم</span>
                        </button>
                    </div>
                </template>

                <!-- Sections List -->
                <div class="space-y-4">
                    <template x-for="(sec, idx) in sections" :key="idx">
                        <div class="bg-slate-50/70 border border-slate-200 rounded-2xl p-5 relative transition-all hover:border-slate-300">
                            <!-- Section Header & Actions -->
                            <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-200/80 mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center" x-text="idx + 1"></span>
                                    <span class="text-sm font-bold text-slate-800" x-text="sec.title_ar || ('قسم جديد #' + (idx + 1))"></span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="moveUp(idx)" :disabled="idx === 0" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white disabled:opacity-30 transition-colors" title="تحريك لأعلى">
                                        <i class="bi bi-arrow-up"></i>
                                    </button>
                                    <button type="button" @click="moveDown(idx)" :disabled="idx === sections.length - 1" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white disabled:opacity-30 transition-colors" title="تحريك لأسفل">
                                        <i class="bi bi-arrow-down"></i>
                                    </button>
                                    <button type="button" @click="removeSection(idx)" class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition-colors ml-2" title="حذف القسم">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Form Fields for this Section -->
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <!-- Icon Selector -->
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الأيقونة (Bootstrap Icon)</label>
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-emerald-600 text-lg shrink-0">
                                            <i :class="'bi bi-' + (sec.icon || 'star')"></i>
                                        </div>
                                        <select x-model="sec.icon" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white">
                                            <option value="shield-check">🛡️ shield-check (حماية/أمان)</option>
                                            <option value="handshake">🤝 handshake (تعاون/قرض)</option>
                                            <option value="award">🏆 award (حوكمة/حقوق)</option>
                                            <option value="smartphone">📱 smartphone (تطبيق/بوابة)</option>
                                            <option value="cash-coin">💰 cash-coin (أموال/أسهم)</option>
                                            <option value="check-circle">✅ check-circle (شروط/اعتماد)</option>
                                            <option value="calendar-check">📅 calendar-check (مواعيد)</option>
                                            <option value="lock">🔒 lock (خصوصية/أمان)</option>
                                            <option value="eye-slash">👁️ eye-slash (سرية)</option>
                                            <option value="telephone">📞 telephone (هاتف)</option>
                                            <option value="envelope">✉️ envelope (إيميل)</option>
                                            <option value="chat-dots">💬 chat-dots (محادثة/دعم)</option>
                                            <option value="star">⭐ star (ميزة)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Badges -->
                                <div class="md:col-span-4">
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الوسم بالعربي (Badge AR)</label>
                                    <input type="text" x-model="sec.badge" placeholder="مثال: حوكمة وأمان" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white">
                                </div>
                                <div class="md:col-span-5" dir="ltr">
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Badge EN</label>
                                    <input type="text" x-model="sec.badge_en" placeholder="e.g. Governance" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white">
                                </div>

                                <!-- Titles -->
                                <div class="md:col-span-6">
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">عنوان القسم (عربي) <span class="text-rose-500">*</span></label>
                                    <input type="text" x-model="sec.title_ar" placeholder="مثال: الأمان والشفافية التامة" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white font-bold">
                                </div>
                                <div class="md:col-span-6" dir="ltr">
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Section Title (EN)</label>
                                    <input type="text" x-model="sec.title_en" placeholder="e.g. Complete Transparency" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white font-bold">
                                </div>

                                <!-- Body Descriptions -->
                                <div class="md:col-span-6">
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تفاصيل وشرح البند (عربي)</label>
                                    <textarea x-model="sec.body_ar" rows="3" placeholder="تفاصيل هذا القسم بالعربية..." class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white"></textarea>
                                </div>
                                <div class="md:col-span-6" dir="ltr">
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Section Body (EN)</label>
                                    <textarea x-model="sec.body_en" rows="3" placeholder="Details in English..." class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white"></textarea>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Add Button at bottom -->
                <div class="pt-2">
                    <button type="button" @click="addSection()" class="w-full py-3 rounded-xl border-2 border-dashed border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/50 text-slate-600 hover:text-emerald-700 font-bold text-sm inline-flex items-center justify-center gap-2 transition-all">
                        <i class="bi bi-plus-circle"></i>
                        <span>إضافة قسم فرعي آخر</span>
                    </button>
                </div>
            </div>

            <!-- TAB 4: Dynamic Interactive Form Settings -->
            <div x-show="activeTab === 'form'" class="p-6 space-y-6">
                <!-- Master Form Switch -->
                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/80 flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i class="bi bi-ui-checks text-emerald-600 text-lg"></i>
                            <span>تفعيل نموذج تفاعلي في هذه الصفحة (Enable Dynamic Form)</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">
                            عند تفعيل هذا الخيار، سيظهر نموذج تفاعلي متكامل في أسفل الصفحة يستقبل رسائل واستفسارات الأعضاء والزوار ويحفظها مباشرة في لوحة التحكم (رسائل الدعم).
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" name="form_enabled" value="1" <?= $formConfig['enabled'] ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <!-- Form Attributes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع النموذج والغاية منه</label>
                        <select name="form_type" class="w-full text-xs py-2.5 px-3 rounded-xl border border-slate-300 bg-white">
                            <option value="contact" <?= ($formConfig['type'] ?? '') === 'contact' ? 'selected' : '' ?>>📬 نموذج تواصل واستفسارات عامة</option>
                            <option value="suggestion" <?= ($formConfig['type'] ?? '') === 'suggestion' ? 'selected' : '' ?>>💡 نموذج اقتراحات ومبادرات أسرية</option>
                            <option value="complaint" <?= ($formConfig['type'] ?? '') === 'complaint' ? 'selected' : '' ?>>⚠️ نموذج ملاحظات أو شكاوى خاصة</option>
                            <option value="shares_inquiry" <?= ($formConfig['type'] ?? '') === 'shares_inquiry' ? 'selected' : '' ?>>📈 استفسار حول الأسهم والاشتراكات</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-6 pt-5">
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                            <input type="checkbox" name="form_require_email" value="1" <?= !empty($formConfig['require_email']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                            <span>طلب البريد الإلكتروني</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                            <input type="checkbox" name="form_require_subject" value="1" <?= !empty($formConfig['require_subject']) ? 'checked' : '' ?> class="rounded text-emerald-600">
                            <span>طلب موضوع الاستفسار</span>
                        </label>
                    </div>

                    <!-- Arabic Titles -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">عنوان النموذج (عربي)</label>
                        <input type="text" name="form_title_ar" value="<?= e($formConfig['title_ar'] ?? 'نموذج التواصل والاستفسارات') ?>" placeholder="مثال: نسعد بتواصلكم معنا" class="w-full text-xs py-2.5 px-3 rounded-xl border border-slate-300 font-bold">
                    </div>

                    <!-- English Titles -->
                    <div dir="ltr">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Form Title (English)</label>
                        <input type="text" name="form_title_en" value="<?= e($formConfig['title_en'] ?? 'Inquiries & Contact Form') ?>" placeholder="e.g. Get in Touch With Us" class="w-full text-xs py-2.5 px-3 rounded-xl border border-slate-300 font-bold">
                    </div>

                    <!-- Arabic Description -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">وصف النموذج وإرشادات التعبئة (عربي)</label>
                        <textarea name="form_desc_ar" rows="3" class="w-full text-xs py-2.5 px-3 rounded-xl border border-slate-300"><?= e($formConfig['desc_ar'] ?? 'نسعد باستقبال استفساراتكم وملاحظاتكم وسيقوم فريق الصندوق بمتابعتها والرد عليكم.') ?></textarea>
                    </div>

                    <!-- English Description -->
                    <div dir="ltr">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Form Description (English)</label>
                        <textarea name="form_desc_en" rows="3" class="w-full text-xs py-2.5 px-3 rounded-xl border border-slate-300"><?= e($formConfig['desc_en'] ?? 'We welcome your questions and suggestions. Our team will follow up promptly.') ?></textarea>
                    </div>

                    <!-- Success Message AR -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">رسالة النجاح بعد الإرسال (عربي)</label>
                        <input type="text" name="form_success_msg_ar" value="<?= e($formConfig['success_msg_ar'] ?? 'تم إرسال رسالتكم بنجاح وسيتواصل معكم فريق الصندوق.') ?>" class="w-full text-xs py-2.5 px-3 rounded-xl border border-slate-300">
                    </div>

                    <!-- Success Message EN -->
                    <div dir="ltr">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Success Message (English)</label>
                        <input type="text" name="form_success_msg_en" value="<?= e($formConfig['success_msg_en'] ?? 'Your message has been received successfully.') ?>" class="w-full text-xs py-2.5 px-3 rounded-xl border border-slate-300">
                    </div>
                </div>

                <!-- Live Feature Highlight Note -->
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-xs text-emerald-800 flex items-start gap-3">
                    <i class="bi bi-shield-check text-emerald-600 text-lg shrink-0"></i>
                    <div>
                        <span class="font-bold block">تربيط ذكي تلقائي مع لوحة التحكم:</span>
                        <span class="text-slate-600">أي رسالة يرسلها الزائر أو العضو عبر هذا النموذج ستظهر فورياً في لوحة تحكم الإدارة ضمن قائمة (رسائل الدعم والتواصل)، مع ربط رقم هوية العضو وحسابه آلياً إن كان مسجلاً للدخول.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Action -->
        <div class="flex items-center justify-between p-4 bg-white rounded-2xl border border-slate-200 shadow-sm">
            <a href="<?= url('admin/content') ?>" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                إلغاء والعودة
            </a>
            <button type="button" @click="submitForm()" class="px-7 py-2.5 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-600/30 inline-flex items-center gap-2 transition-all">
                <i class="bi bi-check2-circle text-lg"></i>
                <span>حفظ جميع التعديلات</span>
            </button>
        </div>
    </form>
</div>

<script>
function contentEditor() {
    return {
        activeTab: 'ar',
        sections: <?= json_encode($sections, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
        addSection() {
            this.sections.push({
                icon: 'star',
                badge: '',
                badge_en: '',
                title_ar: '',
                title_en: '',
                body_ar: '',
                body_en: ''
            });
            this.activeTab = 'sections';
        },
        removeSection(idx) {
            if (confirm('هل أنت متأكد من حذف هذا القسم؟')) {
                this.sections.splice(idx, 1);
            }
        },
        moveUp(idx) {
            if (idx > 0) {
                const item = this.sections.splice(idx, 1)[0];
                this.sections.splice(idx - 1, 0, item);
            }
        },
        moveDown(idx) {
            if (idx < this.sections.length - 1) {
                const item = this.sections.splice(idx, 1)[0];
                this.sections.splice(idx + 1, 0, item);
            }
        },
        submitForm() {
            this.$refs.sectionsJsonInput.value = JSON.stringify(this.sections);
            this.$refs.pageForm.submit();
        }
    };
}
</script>
