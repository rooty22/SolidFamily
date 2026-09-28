<?php
[$bgCls, , $bodyCls] = \App\Models\HomePage::bgClasses($sec['bg']);
$imgUrl = \App\Models\HomePage::imageUrl($sec['image']);
$link = $sec['button_url'] ?? '';
?>
<?php if ($imgUrl): ?>
<!-- Custom Section: Full-width Image -->
<section id="<?= e($sec['id']) ?>" class="py-10 sm:py-14 <?= $bgCls ?>">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <figure>
            <?php if ($link !== ''): ?><a href="<?= e(site_link_url($link)) ?>"<?= preg_match('#^https?://#i', $link) ? ' target="_blank" rel="noopener"' : '' ?> class="block"><?php endif; ?>
                <img src="<?= e($imgUrl) ?>" alt="<?= $t('caption') ?>" loading="lazy" class="w-full h-auto rounded-3xl shadow-xl object-cover">
            <?php if ($link !== ''): ?></a><?php endif; ?>
            <?php if ($t('caption') !== ''): ?>
                <figcaption class="text-center text-sm <?= $bodyCls ?> mt-4"><?= $t('caption') ?></figcaption>
            <?php endif; ?>
        </figure>
    </div>
</section>
<?php endif; ?>
