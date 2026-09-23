<?php
/**
 * @var bool    $enabled
 * @var array   $entries   site-wide translations (App\LiveTranslate\Dictionary)
 * @var array[] $languages
 */
uasort($entries, static fn($a, $b) => (int)($b['updated'] ?? 0) <=> (int)($a['updated'] ?? 0));
$ar = is_rtl();
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-900"><?= $ar ? 'الترجمة الفورية' : 'Live Translate' ?></h1>
            <p class="text-xs text-slate-500 mt-1">
                <?= $ar ? 'اضغط على أي نص في الموقع أو في لوحة التحكم، واكتبه بكل اللغات في نافذة صغيرة، ويُحفظ مكانه مباشرة.'
                        : 'Click any text on the site or in the dashboard, write it in every language in a small window and it is saved in place.' ?>
            </p>
        </div>
        <a href="<?= url('/') ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all">
            <i class="bi bi-globe text-sky-600"></i>
            <span><?= __('visit_website') ?></span>
        </a>
    </div>

    <!-- On / off -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <form action="<?= url('admin/live-translate/toggle') ?>" method="post" class="flex flex-wrap items-center justify-between gap-4">
            <?= csrf_field() ?>
            <div class="min-w-0">
                <h2 class="text-sm font-bold text-slate-900"><?= $ar ? 'تفعيل الترجمة الفورية' : 'Turn Live Translate on' ?></h2>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                    <?= $ar ? 'عند التفعيل يظهر زر «ترجمة فورية» في أعلى لوحة التحكم، وزر «ترجمة» عائم في الموقع للمشرف المسجّل فقط. عند الإيقاف لا يُحمَّل أي كود للترجمة وتتوقف الترجمات العامة.'
                            : 'When on, a “Live translate” button appears in the dashboard top bar and a floating “Translate” button on the site, for the logged-in administrator only. When off, no translation code is loaded and site-wide translations stop.' ?>
                </p>
            </div>
            <input type="hidden" name="lt_enabled" value="<?= $enabled ? '0' : '1' ?>">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold border transition-all <?= $enabled ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border-emerald-200' : 'text-slate-600 bg-slate-50 hover:bg-slate-100 border-slate-200' ?>">
                <i class="bi <?= $enabled ? 'bi-toggle-on text-emerald-600' : 'bi-toggle-off text-slate-400' ?> text-lg"></i>
                <span><?= $enabled ? ($ar ? 'مُفعّلة، اضغط للإيقاف' : 'On, click to turn off') : ($ar ? 'متوقفة، اضغط للتفعيل' : 'Off, click to turn on') ?></span>
            </button>
        </form>
    </div>

    <!-- How to use -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <h2 class="text-sm font-bold text-slate-900 mb-3"><?= $ar ? 'طريقة الاستخدام' : 'How to use it' ?></h2>
        <ol class="text-xs text-slate-600 space-y-1.5 list-decimal ps-5 leading-relaxed">
            <?php if ($ar): ?>
                <li>اضغط زر <b>«ترجمة فورية»</b> في أعلى لوحة التحكم، أو زر <b>«ترجمة»</b> العائم في صفحات الموقع (لازم تكون مسجّل دخول كمشرف).</li>
                <li>مرّر الفأرة على أي نص فيظهر إطار أزرق حوله، ثم اضغط عليه. اضغط <b>Ctrl</b> مع الرابط إذا أردت التنقل.</li>
                <li>في النافذة ستجد النص بكل اللغات وقائمة بالأماكن التي وُجد فيها (صفحة، إعداد، نص ثابت ...). اختر المكان واحفظ.</li>
                <li>إذا كان النص مكتوبًا داخل قالب الصفحة ولا يوجد له مكان في قاعدة البيانات، يُحفظ كـ<b>ترجمة عامة</b> وتظهر في الجدول بالأسفل.</li>
                <li>قفل الزر من لوحة التحكم يُخفي زر الموقع أيضًا في كل التبويبات المفتوحة، وفتحه يُظهره من جديد.</li>
            <?php else: ?>
                <li>Click <b>“Live translate”</b> in the dashboard top bar, or the floating <b>“Translate”</b> button on the site (you have to be logged in as an administrator).</li>
                <li>Hover any text (a blue frame appears) and click it. Hold <b>Ctrl</b> while clicking a link to follow it.</li>
                <li>The window shows the text in every language and the places where it is stored (page, setting, static text ...). Choose where and save.</li>
                <li>A text typed inside a page template, with no place in the database, is saved as a <b>site-wide translation</b> and listed below.</li>
                <li>Closing the button in the dashboard also hides the site button in every open tab; opening it shows it again.</li>
            <?php endif; ?>
        </ol>
    </div>

    <!-- Site-wide translations -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 p-5 border-b border-slate-100">
            <div>
                <h2 class="text-sm font-bold text-slate-900"><?= $ar ? 'الترجمات العامة' : 'Site-wide translations' ?> (<?= count($entries) ?>)</h2>
                <p class="text-xs text-slate-500 mt-1"><?= $ar ? 'نصوص غير مخزنة في قاعدة البيانات، تُستبدل تلقائيًا بلغة الصفحة أينما ظهرت.' : 'Texts that are not stored in the database; they are replaced by the language of the page wherever they appear.' ?></p>
            </div>
            <a href="<?= url('admin/live-translate/export') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all">
                <i class="bi bi-download text-sky-600"></i>
                <span><?= $ar ? 'تصدير JSON' : 'Export JSON' ?></span>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-start text-sm">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wider">
                        <th class="py-3.5 px-5 text-start font-bold"><?= $ar ? 'النص الأصلي' : 'Original text' ?></th>
                        <?php foreach ($languages as $language): ?>
                            <th class="py-3.5 px-5 text-start font-bold"><?= e($language['flag'] . ' ' . $language['name']) ?></th>
                        <?php endforeach; ?>
                        <th class="py-3.5 px-5 text-end font-bold"><?= $ar ? 'إجراءات' : 'Actions' ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$entries): ?>
                        <tr>
                            <td colspan="<?= count($languages) + 2 ?>" class="py-8 px-5 text-center text-xs text-slate-400">
                                <?= $ar ? 'لا توجد ترجمات عامة بعد. فعّل وضع الترجمة واضغط على أي نص غير مخزّن في قاعدة البيانات.' : 'No site-wide translations yet. Turn Translate mode on and click a text that is not stored in the database.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($entries as $id => $entry): ?>
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-5 text-xs text-slate-500"><?= e((string)($entry['source'] ?? '')) ?></td>
                            <?php foreach ($languages as $language): ?>
                                <td class="py-3.5 px-5 text-xs text-slate-800" dir="<?= $language['rtl'] ? 'rtl' : 'ltr' ?>"><?= e((string)($entry['values'][$language['code']] ?? '—')) ?></td>
                            <?php endforeach; ?>
                            <td class="py-3.5 px-5 text-end whitespace-nowrap">
                                <form action="<?= url('admin/live-translate/' . $id . '/delete') ?>" method="post" class="inline" onsubmit="return confirm('<?= $ar ? 'هل تريد حذف هذه الترجمة العامة؟' : 'Delete this site-wide translation?' ?>')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800 bg-transparent border-0 cursor-pointer"><?= $ar ? 'حذف' : 'Delete' ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Import -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <h2 class="text-sm font-bold text-slate-900 mb-1"><?= $ar ? 'استيراد ترجمات عامة' : 'Import site-wide translations' ?></h2>
        <p class="text-xs text-slate-500 mb-4"><?= $ar ? 'ارفع ملف JSON تم تصديره من هذه الصفحة. لا تُستبدل الترجمة الأحدث الموجودة عندك.' : 'Upload a JSON file exported from this page. A newer translation you already have is kept.' ?></p>
        <form action="<?= url('admin/live-translate/import') ?>" method="post" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
            <?= csrf_field() ?>
            <input type="file" name="file" accept="application/json,.json" required class="text-xs text-slate-600">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-all">
                <i class="bi bi-upload text-emerald-600"></i>
                <span><?= $ar ? 'استيراد' : 'Import' ?></span>
            </button>
        </form>
    </div>
</div>
