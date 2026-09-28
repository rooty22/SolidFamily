<!-- Family Charter Section (with the quote card beside it when the quote section directly follows) -->
<section id="<?= e($sec['id']) ?>" class="py-16 sm:py-20 lg:py-28 bg-slate-50 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid <?= $pairedQuote ? 'lg:grid-cols-2' : 'max-w-3xl mx-auto' ?> gap-10 lg:gap-12 items-center">
            <div>
                <span class="text-xs font-extrabold uppercase tracking-widest text-brand-600 mb-2 block"><?= $t('kicker') ?></span>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mb-6 leading-snug">
                    <?= $t('title') ?>
                </h2>
                <p class="text-slate-600 leading-relaxed mb-6 text-sm sm:text-base">
                    <?= $tb('desc') ?>
                </p>

                <div class="space-y-4">
                    <?php foreach ($sec['items'] as $item): ?>
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold flex-shrink-0 mt-1">
                            <i class="bi bi-check2"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-base"><?= $t('title', $item) ?></h4>
                            <p class="text-slate-500 text-sm"><?= $tb('body', $item) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($sec['button_url'] !== '' && $t('button_text') !== ''): ?>
                <div class="mt-8">
                    <a href="<?= e(site_link_url($sec['button_url'])) ?>" class="inline-flex items-center gap-2 text-brand-700 font-extrabold hover:text-brand-800 text-sm">
                        <span><?= $t('button_text') ?></span>
                        <i class="bi bi-arrow-<?= is_rtl() ? 'left' : 'right' ?>"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($pairedQuote): $quoteSec = $pairedQuote; ?>
            <div>
                <?php include __DIR__ . '/_quote-card.php'; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
