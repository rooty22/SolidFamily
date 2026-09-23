<?php
$isEn = is_en();
$metaTitle = isset($pageTitle) ? e($pageTitle) . ' - ' . e(site_name()) : e(site_setting($isEn ? 'seo_meta_title_en' : 'seo_meta_title', site_name()));
$metaDesc = e(site_setting($isEn ? 'seo_meta_description_en' : 'seo_meta_description', 'صندوق عائلي تكافلي'));
$metaKeywords = e(site_setting($isEn ? 'seo_meta_keywords_en' : 'seo_meta_keywords', ''));
$canonicalUrl = site_setting('seo_canonical_url') ?: url(current_path());
$ogImage = site_setting('seo_og_image') ?: site_logo_url();
$gaId = site_setting('seo_google_analytics');
$customHeaderScripts = site_setting('seo_custom_header_scripts');
$customFooterScripts = site_setting('seo_custom_footer_scripts');
?>
<!DOCTYPE html>
<html lang="<?= current_locale() ?>" dir="<?= dir_attr() ?>" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $metaTitle ?></title>

    <!-- Favicon -->
    <?php if ($__faviconUrl = site_favicon_url()): ?>
        <link rel="icon" type="<?= site_favicon_mime() ?>" href="<?= e($__faviconUrl) ?>">
        <link rel="shortcut icon" href="<?= e($__faviconUrl) ?>">
    <?php endif; ?>

    <?php if ($metaDesc): ?><meta name="description" content="<?= $metaDesc ?>"><?php endif; ?>
    <?php if ($metaKeywords): ?><meta name="keywords" content="<?= $metaKeywords ?>"><?php endif; ?>
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">

    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:title" content="<?= $metaTitle ?>">
    <?php if ($metaDesc): ?><meta property="og:description" content="<?= $metaDesc ?>"><?php endif; ?>
    <?php if ($ogImage): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
    <meta property="og:site_name" content="<?= e(site_name()) ?>">
    <meta property="og:locale" content="<?= $isEn ? 'en_US' : 'ar_SA' ?>">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $metaTitle ?>">
    <?php if ($metaDesc): ?><meta name="twitter:description" content="<?= $metaDesc ?>"><?php endif; ?>
    <?php if ($ogImage): ?><meta name="twitter:image" content="<?= e($ogImage) ?>"><?php endif; ?>

    <!-- Google Analytics (GA4) -->
    <?php if (!empty($gaId)): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?= e($gaId) ?>');
        </script>
    <?php endif; ?>

    <!-- Custom Injected Head Scripts -->
    <?php if (!empty($customHeaderScripts)): ?>
        <?= $customHeaderScripts ?>
    <?php endif; ?>

    <!-- Google Fonts: Cairo (Arabic) & Plus Jakarta Sans (English / Numeric) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 (RTL or LTR based on language) -->
    <?php if (is_rtl()): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <?php endif; ?>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Tailwind CSS with custom palette -->
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
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
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
                            700: '#b45309',
                        },
                        navy: {
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#060913',
                        }
                    },
                    boxShadow: {
                        'glow': '0 0 35px -5px rgba(16, 185, 129, 0.3)',
                        'gold-glow': '0 0 35px -5px rgba(245, 158, 11, 0.25)',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js & SweetAlert2 -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="<?= asset('site/css/site.css') ?>">
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-brand-500 selection:text-white flex flex-col min-h-screen">

    <?= $content ?>

    <script src="<?= asset('site/js/site.js') ?>"></script>
    <?php if (!empty($customFooterScripts)): ?>
        <?= $customFooterScripts ?>
    <?php endif; ?>
    <?= \App\LiveTranslate\LiveTranslate::scripts() ?>
</body>
</html>
