<?php

declare(strict_types=1);
?>
<section class="page-head" aria-labelledby="tarifs-titre">
    <p class="section-kicker">Honoraires</p>
    <h1 id="tarifs-titre"><?= e(content('tarifs.title')) ?></h1>
    <p class="section__answer"><?= e(content('tarifs.answer')) ?></p>
    <p><?= e(content('tarifs.intro')) ?></p>
</section>

<section class="section">
    <p class="notice"><?= e(content('tarifs.body')) ?></p>
    <p class="section__link"><a class="btn btn--primary" href="/devis"><?= e(content('tarifs.cta')) ?></a></p>
</section>
