<?php

declare(strict_types=1);
?>
<section class="page-head" aria-labelledby="zones-titre">
    <p class="section-kicker">Zones desservies</p>
    <h1 id="zones-titre"><?= e(content('zones.title')) ?></h1>
    <p class="section__answer"><?= e(content('zones.answer')) ?></p>
    <p><?= e(content('zones.intro')) ?></p>
</section>

<section class="section">
    <p class="notice"><?= e(content('zones.body')) ?></p>
    <p class="section__link"><a class="btn btn--primary" href="/devis"><?= e(content('hero.cta')) ?></a></p>
</section>
