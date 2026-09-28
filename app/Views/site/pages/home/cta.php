<!-- Call To Action Section -->
<section id="<?= e($sec['id']) ?>" class="py-14 bg-gradient-to-r from-slate-950 via-brand-950 to-slate-950 text-white border-t border-slate-800 relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-start">
        <div>
            <?php if ($t('badge') !== ''): ?>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-brand-500/20 text-brand-300 border border-brand-500/30 mb-2 inline-block">
                <?= $t('badge') ?>
            </span>
            <?php endif; ?>
            <h3 class="text-2xl sm:text-3xl font-black text-white mb-2">
                <?= $t('title') ?>
            </h3>
            <p class="text-xs sm:text-sm text-slate-300 max-w-xl">
                <?= $tb('desc') ?>
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <a href="<?= url('register') ?>" class="px-6 py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-500 hover:to-brand-600 text-white font-bold text-xs sm:text-sm shadow-glow transition-all flex items-center gap-2">
                <i class="bi bi-person-plus-fill"></i>
                <span><?= __('join_now') ?></span>
            </a>
            <a href="<?= url('page/contact') ?>" class="px-5 py-3.5 rounded-xl bg-white/10 hover:bg-white/15 text-slate-200 hover:text-white font-bold text-xs sm:text-sm transition-colors flex items-center gap-2">
                <i class="bi bi-chat-dots-fill text-gold-400"></i>
                <span><?= __('contact_us') ?></span>
            </a>
        </div>
    </div>
</section>
