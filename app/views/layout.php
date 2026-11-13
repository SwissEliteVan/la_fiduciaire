<?php

declare(strict_types=1);

/** @var string $content Contenu HTML déjà rendu par la vue. */
$privatePage = str_starts_with($view, 'portal-');
$quotePage = request_path() === '/devis';
$showPortal = $privatePage || config('portal.enabled', false) === true;
$pageTitle = $pageTitle ?? content('pages.' . $view . '.title', content('site.name'));
$pageDescription = content('pages.' . $view . '.description', content('site.description'));
?>
<!doctype html>
<html lang="<?= e(locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <?php if ($privatePage || $quotePage): ?>
        <meta name="robots" content="noindex, nofollow">
    <?php else: ?>
        <link rel="canonical" href="<?= e(config('base_url', '') . request_path()) ?>">
    <?php endif; ?>
    <meta name="theme-color" content="#0f3d5a">
    <link rel="icon" type="image/png" href="<?= e(asset('assets/brand/apple-touch-icon.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('assets/brand/apple-touch-icon.png')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/brand.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/vitrine.css')) ?>">
    <?php if ($privatePage): ?>
        <link rel="stylesheet" href="<?= e(asset('assets/css/portal.css')) ?>">
    <?php endif; ?>
</head>
<body class="<?= $privatePage ? 'site--portail' : 'site--vitrine' ?>">
<a class="skip-link" href="#contenu"><?= e(content('ui.skip')) ?></a>

<header class="site-header">
    <div class="site-header__inner">
        <a class="brand" href="/">
            <img class="brand__logo" src="<?= e(asset('assets/brand/la-fiduciaire.png')) ?>"
                 width="512" height="149" alt="<?= e(content('site.name')) ?> — Accueil">
        </a>

        <nav class="site-nav" aria-label="<?= e(content('ui.menu')) ?>">
            <?php foreach ((array) content('nav', []) as $item): ?>
                <a href="<?= e($item['path']) ?>"
                   <?= is_active($item['path']) ? 'aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <?php if ($showPortal): ?>
                <a class="btn btn--secondary btn--header" href="/espace-client"
                   <?= $privatePage ? 'aria-current="true"' : '' ?>>Espace client</a>
            <?php endif; ?>
            <?php if (!$privatePage): ?>
                <a class="btn btn--primary btn--header" href="/devis"><?= e(content('hero.cta')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main id="contenu">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="site-footer__inner">
        <p class="site-footer__brand"><?= e(content('site.name')) ?> — <?= e(content('site.tagline')) ?></p>
        <p class="site-footer__meta">
            <a href="/mentions-legales"><?= e(content('footer.legal')) ?></a>
            <span aria-hidden="true">·</span>
            <?= e(date('Y')) ?> <?= e(content('footer.rights')) ?>
        </p>
    </div>
</footer>

<?php if ($view === 'home'): ?>
    <script src="<?= e(asset('assets/js/hero.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
