<?php $socialLinks = site_social_links(); ?>
<?php if ($socialLinks): ?>
<!-- Social Media Icons (managed from Settings > Contact & Socials) -->
<div class="flex flex-wrap items-center gap-2.5">
    <?php foreach ($socialLinks as $social): ?>
        <a href="<?= e($social['url']) ?>" target="_blank" rel="noopener" class="w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 <?= $social['icon'] === 'whatsapp' ? 'text-emerald-400' : 'text-slate-300' ?> hover:text-white hover:border-slate-700 flex items-center justify-center text-xs transition-colors" title="<?= e($social['label']) ?>" aria-label="<?= e($social['label'] ?: $social['icon']) ?>">
            <i class="bi bi-<?= e($social['icon']) ?>"></i>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
