<?php

declare(strict_types=1);

/** @var string $content Contenu HTML déjà rendu par la vue. */
$pageTitle = content('pages.' . $view . '.title', content('site.name'));
$pageDescription = content('pages.' . $view . '.description', content('site.description'));
?>
<!doctype html>
<html lang="<?= e(locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <?php if ($view === 'devis'): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    <link rel="canonical" href="<?= e(config('base_url', '') . request_path()) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body>
<a class="skip-link" href="#contenu"><?= e(content('ui.skip')) ?></a>

<header class="site-header">
    <a class="brand" href="/">
        <span class="brand__mark" aria-hidden="true">LF</span>
        <span class="brand__name"><?= e(content('site.name')) ?></span>
    </a>

    <nav class="site-nav" aria-label="<?= e(content('ui.menu')) ?>">
        <?php foreach ((array) content('nav', []) as $item): ?>
            <a href="<?= e($item['path']) ?>"
               <?= is_active($item['path']) ? 'aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
        <?php endforeach; ?>
    </nav>

    <a class="btn btn--primary btn--header" href="/devis"><?= e(content('hero.cta')) ?></a>
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

<script src="<?= e(asset('assets/js/hero.js')) ?>" defer></script>
</body>
</html>
