<?php

declare(strict_types=1);
$postedEmail = is_string($_POST['email'] ?? null) ? $_POST['email'] : '';
$postedName = is_string($_POST['dossier_nom'] ?? null) ? $_POST['dossier_nom'] : '';
?>
<section class="lf-portal lf-portal--narrow" aria-labelledby="invitation-titre">
    <div class="lf-toolbar">
        <div>
            <p class="lf-kicker">Espace cabinet</p>
            <h1 id="invitation-titre">Inviter un client</h1>
            <p class="lf-muted">Le compte est créé immédiatement. Le code d’activation est affiché une seule fois.</p>
        </div>
        <div class="lf-toolbar__actions">
            <a class="btn btn--secondary" href="/espace-client/demandes">Demandes de devis</a>
            <a class="btn btn--secondary" href="/espace-client">Retour aux dossiers</a>
        </div>
    </div>

    <?php if (is_array($once)): ?>
        <div class="lf-panel lf-code-panel" role="status">
            <h2>Code d’activation à remettre</h2>
            <p class="lf-muted">
                <?= e($once['email']) ?> · Dossier <?= e($once['dossier']) ?>
            </p>
            <p class="lf-code"><code><?= e($once['code']) ?></code></p>
            <p class="lf-muted">Ce code n’est plus affiché après rechargement. Il expire dans 24 heures.
                Transmettez-le par un canal privé : aucun e-mail automatique n’est envoyé.</p>
            <p><a class="btn btn--primary" href="/espace-client/inviter">Inviter un autre client</a></p>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="lf-feedback lf-feedback--error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <div class="lf-panel lf-panel--form">
        <h2>Nouveau compte client</h2>
        <form class="form lf-form" method="post" action="/espace-client/inviter">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <div class="form__row">
                <label for="invite-email">Adresse e-mail du client</label>
                <input id="invite-email" name="email" type="email" required maxlength="180"
                       autocomplete="off" value="<?= e($postedEmail) ?>">
            </div>
            <div class="form__row">
                <label for="invite-dossier">Dossier existant</label>
                <select id="invite-dossier" name="dossier">
                    <option value="">Créer un nouveau dossier</option>
                    <?php foreach ($dossiers as $dossier): ?>
                        <option value="<?= e($dossier['id']) ?>"><?= e($dossier['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form__row">
                <label for="invite-nom">Nom du nouveau dossier</label>
                <input id="invite-nom" name="dossier_nom" type="text" maxlength="160"
                       value="<?= e($postedName) ?>">
                <small class="lf-muted">Utilisé seulement si aucun dossier existant n’est sélectionné.</small>
            </div>
            <button type="submit" class="btn btn--primary">Créer l’accès</button>
        </form>
    </div>
</section>
