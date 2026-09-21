<!DOCTYPE html>
<html lang="<?= current_locale() ?>" dir="<?= dir_attr() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?><?= e(site_name()) ?></title>

    <!-- Favicon -->
    <?php $__favicon = \App\Models\Setting::get('site_favicon'); ?>
    <?php if ($__favicon): ?>
        <?php $__faviconUrl = (str_starts_with($__favicon, 'http') ? $__favicon : asset($__favicon)); ?>
        <?php $__faviconExt = strtolower(pathinfo($__favicon, PATHINFO_EXTENSION)); ?>
        <?php $__faviconMime = ($__faviconExt === 'svg' ? 'image/svg+xml' : ($__faviconExt === 'ico' ? 'image/x-icon' : 'image/png')); ?>
        <link rel="icon" type="<?= $__faviconMime ?>" href="<?= e($__faviconUrl) ?>">
        <link rel="shortcut icon" href="<?= e($__faviconUrl) ?>">
    <?php endif; ?>
    

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 RTL / LTR dynamically -->
    <?php if (is_rtl()): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <?php endif; ?>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Tailwind CSS with Islamic Emerald & Gold brand tokens -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        cairo: ['Cairo', 'sans-serif'],
                        numeric: ['Plus Jakarta Sans', 'Cairo', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#064e3b',
                            900: '#022c22',
                        },
                        gold: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js & SweetAlert2 -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom Modern Site Styling -->
    <link rel="stylesheet" href="<?= asset('site/css/site.css') ?>">
</head>
<body class="bg-slate-900 antialiased">

<div class="auth-wrapper relative min-h-screen flex items-center justify-center p-4">
    <!-- Language toggle in top corner (hidden if single-language mode is active) -->
    <?php if (!is_single_language()): ?>
        <div class="absolute top-6 <?= is_rtl() ? 'left-6' : 'right-6' ?> z-20">
            <a href="<?= url('lang/' . (current_locale() === 'ar' ? 'en' : 'ar')) ?>" 
               class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur-md text-white border border-white/20 text-xs font-bold transition-all shadow-sm">
                <i class="bi bi-translate text-brand-400"></i>
                <span><?= current_locale() === 'ar' ? 'English' : 'العربية' ?></span>
            </a>
        </div>
    <?php endif; ?>

    <?= $content ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('site/js/site.js') ?>"></script>
</body>
</html>
