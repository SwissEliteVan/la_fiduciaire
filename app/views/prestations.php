<?php

declare(strict_types=1);
?>
<section class="page-head" aria-labelledby="prestations-titre">
    <p class="section-kicker">Prestations</p>
    <h1 id="prestations-titre"><?= e(content('prestations.title')) ?></h1>
    <p class="section__answer"><?= e(content('prestations.answer')) ?></p>
    <p><?= e(content('prestations.intro')) ?></p>
</section>

<section class="section">
    <ul class="register">
        <?php foreach ((array) content('prestations.items', []) as $index => $item): ?>
            <li class="register__row">
                <span class="register__index" aria-hidden="true"><?= e(sprintf('%02d', $index + 1)) ?></span>
                <h2 class="register__title"><?= e($item['nom']) ?></h2>
                <p class="register__text"><?= e($item['texte']) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>

    <p class="section__link"><a class="btn btn--primary" href="/devis"><?= e(content('hero.cta')) ?></a></p>
</section>
