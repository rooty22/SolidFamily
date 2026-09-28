<?php $qt = fn(string $f): string => e(\App\Models\HomePage::t($quoteSec, $f)); ?>
<!-- Visual Quote Card -->
<div class="bg-gradient-to-tr from-brand-900 via-slate-900 to-slate-950 rounded-3xl p-8 sm:p-12 text-white shadow-2xl border border-slate-700">
    <div class="flex items-center gap-4 mb-6">
        <div class="w-14 h-14 rounded-2xl bg-brand-500/20 border border-brand-500/40 flex items-center justify-center text-2xl text-brand-400">
            <i class="bi bi-quote"></i>
        </div>
        <div>
            <h3 class="font-black text-lg sm:text-xl text-white"><?= $qt('title') ?></h3>
            <p class="text-xs text-brand-300"><?= e($siteName) ?></p>
        </div>
    </div>

    <blockquote class="text-base sm:text-lg font-medium leading-relaxed mb-6 text-slate-200">
        <?= nl2br($qt('quote')) ?>
    </blockquote>

    <div class="pt-5 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
        <span><?= $qt('source') ?></span>
        <span class="text-gold-400 font-bold"><?= e($siteName) ?></span>
    </div>
</div>
