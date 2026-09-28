<?php
[$bgCls, $headCls, $bodyCls, $kickCls] = \App\Models\HomePage::bgClasses($sec['bg']);
$cardCls = ['dark' => 'bg-slate-800/60 border-slate-700', 'brand' => 'bg-slate-800/60 border-slate-700', 'white' => 'bg-slate-50 border-slate-200/80'][$sec['bg']] ?? 'bg-white border-slate-200/80';
$count = count($sec['items']);
$cols = $count >= 4 ? 'sm:grid-cols-2 lg:grid-cols-4' : ($count === 3 ? 'sm:grid-cols-2 lg:grid-cols-3' : 'sm:grid-cols-2');
?>
<!-- Custom Section: Cards Grid -->
<section id="<?= e($sec['id']) ?>" class="py-16 sm:py-20 lg:py-24 <?= $bgCls ?>">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if ($t('kicker') !== '' || $t('title') !== '' || $t('desc') !== ''): ?>
        <div class="text-center max-w-2xl mx-auto mb-12">
            <?php if ($t('kicker') !== ''): ?>
                <span class="text-xs font-extrabold uppercase tracking-widest <?= $kickCls ?> mb-2 block"><?= $t('kicker') ?></span>
            <?php endif; ?>
            <?php if ($t('title') !== ''): ?>
                <h2 class="text-3xl sm:text-4xl font-black <?= $headCls ?> mb-4"><?= $t('title') ?></h2>
            <?php endif; ?>
            <?php if ($t('desc') !== ''): ?>
                <p class="<?= $bodyCls ?> text-sm sm:text-base leading-relaxed"><?= $tb('desc') ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="grid <?= $cols ?> gap-6">
            <?php foreach ($sec['items'] as $item): $c = $item['color']; ?>
            <div class="<?= $cardCls ?> rounded-3xl p-6 sm:p-7 border hover:border-<?= $c ?>-500/50 hover:shadow-xl transition-all duration-300 group">
                <div class="w-12 h-12 rounded-2xl bg-<?= $c ?>-100 text-<?= $c ?>-600 flex items-center justify-center text-xl mb-5 group-hover:scale-110 transition-transform">
                    <i class="bi bi-<?= e($item['icon']) ?>"></i>
                </div>
                <h3 class="text-lg font-bold <?= $headCls ?> mb-2"><?= $t('title', $item) ?></h3>
                <p class="<?= $bodyCls ?> text-sm leading-relaxed"><?= $tb('body', $item) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
