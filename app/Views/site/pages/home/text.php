<?php [$bgCls, $headCls, $bodyCls, $kickCls] = \App\Models\HomePage::bgClasses($sec['bg']); ?>
<!-- Custom Section: Text -->
<section id="<?= e($sec['id']) ?>" class="py-16 sm:py-20 lg:py-24 <?= $bgCls ?>">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <?php if ($t('kicker') !== ''): ?>
            <span class="text-xs font-extrabold uppercase tracking-widest <?= $kickCls ?> mb-2 block"><?= $t('kicker') ?></span>
        <?php endif; ?>
        <?php if ($t('title') !== ''): ?>
            <h2 class="text-3xl sm:text-4xl font-black <?= $headCls ?> mb-5 leading-snug"><?= $t('title') ?></h2>
        <?php endif; ?>
        <div class="<?= $bodyCls ?> text-sm sm:text-base leading-relaxed mb-6"><?= $tb('body') ?></div>
        <?php include __DIR__ . '/_button.php'; ?>
    </div>
</section>
