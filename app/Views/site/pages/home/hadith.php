<!-- Quote Section (on its own; beside the charter it is rendered by charter.php) -->
<section id="<?= e($sec['id']) ?>" class="py-16 sm:py-20 bg-slate-50 border-b border-slate-200">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php $quoteSec = $sec; include __DIR__ . '/_quote-card.php'; ?>
    </div>
</section>
