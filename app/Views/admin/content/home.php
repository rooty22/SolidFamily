<?php
use App\Models\HomePage;

$L = fn(string $ar, string $en): string => is_rtl() ? $ar : $en;
$lang = is_rtl() ? 'ar' : 'en';

$fieldLabels = [
    'badge' => $L('الوسم العلوي', 'Top badge'),
    'title' => $L('العنوان', 'Title'),
    'title_2' => $L('السطر الثاني الملوّن من العنوان', 'Highlighted second title line'),
    'desc' => $L('الوصف', 'Description'),
    'kicker' => $L('عنوان صغير فوق العنوان', 'Small heading above the title'),
    'body' => $L('النص', 'Text'),
    'button_text' => $L('نص الزر / الرابط', 'Button / link text'),
    'disclaimer' => $L('ملاحظة أسفل الحاسبة', 'Note under the calculator'),
    'quote' => $L('نص الاقتباس', 'Quote text'),
    'source' => $L('المصدر', 'Source'),
    'caption' => $L('تعليق أسفل الصورة', 'Caption under the image'),
    'stat_shares' => $L('عنوان إحصائية الأسهم', 'Shares stat label'),
    'stat_share_val' => $L('عنوان إحصائية قيمة السهم', 'Share value stat label'),
    'stat_members' => $L('عنوان إحصائية الأعضاء', 'Members stat label'),
    'stat_transparency' => $L('عنوان إحصائية الشفافية', 'Transparency stat label'),
];

$types = [];
foreach (HomePage::TYPES as $type => $schema) {
    $fields = [];
    foreach ($schema['text'] as $field => $max) {
        $fields[] = ['key' => $field, 'label' => $fieldLabels[$field] ?? $field, 'long' => $max > 300, 'max' => $max];
    }
    $types[$type] = [
        'label' => $schema['label'][$lang], 'icon' => $schema['icon'], 'builtin' => $schema['builtin'], 'fields' => $fields,
        'image' => $schema['image'], 'position' => $schema['position'], 'bg' => $schema['bg'], 'link' => $schema['link'],
        'items' => $schema['items'], 'blank' => HomePage::blank($type),
    ];
}

$linkSuggestions = ['/register', '/login', '/#features', '/#calculator', '/#charter'];
foreach ($pages as $p) {
    $linkSuggestions[] = '/page/' . $p['slug'];
}
$iconSuggestions = ['star', 'star-fill', 'stars', 'shield-check', 'shield-lock', 'heart', 'heart-fill', 'people', 'people-fill', 'person-heart',
    'house-heart', 'cash-coin', 'cash-stack', 'coin', 'wallet2', 'bank', 'bank2', 'piggy-bank', 'graph-up-arrow', 'pie-chart-fill',
    'bar-chart', 'award', 'trophy', 'gem', 'lightning', 'check-circle', 'check2-all', 'calendar-check', 'clock-history', 'lock',
    'eye', 'hand-thumbs-up', 'globe', 'phone', 'telephone', 'envelope', 'chat-dots', 'geo-alt', 'book', 'mortarboard',
    'briefcase', 'gift', 'flower1', 'sun', 'moon-stars', 'building', 'tree', 'lightbulb', 'megaphone', 'info-circle'];
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
?>

