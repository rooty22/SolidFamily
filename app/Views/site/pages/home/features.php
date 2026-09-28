<!-- Pillars Section -->
<section id="<?= e($sec['id']) ?>" class="py-16 sm:py-20 lg:py-28 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <h2 class="text-xs font-extrabold uppercase tracking-widest text-brand-600 mb-2"><?= $t('kicker') ?></h2>
            <h3 class="text-3xl sm:text-4xl font-black text-slate-900 mb-4"><?= $t('title') ?></h3>
            <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
                <?= $tb('desc') ?>
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
            <?php foreach ($sec['items'] as $item): $c = $item['color']; ?>
            <div class="bg-slate-50 hover:bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 hover:border-<?= $c ?>-500/50 hover:shadow-xl transition-all duration-300 group flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-<?= $c ?>-100 text-<?= $c ?>-600 flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="bi bi-<?= e($item['icon']) ?>"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 mb-3"><?= $t('title', $item) ?></h4>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4">
                        <?= $tb('body', $item) ?>
                    </p>
                </div>
                <div class="text-xs font-bold text-<?= $c ?>-700 flex items-center gap-1">
                    <span><?= $t('title', $item) ?></span>
                    <i class="bi bi-arrow-<?= is_rtl() ? 'left' : 'right' ?>"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
