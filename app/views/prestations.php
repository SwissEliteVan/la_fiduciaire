<?php

declare(strict_types=1);
?>
<section class="page-head">
    <h1><?= e(content('prestations.title')) ?></h1>
    <p><?= e(content('prestations.intro')) ?></p>
</section>

<section class="section">
    <ul class="cards cards--wide">
        <?php foreach ((array) content('prestations.items', []) as $item): ?>
            <li class="card">
                <h2 class="card__title"><?= e($item['nom']) ?></h2>
                <p class="card__text"><?= e($item['texte']) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>

    <p class="section__link"><a class="btn btn--primary" href="/devis"><?= e(content('hero.cta')) ?></a></p>
</section>
