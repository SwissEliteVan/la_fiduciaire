<?php

declare(strict_types=1);
?>
<section class="page-head">
    <h1><?= e(content('tarifs.title')) ?></h1>
    <p><?= e(content('tarifs.intro')) ?></p>
</section>

<section class="section">
    <p class="notice"><?= e(content('tarifs.body')) ?></p>
    <p class="section__link"><a class="btn btn--primary" href="/devis"><?= e(content('tarifs.cta')) ?></a></p>
</section>
