<?php

declare(strict_types=1);

/** @var string $content Contenu HTML déjà rendu par la vue. */
$privatePage = str_starts_with($view, 'portal-');
$quotePage = request_path() === '/devis';
$showPortal = $privatePage || lf_portal_enabled();
$pageTitle = $pageTitle ?? content('pages.' . $view . '.title', content('site.name'));
$pageDescription = content('pages.' . $view . '.description', content('site.description'));
$baseUrl = rtrim((string) config('base_url', ''), '/');
$canonical = $baseUrl . request_path();

/* Coordonnées publiques : rendues seulement lorsqu'elles sont validées.
   Les valeurs encore marquées « [À CONFIRMER] » ne doivent jamais s'afficher. */
$contactItems = [];
foreach ((array) content('home_contact.items', []) as $item) {
    $value = trim((string) ($item['value'] ?? ''));
    if ($value !== '' && !str_starts_with($value, '[')) {
        $contactItems[] = ['label' => (string) ($item['label'] ?? ''), 'value' => $value];
    }
}

/* Données structurées : uniquement des faits présents dans le contenu validé.
   Aucune adresse, aucun numéro, aucun prix inventé. */
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'AccountingService',
    'name' => content('site.name'),
    'description' => content('site.description'),
    'areaServed' => ['@type' => 'AdministrativeArea', 'name' => 'Suisse romande'],
    'availableLanguage' => ['fr'],
    'knowsAbout' => array_values(array_filter(array_column((array) content('prestations.items', []), 'nom'))),
];
if ($baseUrl !== '') {
    $schema['url'] = $baseUrl . '/';
}
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
        <link rel="canonical" href="<?= e($canonical) ?>">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="<?= e(content('site.name')) ?>">
        <meta property="og:locale" content="fr_CH">
        <meta property="og:title" content="<?= e($pageTitle) ?>">
        <meta property="og:description" content="<?= e($pageDescription) ?>">
        <?php if ($baseUrl !== ''): ?>
            <meta property="og:url" content="<?= e($canonical) ?>">
        <?php endif; ?>
        <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
    <?php endif; ?>
    <meta name="theme-color" content="#0f3d5a">
    <link rel="icon" type="image/png" href="<?= e(asset('assets/brand/apple-touch-icon.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('assets/brand/apple-touch-icon.png')) ?>">
    <?php if ($view === 'home'): ?>
        <link rel="preload" as="image" href="<?= e(asset('hero-video-poster.jpg')) ?>" fetchpriority="high">
    <?php endif; ?>
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
                   <?= $privatePage ? 'aria-current="page"' : '' ?>>Espace client</a>
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
        <div class="site-footer__block">
            <p class="site-footer__name"><?= e(content('site.name')) ?></p>
            <p class="site-footer__tagline"><?= e(content('site.tagline')) ?></p>
        </div>

        <nav aria-label="<?= e(content('ui.footer_nav')) ?>">
            <ul class="site-footer__nav">
                <?php foreach ((array) content('nav', []) as $item): ?>
                    <li><a href="<?= e($item['path']) ?>"><?= e($item['label']) ?></a></li>
                <?php endforeach; ?>
                <li><a href="/mentions-legales"><?= e(content('footer.legal')) ?></a></li>
                <?php if ($showPortal): ?>
                    <li><a href="/espace-client">Espace client</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <?php if ($contactItems): ?>
            <dl class="contact-list contact-list--footer">
                <?php foreach ($contactItems as $item): ?>
                    <div class="contact-list__item">
                        <dt><?= e($item['label']) ?></dt>
                        <dd><?= e($item['value']) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>

        <?php if (!$privatePage): ?>
            <a class="btn btn--primary site-footer__cta" href="/devis"><?= e(content('hero.cta')) ?></a>
        <?php endif; ?>

        <p class="site-footer__meta"><?= e(date('Y')) ?> <?= e(content('footer.rights')) ?></p>
    </div>
</footer>

<?php if ($view === 'home'): ?>
    <script src="<?= e(asset('assets/js/hero.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