<div x-data="homeBuilder()" x-init="init()" class="space-y-6">
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="<?= url('admin/content') ?>" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors">
                <i class="bi <?= is_rtl() ? 'bi-arrow-right' : 'bi-arrow-left' ?> text-lg"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-900"><?= $L('الصفحة الرئيسية', 'Home Page') ?></h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200" dir="ltr">/</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5"><?= $L('رتّب الأقسام، عدّل نصوصها بالعربية والإنجليزية، أضف أقساماً جديدة بصور ونصوص، أو أخفِ ما لا تريده', 'Reorder sections, edit their Arabic & English texts, add new sections with images and text, or hide any of them') ?></p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <span x-show="dirty" x-cloak class="text-xs font-bold text-amber-600 inline-flex items-center gap-1">
                <i class="bi bi-exclamation-circle"></i><?= $L('تعديلات غير محفوظة', 'Unsaved changes') ?>
            </span>
            <a href="<?= url('/') ?>" target="_blank" class="px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 inline-flex items-center gap-2 transition-colors">
                <i class="bi bi-box-arrow-up-right"></i>
                <span><?= $L('معاينة', 'Preview') ?></span>
            </a>
            <button type="button" @click="save()" class="px-5 py-2 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-600/30 inline-flex items-center gap-2 transition-all">
                <i class="bi bi-check2-circle text-base"></i>
                <span><?= $L('حفظ التعديلات', 'Save changes') ?></span>
            </button>
        </div>
    </div>

    <form x-ref="saveForm" method="post" action="<?= url('admin/content/home') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="sections_json" x-ref="sectionsJson">
    </form>

    <datalist id="hb-icons">
        <?php foreach ($iconSuggestions as $icon): ?><option value="<?= e($icon) ?>"></option><?php endforeach; ?>
    </datalist>
    <datalist id="hb-links">
        <?php foreach ($linkSuggestions as $link): ?><option value="<?= e($link) ?>"></option><?php endforeach; ?>
    </datalist>

    <!-- Sections -->
    <div class="space-y-4">
        <template x-for="(sec, idx) in sections" :key="sec._key">
            <div class="bg-white border rounded-2xl shadow-sm overflow-hidden transition-all" :class="sec.enabled ? 'border-slate-200/80' : 'border-dashed border-slate-300 opacity-75'">
                <!-- Section header -->
                <div class="flex flex-wrap items-center justify-between gap-3 p-4 cursor-pointer select-none" :class="sec._open ? 'border-b border-slate-100 bg-slate-50/60' : ''" @click="sec._open = !sec._open">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center shrink-0" x-text="idx + 1"></span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <i :class="'bi bi-' + types[sec.type].icon"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-slate-800" x-text="types[sec.type].label"></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="types[sec.type].builtin ? 'bg-slate-100 text-slate-500' : 'bg-sky-50 text-sky-700 border border-sky-200'" x-text="types[sec.type].builtin ? '<?= $L('أساسي', 'Built-in') ?>' : '<?= $L('مخصص', 'Custom') ?>'"></span>
                                <span x-show="!sec.enabled" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"><?= $L('مخفي', 'Hidden') ?></span>
                            </div>
                            <div class="text-xs text-slate-400 truncate max-w-md" x-text="preview(sec)"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1" @click.stop>
                        <label class="relative inline-flex items-center cursor-pointer me-2" title="<?= $L('إظهار / إخفاء', 'Show / hide') ?>">
                            <input type="checkbox" x-model="sec.enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                        <button type="button" @click="move(sections, idx, -1)" :disabled="idx === 0" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 disabled:opacity-30" title="<?= $L('تحريك لأعلى', 'Move up') ?>"><i class="bi bi-arrow-up"></i></button>
                        <button type="button" @click="move(sections, idx, 1)" :disabled="idx === sections.length - 1" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 disabled:opacity-30" title="<?= $L('تحريك لأسفل', 'Move down') ?>"><i class="bi bi-arrow-down"></i></button>
                        <button type="button" @click="removeSection(idx)" class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50" title="<?= $L('حذف القسم', 'Delete section') ?>"><i class="bi bi-trash3"></i></button>
                        <button type="button" @click="sec._open = !sec._open" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100"><i class="bi" :class="sec._open ? 'bi-chevron-up' : 'bi-chevron-down'"></i></button>
                    </div>
                </div>

                <!-- Section body -->
                <div x-show="sec._open" class="p-5 space-y-5">
                    <!-- Bilingual text fields -->
                    <template x-for="f in types[sec.type].fields" :key="f.key">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5"><span x-text="f.label"></span> <span class="text-slate-400 font-medium">(عربي)</span></label>
                                <template x-if="f.long"><textarea x-model="sec[f.key + '_ar']" :maxlength="f.max" rows="3" class="w-full text-sm py-2 px-3 rounded-xl border border-slate-300 bg-white leading-relaxed" dir="rtl"></textarea></template>
                                <template x-if="!f.long"><input type="text" x-model="sec[f.key + '_ar']" :maxlength="f.max" class="w-full text-sm py-2 px-3 rounded-xl border border-slate-300 bg-white" dir="rtl"></template>
                            </div>
                            <div dir="ltr">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5"><span x-text="f.label"></span> <span class="text-slate-400 font-medium">(English)</span></label>
                                <template x-if="f.long"><textarea x-model="sec[f.key + '_en']" :maxlength="f.max" rows="3" class="w-full text-sm py-2 px-3 rounded-xl border border-slate-300 bg-white leading-relaxed"></textarea></template>
                                <template x-if="!f.long"><input type="text" x-model="sec[f.key + '_en']" :maxlength="f.max" class="w-full text-sm py-2 px-3 rounded-xl border border-slate-300 bg-white"></template>
                            </div>
                        </div>
                    </template>

                    <!-- Hero: stats ribbon -->
                    <template x-if="sec.type === 'hero'">
                        <label class="flex items-center gap-2 text-sm font-bold text-slate-700 cursor-pointer">
                            <input type="checkbox" x-model="sec.show_stats" class="rounded text-emerald-600">
                            <span><?= $L('إظهار شريط الإحصائيات (الأسهم، قيمة السهم، الأعضاء)', 'Show the stats ribbon (shares, share value, members)') ?></span>
                        </label>
                    </template>

                    <!-- Image -->
                    <template x-if="types[sec.type].image">
                        <div class="bg-slate-50/70 border border-slate-200 rounded-2xl p-4">
                            <label class="block text-xs font-bold text-slate-700 mb-2">
                                <span x-text="sec.type === 'hero' ? '<?= $L('صورة خلفية للبنر (اختيارية)', 'Banner background image (optional)') ?>' : '<?= $L('الصورة', 'Image') ?>'"></span>
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                <div class="md:col-span-4 flex items-center justify-center p-3 bg-white rounded-xl border border-slate-200 min-h-[110px]">
                                    <template x-if="sec.image"><img :src="imgSrc(sec.image)" class="max-h-28 max-w-full object-contain rounded-lg"></template>
                                    <template x-if="!sec.image"><div class="text-center text-slate-400 text-xs"><i class="bi bi-image text-3xl block mb-1"></i><?= $L('لا توجد صورة', 'No image') ?></div></template>
                                </div>
                                <div class="md:col-span-8 space-y-2.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <label class="px-4 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 inline-flex items-center gap-2 cursor-pointer">
                                            <i class="bi" :class="sec._uploading ? 'bi-hourglass-split' : 'bi-upload'"></i>
                                            <span x-text="sec._uploading ? '<?= $L('جاري الرفع...', 'Uploading...') ?>' : '<?= $L('رفع صورة من الجهاز', 'Upload an image') ?>'"></span>
                                            <input type="file" accept="image/png,image/jpeg,image/webp,image/gif" class="hidden" @change="upload($event, sec)" :disabled="sec._uploading">
                                        </label>
                                        <button type="button" x-show="sec.image" @click="sec.image = ''" class="px-3 py-2 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 inline-flex items-center gap-1.5"><i class="bi bi-x-circle"></i><?= $L('إزالة الصورة', 'Remove image') ?></button>
                                    </div>
                                    <input type="text" x-model="sec.image" placeholder="<?= $L('أو الصق رابط صورة https://...', 'or paste an image URL https://...') ?>" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white" dir="ltr">
                                    <p class="text-[11px] text-slate-400">PNG, JPG, WebP, GIF — <?= $L('حتى 5MB', 'up to 5MB') ?></p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Layout options -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4" x-show="types[sec.type].position || types[sec.type].bg || types[sec.type].link">
                        <template x-if="types[sec.type].position">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5"><?= $L('مكان الصورة', 'Image position') ?></label>
                                <select x-model="sec.image_position" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white">
                                    <option value="start"><?= $L('بداية السطر (يمين في العربي)', 'Start (right in Arabic)') ?></option>
                                    <option value="end"><?= $L('نهاية السطر (يسار في العربي)', 'End (left in Arabic)') ?></option>
                                </select>
                            </div>
                        </template>
                        <template x-if="types[sec.type].bg">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5"><?= $L('خلفية القسم', 'Section background') ?></label>
                                <select x-model="sec.bg" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white">
                                    <option value="white"><?= $L('أبيض', 'White') ?></option>
                                    <option value="light"><?= $L('رمادي فاتح', 'Light gray') ?></option>
                                    <option value="dark"><?= $L('داكن', 'Dark') ?></option>
                                    <option value="brand"><?= $L('داكن بلون الهوية', 'Dark brand gradient') ?></option>
                                </select>
                            </div>
                        </template>
                        <template x-if="types[sec.type].link">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5" x-text="sec.type === 'image' ? '<?= $L('رابط عند الضغط على الصورة (اختياري)', 'Link when the image is clicked (optional)') ?>' : '<?= $L('رابط الزر', 'Button link') ?>'"></label>
                                <input type="text" x-model="sec.button_url" list="hb-links" placeholder="/page/about  |  https://..." class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white" dir="ltr">
                            </div>
                        </template>
                    </div>

                    <!-- Items (cards / list) -->
                    <template x-if="types[sec.type].items">
                        <div class="border-t border-slate-100 pt-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-bold text-slate-800">
                                    <span x-text="types[sec.type].items === 'cards' ? '<?= $L('البطاقات', 'Cards') ?>' : '<?= $L('البنود', 'List items') ?>'"></span>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800" x-text="sec.items.length"></span>
                                </h4>
                                <button type="button" @click="addItem(sec)" class="px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 inline-flex items-center gap-1.5">
                                    <i class="bi bi-plus-circle-fill"></i><?= $L('إضافة', 'Add') ?>
                                </button>
                            </div>
                            <template x-for="(item, j) in sec.items" :key="j">
                                <div class="bg-slate-50/70 border border-slate-200 rounded-xl p-4">
                                    <div class="flex items-center justify-between gap-2 mb-3">
                                        <div class="flex items-center gap-2">
                                            <template x-if="types[sec.type].items === 'cards'">
                                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg" :class="'bg-' + item.color + '-100 text-' + item.color + '-600'"><i :class="'bi bi-' + (item.icon || 'star')"></i></div>
                                            </template>
                                            <span class="text-xs font-bold text-slate-700" x-text="(j + 1) + '. ' + (item.title_ar || item.title_en || '<?= $L('بند جديد', 'New item') ?>')"></span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="move(sec.items, j, -1)" :disabled="j === 0" class="p-1 rounded text-slate-400 hover:text-slate-700 disabled:opacity-30"><i class="bi bi-arrow-up"></i></button>
                                            <button type="button" @click="move(sec.items, j, 1)" :disabled="j === sec.items.length - 1" class="p-1 rounded text-slate-400 hover:text-slate-700 disabled:opacity-30"><i class="bi bi-arrow-down"></i></button>
                                            <button type="button" @click="sec.items.splice(j, 1)" class="p-1 rounded text-rose-500 hover:text-rose-700"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <template x-if="types[sec.type].items === 'cards'">
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-600 mb-1"><?= $L('الأيقونة', 'Icon') ?> <a href="https://icons.getbootstrap.com/" target="_blank" class="text-emerald-600 font-medium">(Bootstrap Icons)</a></label>
                                                <input type="text" x-model="item.icon" list="hb-icons" placeholder="star" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white" dir="ltr">
                                            </div>
                                        </template>
                                        <template x-if="types[sec.type].items === 'cards'">
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-600 mb-1"><?= $L('اللون', 'Color') ?></label>
                                                <div class="flex flex-wrap gap-1.5 pt-0.5">
                                                    <template x-for="c in colors" :key="c">
                                                        <button type="button" @click="item.color = c" class="w-7 h-7 rounded-lg border-2 transition-all" :class="['bg-' + c + '-500', item.color === c ? 'border-slate-900 scale-110' : 'border-transparent']" :title="c"></button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1"><?= $L('العنوان', 'Title') ?> (عربي)</label>
                                            <input type="text" x-model="item.title_ar" maxlength="300" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white font-bold" dir="rtl">
                                        </div>
                                        <div dir="ltr">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1"><?= $L('العنوان', 'Title') ?> (English)</label>
                                            <input type="text" x-model="item.title_en" maxlength="300" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white font-bold">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1"><?= $L('الوصف', 'Description') ?> (عربي)</label>
                                            <textarea x-model="item.body_ar" maxlength="3000" rows="2" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white" dir="rtl"></textarea>
                                        </div>
                                        <div dir="ltr">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1"><?= $L('الوصف', 'Description') ?> (English)</label>
                                            <textarea x-model="item.body_en" maxlength="3000" rows="2" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 bg-white"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="sections.length === 0">
            <div class="text-center py-12 bg-white border-2 border-dashed border-slate-200 rounded-2xl text-sm font-bold text-slate-500">
                <?= $L('الصفحة الرئيسية فارغة — أضف قسماً من الأسفل', 'The home page is empty — add a section below') ?>
            </div>
        </template>
    </div>

    <!-- Add a section -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <h3 class="text-sm font-bold text-slate-900 mb-1"><?= $L('إضافة قسم جديد', 'Add a section') ?></h3>
        <p class="text-xs text-slate-500 mb-4"><?= $L('يُضاف القسم في آخر الصفحة، ثم رتّبه بالأسهم. الأقسام الأساسية تُضاف مرة واحدة فقط.', 'New sections go to the end of the page; reorder them with the arrows. Built-in sections can be added once.') ?></p>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
            <template x-for="(t, type) in types" :key="type">
                <button type="button" @click="addSection(type)" :disabled="t.builtin && hasType(type)"
                        class="p-3 rounded-xl border text-start transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="t.builtin ? 'border-slate-200 hover:border-slate-300 bg-slate-50/60' : 'border-emerald-200 hover:border-emerald-400 bg-emerald-50/40'">
                    <i class="text-lg" :class="['bi bi-' + t.icon, t.builtin ? 'text-slate-500' : 'text-emerald-600']"></i>
                    <div class="text-xs font-bold text-slate-800 mt-1" x-text="t.label"></div>
                    <div class="text-[10px] text-slate-400" x-text="t.builtin ? (hasType(type) ? '<?= $L('موجود في الصفحة', 'Already on the page') ?>' : '<?= $L('قسم أساسي', 'Built-in') ?>') : '<?= $L('قسم مخصص', 'Custom') ?>'"></div>
                </button>
            </template>
        </div>
    </div>

    <!-- Footer actions -->
    <div class="flex flex-wrap items-center justify-between gap-3 p-4 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <form method="post" action="<?= url('admin/content/home/reset') ?>" onsubmit="return confirm('<?= $L('سيتم حذف كل تعديلاتك وإرجاع الصفحة الرئيسية لمحتواها الأصلي. متابعة؟', 'All your changes will be discarded and the original home page restored. Continue?') ?>')">
            <?= csrf_field() ?>
            <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 inline-flex items-center gap-2">
                <i class="bi bi-arrow-counterclockwise"></i><?= $L('استعادة المحتوى الافتراضي', 'Restore defaults') ?>
            </button>
        </form>
        <button type="button" @click="save()" class="px-7 py-2.5 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-600/30 inline-flex items-center gap-2 transition-all">
            <i class="bi bi-check2-circle text-lg"></i>
            <span><?= $L('حفظ جميع التعديلات', 'Save all changes') ?></span>
        </button>
    </div>
