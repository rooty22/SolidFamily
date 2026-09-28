<?php
[$bgCls, $headCls, $bodyCls, $kickCls] = \App\Models\HomePage::bgClasses($sec['bg']);
$imgUrl = \App\Models\HomePage::imageUrl($sec['image']);
?>
<!-- Custom Section: Image + Text -->
<section id="<?= e($sec['id']) ?>" class="py-16 sm:py-20 lg:py-24 <?= $bgCls ?>">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid <?= $imgUrl ? 'lg:grid-cols-2' : 'max-w-3xl mx-auto' ?> gap-10 lg:gap-14 items-center">
            <?php if ($imgUrl): ?>
            <div class="<?= $sec['image_position'] === 'end' ? 'lg:order-2' : '' ?>">
                <img src="<?= e($imgUrl) ?>" alt="<?= $t('title') ?>" loading="lazy" class="w-full h-auto max-h-[460px] object-cover rounded-3xl shadow-xl border border-slate-200/60">
            </div>
            <?php endif; ?>
            <div>
                <?php if ($t('kicker') !== ''): ?>
                    <span class="text-xs font-extrabold uppercase tracking-widest <?= $kickCls ?> mb-2 block"><?= $t('kicker') ?></span>
                <?php endif; ?>
                <?php if ($t('title') !== ''): ?>
                    <h2 class="text-3xl sm:text-4xl font-black <?= $headCls ?> mb-5 leading-snug"><?= $t('title') ?></h2>
                <?php endif; ?>
                <div class="<?= $bodyCls ?> text-sm sm:text-base leading-relaxed mb-6"><?= $tb('body') ?></div>
                <?php include __DIR__ . '/_button.php'; ?>
            </div>
        </div>
    </div>
</section>
