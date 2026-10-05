<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-900"><?= is_rtl() ? 'إدارة محتوى الصفحات والبنود' : 'Manage Content & Information Pages' ?></h1>
            <p class="text-xs text-slate-500 mt-1"><?= is_rtl() ? 'تحكم كامل بنصوص الصفحات والأقسام والنماذج باللغتين العربية والإنجليزية' : 'Full control over page content, sections, and interactive forms in AR & EN' ?></p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200 inline-flex items-center gap-1.5">
                <i class="bi bi-file-earmark-check-fill text-emerald-600"></i>
                <span><?= count($pages) ?> <?= is_rtl() ? 'صفحات مفعلة' : 'Active Pages' ?></span>
            </span>
        </div>
    </div>

    <!-- Home page builder -->
    <div class="bg-gradient-to-l from-emerald-50 to-white rounded-2xl border border-emerald-200 shadow-sm p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-xl shadow-md shadow-emerald-600/20 shrink-0">
                <i class="bi bi-house-door-fill"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-slate-900"><?= is_rtl() ? 'الصفحة الرئيسية' : 'Home Page' ?></h2>
                    <span class="text-xs text-slate-400 font-mono" dir="ltr">/</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5"><?= is_rtl() ? 'تحكم كامل في سكاشن الرئيسية: تعديل النصوص، الترتيب، الإخفاء، وإضافة أقسام جديدة بصور ونصوص وبطاقات' : 'Full control over home sections: edit texts, reorder, hide, and add new sections with images, text and cards' ?></p>
            </div>
        </div>
        <div class="inline-flex items-center gap-2.5 shrink-0">
            <a href="<?= url('admin/content/home') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition-all whitespace-nowrap">
                <i class="bi bi-pencil-square"></i>
                <span><?= is_rtl() ? 'تعديل الصفحة الرئيسية' : 'Edit Home Page' ?></span>
            </a>
            <a href="<?= url('/') ?>" target="_blank" class="w-9 h-9 rounded-xl bg-white hover:bg-slate-100 text-slate-500 hover:text-slate-800 border border-slate-200 inline-flex items-center justify-center transition-all" title="<?= is_rtl() ? 'معاينة في الموقع' : 'Preview on Site' ?>">
                <i class="bi bi-box-arrow-up-right text-xs"></i>
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-start text-sm">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wider">
                        <th class="py-3.5 px-5 text-start font-bold"><?= is_rtl() ? 'الصفحة / المسار' : 'Page / Path' ?></th>
                        <th class="py-3.5 px-5 text-start font-bold"><?= is_rtl() ? 'العنوان بالإنجليزي' : 'English Title' ?></th>
                        <th class="py-3.5 px-5 text-center font-bold"><?= is_rtl() ? 'الأقسام المضافة' : 'Sections' ?></th>
                        <th class="py-3.5 px-5 text-start font-bold"><?= is_rtl() ? 'آخر تحديث' : 'Last Updated' ?></th>
                        <th class="py-3.5 px-5 text-end font-bold whitespace-nowrap min-w-[200px]"><?= is_rtl() ? 'إجراءات' : 'Actions' ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($pages as $p): ?>
                    <?php 
                        $sectionsCount = 0;
                        if (!empty($p['sections_json'])) {
                            $decoded = json_decode($p['sections_json'], true);
                            if (is_array($decoded)) {
                                $sectionsCount = count($decoded);
                            }
                        }
                    ?>
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg shrink-0">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800"><?= e($p['title']) ?></div>
                                    <div class="text-xs text-slate-400 font-mono mt-0.5" dir="ltr">/page/<?= e($p['slug']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-5 text-slate-600 font-medium" dir="ltr">
                            <?= !empty($p['title_en']) ? e($p['title_en']) : '<span class="text-slate-400 italic">Not set</span>' ?>
                        </td>
                        <td class="py-4 px-5 text-center whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?= $sectionsCount > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' ?>">
                                <i class="bi bi-grid-fill text-[10px]"></i>
                                <span><?= $sectionsCount ?> <?= is_rtl() ? 'أقسام' : 'sections' ?></span>
                            </span>
                        </td>
                        <td class="py-4 px-5 text-slate-500 text-xs whitespace-nowrap">
                            <bdi dir="ltr"><?= date_ar($p['updated_at'], 'd/m/Y H:i') ?></bdi>
                        </td>
                        <td class="py-4 px-5 text-end whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-2.5">
                                <a href="<?= url('admin/content/' . $p['slug'] . '/edit') ?>" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 shadow-sm transition-all whitespace-nowrap">
                                    <i class="bi bi-pencil-square text-emerald-600"></i>
                                    <span><?= is_rtl() ? 'تعديل الأقسام والمحتوى' : 'Edit Sections & Content' ?></span>
                                </a>
                                <a href="<?= url('page/' . $p['slug']) ?>" target="_blank" class="w-8 h-8 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 hover:text-slate-800 border border-slate-200 inline-flex items-center justify-center transition-all shrink-0" title="<?= is_rtl() ? 'معاينة في الموقع' : 'Preview on Site' ?>">
                                    <i class="bi bi-box-arrow-up-right text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
