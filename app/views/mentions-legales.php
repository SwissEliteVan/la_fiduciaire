<?php

declare(strict_types=1);
?>
<section class="page-head" aria-labelledby="mentions-titre">
    <p class="section-kicker">Informations légales</p>
    <h1 id="mentions-titre"><?= e(content('mentions.title')) ?></h1>
</section>

<section class="section">
    <p class="notice"><?= e(content('mentions.body')) ?></p>

    <h2><?= e(content('mentions.privacy_title')) ?></h2>
    <p><?= e(content('mentions.privacy_body')) ?></p>
    <p><?= e(content('mentions.privacy_pending')) ?></p>
</section>
