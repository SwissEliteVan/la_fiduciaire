<?php
declare(strict_types=1);
$activate = $mode === 'activate';
$activated = !$activate && !empty($_SESSION['activated']);
if ($activated) {
    unset($_SESSION['activated']);
}
?>
<section class="lf-portal lf-portal--narrow" aria-labelledby="acces-titre">
    <div class="lf-panel">
        <p class="lf-kicker">La Fiduciaire · Espace client</p>
        <h1 id="acces-titre"><?= $activate ? 'Configurer mon accès' : 'Vos pièces, au même endroit' ?></h1>
        <p class="lf-muted"><?= $activate
            ? 'Utilisez le code transmis par votre interlocuteur pour créer ou remplacer votre mot de passe.'
            : 'Connectez-vous pour déposer vos justificatifs comptables et suivre leur traitement.' ?></p>

        <?php if ($activated): ?>
            <p class="lf-feedback lf-feedback--ok" role="status">Mot de passe enregistré. Vous pouvez vous connecter.</p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="lf-feedback lf-feedback--error" id="acces-erreur" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <form class="form lf-form" method="post" action="/espace-client/<?= $activate ? 'activation' : 'connexion' ?>"
              <?= $error ? 'aria-describedby="acces-erreur"' : '' ?>>
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <?php if ($activate): ?>
                <div class="form__row">
                    <label for="code">Code d’accès</label>
                    <input id="code" name="code" required maxlength="64" minlength="64" autocomplete="off" autocapitalize="none" spellcheck="false" value="<?= e($code ?? '') ?>">
                </div>
            <?php else: ?>
                <div class="form__row">
                    <label for="email">Adresse e-mail</label>
                    <input id="email" name="email" type="email" required maxlength="180" autocomplete="username" value="<?= e($email) ?>">
                </div>
            <?php endif; ?>
            <div class="form__row">
                <label for="password"><?= $activate ? 'Nouveau mot de passe' : 'Mot de passe' ?></label>
                <input id="password" name="password" type="password" required maxlength="72"
                       autocomplete="<?= $activate ? 'new-password' : 'current-password' ?>"
                       <?= $activate ? 'minlength="12" aria-describedby="password-aide"' : '' ?>>
                <?php if ($activate): ?><small id="password-aide">Au moins 12 caractères, dans la limite de 72 octets.</small><?php endif; ?>
            </div>
            <?php if ($activate): ?>
                <div class="form__row">
                    <label for="repeat">Confirmer le mot de passe</label>
                    <input id="repeat" name="repeat" type="password" minlength="12" maxlength="72" required autocomplete="new-password">
                </div>
            <?php endif; ?>
            <button type="submit" class="btn btn--primary"><?= $activate ? 'Enregistrer mon mot de passe' : 'Me connecter' ?></button>
        </form>

        <?php if ($activate): ?>
            <p class="lf-help"><a href="/espace-client/connexion">Retour à la connexion</a></p>
        <?php else: ?>
            <p class="lf-help"><a href="/espace-client/activation">J’ai un code d’accès</a></p>
            <p class="lf-muted">Mot de passe oublié ou pas encore d’accès ? Demandez un code à votre interlocuteur.</p>
        <?php endif; ?>
    </div>
</section>
