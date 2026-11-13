<?php

declare(strict_types=1);

/** Section hero : vidéo locale réelle (WebM + MP4 + poster), pause accessible. */
?>
<section class="hero" aria-labelledby="hero-titre">
    <video class="hero__video"
           data-hero-video
           poster="<?= e(asset('hero-video-poster.jpg')) ?>"
           width="1920"
           height="1080"
           muted
           loop
           playsinline
           preload="metadata"
           tabindex="-1"
           aria-hidden="true">
        <source src="<?= e(asset('hero-video.webm')) ?>" type="video/webm">
        <source src="<?= e(asset('hero-video.mp4')) ?>" type="video/mp4">
    </video>

    <div class="hero__overlay" aria-hidden="true"></div>

    <div class="hero__content">
        <p class="hero__eyebrow"><?= e(content('site.name')) ?> · <?= e(content('site.tagline')) ?></p>
        <h1 id="hero-titre" class="hero__title"><?= e(content('hero.title')) ?></h1>
        <p class="hero__subtitle"><?= e(content('hero.subtitle')) ?></p>

        <div class="hero__actions">
            <a class="btn btn--primary" href="/devis"><?= e(content('hero.cta')) ?></a>
            <a class="btn btn--ghost" href="/prestations"><?= e(content('hero.cta_alt')) ?></a>
        </div>

        <button class="hero__toggle" type="button" data-hero-toggle
                data-label-pause="<?= e(content('hero.video_pause')) ?>"
                data-label-play="<?= e(content('hero.video_play')) ?>"
                aria-pressed="false" hidden>
            <span data-hero-toggle-label><?= e(content('hero.video_pause')) ?></span>
        </button>
        <p class="visually-hidden"><?= e(content('hero.video_label')) ?></p>
    </div>
</section>

<section class="section" aria-labelledby="prestations-titre">
    <div class="section__head">
        <h2 id="prestations-titre"><?= e(content('home.prestations_title')) ?></h2>
        <p><?= e(content('home.prestations_subtitle')) ?></p>
    </div>

    <ul class="cards">
        <?php foreach ((array) content('prestations.items', []) as $item): ?>
            <li class="card">
                <h3 class="card__title"><?= e($item['nom']) ?></h3>
                <p class="card__text"><?= e($item['texte']) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>

    <p class="section__link"><a class="link" href="/prestations"><?= e(content('hero.cta_alt')) ?></a></p>
</section>

<section class="section section--muted" aria-labelledby="confiance-titre">
    <div class="section__head">
        <h2 id="confiance-titre"><?= e(content('home.trust_title')) ?></h2>
    </div>
    <ul class="trust">
        <?php foreach ((array) content('home.trust_items', []) as $item): ?>
            <li><?= e($item) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="section" aria-labelledby="pourquoi-titre">
    <div class="section__head">
        <h2 id="pourquoi-titre"><?= e(content('home_why.title')) ?></h2>
    </div>

    <ul class="cards">
        <?php foreach ((array) content('home_why.items', []) as $argument): ?>
            <li class="card">
                <h3 class="card__title"><?= e($argument['titre']) ?></h3>
                <p class="card__text"><?= e($argument['texte']) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<?php if (config('portal.enabled', false) === true): ?>
    <section class="section section--muted" aria-labelledby="espace-titre">
        <div class="section__head">
            <h2 id="espace-titre">Vos pièces comptables, au même endroit</h2>
            <p>Vous êtes déjà client ? Connectez-vous pour transmettre vos factures, relevés et justificatifs, puis suivre leur traitement.</p>
            <a class="btn btn--secondary" href="/espace-client">Accéder à mon espace</a>
        </div>
    </section>
<?php endif; ?>

<section class="cta-band" aria-labelledby="cta-titre">
    <div class="cta-band__inner">
        <h2 id="cta-titre"><?= e(content('home.cta_title')) ?></h2>
        <p><?= e(content('home.cta_text')) ?></p>
        <a class="btn btn--primary" href="/devis"><?= e(content('hero.cta')) ?></a>
    </div>
</section>
