<?php if (($sec['button_url'] ?? '') !== '' && $t('button_text') !== ''): ?>
<a href="<?= e(site_link_url($sec['button_url'])) ?>"<?= preg_match('#^https?://#i', $sec['button_url']) ? ' target="_blank" rel="noopener"' : '' ?> class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-500 hover:to-brand-600 text-white font-bold text-xs sm:text-sm shadow-glow transition-all">
    <span><?= $t('button_text') ?></span>
    <i class="bi bi-arrow-<?= is_rtl() ? 'left' : 'right' ?>"></i>
</a>
<?php endif; ?>
