<?php

declare(strict_types=1);
$quotes = is_array($quotes ?? null) ? $quotes : [];
$text = static fn (array $row, string $key): string => is_scalar($row[$key] ?? null) ? (string) $row[$key] : '';
?>
<section class="lf-portal" aria-labelledby="demandes-titre">
    <div class="lf-toolbar">
        <div>
            <p class="lf-kicker">Espace cabinet</p>
            <h1 id="demandes-titre">Demandes de devis</h1>
            <p class="lf-muted">Demandes enregistrées, les plus récentes en premier. Aucun e-mail automatique n’est envoyé.</p>
        </div>
        <div class="lf-toolbar__actions">
            <a class="btn btn--secondary" href="/espace-client">Retour aux dossiers</a>
            <form method="post" action="/espace-client/deconnexion">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <button type="submit" class="btn btn--secondary">Me déconnecter</button>
            </form>
        </div>
    </div>

    <?php if (!$quotes): ?>
        <p class="lf-empty">Aucune demande de devis enregistrée pour l’instant.</p>
    <?php else: ?>
        <ul class="lf-list lf-quotes">
            <?php foreach ($quotes as $quote): ?>
                <?php
                if (!is_array($quote)) {
                    continue;
                }
                $date = $text($quote, 'recu_le') !== '' ? $text($quote, 'recu_le') : $text($quote, 'cree_le');
                $email = $text($quote, 'email');
                ?>
                <li class="lf-quote">
                    <div class="lf-quote__head">
                        <strong><?= e($text($quote, 'nom')) ?></strong>
                        <span class="lf-muted"><?= e($date) ?></span>
                    </div>
                    <p class="lf-quote__meta">
                        <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
                        <?php if ($text($quote, 'telephone') !== ''): ?>
                            · <?= e($text($quote, 'telephone')) ?>
                        <?php endif; ?>
                        <?php if ($text($quote, 'activite') !== ''): ?>
                            · <?= e($text($quote, 'activite')) ?>
                        <?php endif; ?>
                        <?php if ($text($quote, 'canton') !== ''): ?>
                            · <?= e($text($quote, 'canton')) ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($text($quote, 'besoin') !== ''): ?>
                        <p class="lf-quote__need">Besoin : <?= e($text($quote, 'besoin')) ?></p>
                    <?php endif; ?>
                    <p class="lf-quote__message"><?= e($text($quote, 'message')) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
