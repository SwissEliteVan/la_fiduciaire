<?php

declare(strict_types=1);
?>
<section class="page-head">
    <h1><?= e(content('ui.notfound_title')) ?></h1>
    <p><?= e(content('ui.notfound_body')) ?></p>
    <?php if (isset($path) && $path !== ''): ?>
        <p class="notice"><?= e($path) ?></p>
    <?php endif; ?>
</section>

<section class="section">
    <a class="btn btn--primary" href="/"><?= e(content('ui.back_home')) ?></a>
</section>
