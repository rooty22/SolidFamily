<!DOCTYPE html>
<html lang="<?= current_locale() ?>" dir="<?= dir_attr() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?><?= __('brand_name') ?></title>

    <!-- Favicon -->
    <?php $__favicon = \App\Models\Setting::get('site_favicon'); ?>
    <?php if ($__favicon): ?>
        <?php $__faviconUrl = (str_starts_with($__favicon, 'http') ? $__favicon : asset($__favicon)); ?>
        <?php $__faviconExt = strtolower(pathinfo($__favicon, PATHINFO_EXTENSION)); ?>
        <?php $__faviconMime = ($__faviconExt === 'svg' ? 'image/svg+xml' : ($__faviconExt === 'ico' ? 'image/x-icon' : 'image/png')); ?>
        <link rel="icon" type="<?= $__faviconMime ?>" href="<?= e($__faviconUrl) ?>">
        <link rel="shortcut icon" href="<?= e($__faviconUrl) ?>">
    <?php endif; ?>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 RTL / LTR dynamically -->
    <?php if (is_rtl()): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <?php endif; ?>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Tailwind CSS with custom Islamic Emerald & Gold brand tokens -->
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

    <!-- Custom Modern Site Styles -->
    <link rel="stylesheet" href="<?= asset('site/css/site.css') ?>">
</head>
<body class="bg-slate-50 antialiased">
<div class="app-shell min-h-screen flex">
    <?php \App\Core\View::partial('site/partials/sidebar'); ?>
    <div class="main-area flex-1 flex flex-col min-w-0">
        <?php \App\Core\View::partial('site/partials/topbar', ['pageTitle' => $pageTitle ?? '']); ?>
        <main class="page-content flex-1 p-4 sm:p-6 lg:p-8">
            <?php \App\Core\View::partial('site/partials/flash'); ?>
            <?= $content ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('site/js/site.js') ?>"></script>
</body>
</html>