</div>

<script>
function homeBuilder() {
    let keySeq = 0;
    return {
        types: <?= json_encode($types, $jsonFlags) ?>,
        colors: <?= json_encode(HomePage::COLORS, $jsonFlags) ?>,
        sections: <?= json_encode($sections, $jsonFlags) ?>,
        baseUrl: <?= json_encode(rtrim(url('/'), '/'), $jsonFlags) ?>,
        uploadUrl: <?= json_encode(url('admin/content/home/upload'), $jsonFlags) ?>,
        csrf: <?= json_encode(\App\Core\Session::csrfToken(), $jsonFlags) ?>,
        initial: '',
        submitting: false,
        init() {
            this.sections.forEach(s => { s._key = ++keySeq; s._open = false; s._uploading = false; });
            this.initial = this.cleanJson();
            window.addEventListener('beforeunload', (e) => {
                if (this.dirty && !this.submitting) { e.preventDefault(); e.returnValue = ''; }
            });
        },
        get dirty() {
            return this.cleanJson() !== this.initial;
        },
        cleanJson() {
            return JSON.stringify(this.sections.map(({ _key, _open, _uploading, ...rest }) => rest));
        },
        hasType(type) {
            return this.sections.some(s => s.type === type);
        },
        preview(sec) {
            const f = this.types[sec.type].fields[0];
            const text = sec.title_ar || sec.title_en || (f ? (sec[f.key + '_ar'] || sec[f.key + '_en']) : '') || '';
            return text.length > 90 ? text.slice(0, 90) + '…' : text;
        },
        addSection(type) {
            const t = this.types[type];
            if (t.builtin && this.hasType(type)) return;
            const sec = JSON.parse(JSON.stringify(t.blank));
            if (!t.builtin) sec.id = 'sec_' + Date.now().toString(36);
            if (t.items) sec.items = [];
            Object.assign(sec, { _key: ++keySeq, _open: true, _uploading: false });
            this.sections.push(sec);
            if (t.items === 'cards') this.addItem(sec);
            this.$nextTick(() => window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }));
        },
        removeSection(idx) {
            const t = this.types[this.sections[idx].type];
            const msg = t.builtin
                ? '<?= $L('حذف هذا القسم الأساسي؟ يمكنك إعادة إضافته لاحقاً، أو إخفاؤه بدلاً من الحذف بزر الإظهار.', 'Delete this built-in section? You can add it again later, or just hide it with the switch.') ?>'
                : '<?= $L('هل أنت متأكد من حذف هذا القسم؟', 'Delete this section?') ?>';
            if (confirm(msg)) this.sections.splice(idx, 1);
        },
        addItem(sec) {
            const item = { title_ar: '', title_en: '', body_ar: '', body_en: '' };
            if (this.types[sec.type].items === 'cards') Object.assign(item, { icon: 'star', color: 'emerald' });
            sec.items.push(item);
        },
        move(list, idx, dir) {
            const to = idx + dir;
            if (to < 0 || to >= list.length) return;
            const [el] = list.splice(idx, 1);
            list.splice(to, 0, el);
        },
        imgSrc(path) {
            return /^https?:\/\//i.test(path) ? path : this.baseUrl + '/' + path.replace(/^\/+/, '');
        },
        async upload(event, sec) {
            const file = event.target.files[0];
            if (!file) return;
            sec._uploading = true;
            const fd = new FormData();
            fd.append('_csrf', this.csrf);
            fd.append('image', file);
            try {
                const res = await fetch(this.uploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                let data;
                try { data = await res.json(); } catch (e) { data = { success: false, message: '<?= $L('انتهت صلاحية الجلسة، أعد تحميل الصفحة.', 'Session expired, please reload the page.') ?>' }; }
                if (data.success) {
                    sec.image = data.path;
                } else {
                    Swal.fire({ icon: 'error', text: data.message, confirmButtonColor: '#059669' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', text: '<?= $L('تعذر رفع الصورة، تحقق من الاتصال.', 'Upload failed, check your connection.') ?>', confirmButtonColor: '#059669' });
            } finally {
                sec._uploading = false;
                event.target.value = '';
            }
        },
        save() {
            this.$refs.sectionsJson.value = this.cleanJson();
            this.submitting = true;
            this.$refs.saveForm.submit();
        }
    };
}
</script>
