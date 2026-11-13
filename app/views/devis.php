<?php

declare(strict_types=1);

$fields = (array) content('devis.fields', []);
$errors = isset($errors) && is_array($errors) ? $errors : [];
$old = isset($old) && is_array($old) ? $old : [];
$flash = devis_take_flash();
$success = is_array($flash) && ($flash['succes'] ?? false) === true;

$oldValue = static fn (string $key): string => is_string($old[$key] ?? null) ? (string) $old[$key] : '';
$fieldError = static function (string $key) use ($errors): ?string {
    if (!isset($errors[$key])) {
        return null;
    }

    $message = content('devis.errors.' . $key);

    return is_string($message) ? $message : content('devis.errors.generic');
};
$globalCode = isset($errors['global']) ? (string) $errors['global'] : '';
$globalMessage = $globalCode !== '' ? content('devis.errors.global.' . $globalCode) : null;

$hasFieldErrors = false;
foreach (['nom', 'email', 'telephone', 'activite', 'canton', 'besoin', 'message', 'consentement'] as $key) {
    if (isset($errors[$key])) {
        $hasFieldErrors = true;
        break;
    }
}

$alertMessage = is_string($globalMessage)
    ? $globalMessage
    : ($hasFieldErrors ? content('devis.errors.intro') : null);
?>
<section class="page-head">
    <h1><?= e(content('devis.title')) ?></h1>
    <p><?= e(content('devis.intro')) ?></p>
</section>

<section class="section">
<?php if ($success): ?>
    <div class="alert alert--success" role="status">
        <h2><?= e(content('devis.success.title')) ?></h2>
        <p><?= e(content('devis.success.body')) ?></p>
    </div>
    <p class="section__link"><a class="btn btn--primary" href="/"><?= e(content('ui.back_home')) ?></a></p>
<?php else: ?>
    <p class="notice"><?= e(content('devis.note')) ?></p>

    <?php if (is_string($alertMessage)): ?>
        <div class="alert alert--error" role="alert">
            <p><?= e($alertMessage) ?></p>
        </div>
    <?php endif; ?>

    <form class="form" method="post" action="/devis" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="hp" aria-hidden="true">
            <label for="site_web">Site web</label>
            <input id="site_web" name="site_web" type="text" tabindex="-1" autocomplete="off">
        </div>

        <?php $error = $fieldError('nom'); ?>
        <div class="form__row">
            <label for="nom"><?= e($fields['nom'] ?? 'Nom') ?><span aria-hidden="true"> *</span></label>
            <input id="nom" name="nom" type="text" autocomplete="name" required maxlength="120"
                   value="<?= e($oldValue('nom')) ?>"<?= $error !== null ? ' aria-invalid="true" aria-describedby="nom-error"' : '' ?>>
            <?php if ($error !== null): ?><p class="form__error" id="nom-error"><?= e($error) ?></p><?php endif; ?>
        </div>

        <?php $error = $fieldError('email'); ?>
        <div class="form__row">
            <label for="email"><?= e($fields['email'] ?? 'E-mail') ?><span aria-hidden="true"> *</span></label>
            <input id="email" name="email" type="email" autocomplete="email" required maxlength="180"
                   value="<?= e($oldValue('email')) ?>"<?= $error !== null ? ' aria-invalid="true" aria-describedby="email-error"' : '' ?>>
            <?php if ($error !== null): ?><p class="form__error" id="email-error"><?= e($error) ?></p><?php endif; ?>
        </div>

        <?php $error = $fieldError('telephone'); ?>
        <div class="form__row">
            <label for="telephone"><?= e($fields['telephone'] ?? 'Téléphone') ?></label>
            <input id="telephone" name="telephone" type="tel" autocomplete="tel" maxlength="40"
                   value="<?= e($oldValue('telephone')) ?>"<?= $error !== null ? ' aria-invalid="true" aria-describedby="telephone-error"' : '' ?>>
            <?php if ($error !== null): ?><p class="form__error" id="telephone-error"><?= e($error) ?></p><?php endif; ?>
        </div>

        <?php $error = $fieldError('activite'); ?>
        <div class="form__row">
            <label for="activite"><?= e($fields['activite'] ?? 'Activité') ?></label>
            <input id="activite" name="activite" type="text" maxlength="160"
                   value="<?= e($oldValue('activite')) ?>"<?= $error !== null ? ' aria-invalid="true" aria-describedby="activite-error"' : '' ?>>
            <?php if ($error !== null): ?><p class="form__error" id="activite-error"><?= e($error) ?></p><?php endif; ?>
        </div>

        <?php $error = $fieldError('canton'); ?>
        <div class="form__row">
            <label for="canton"><?= e($fields['canton'] ?? 'Canton') ?></label>
            <input id="canton" name="canton" type="text" maxlength="60"
                   value="<?= e($oldValue('canton')) ?>"<?= $error !== null ? ' aria-invalid="true" aria-describedby="canton-error"' : '' ?>>
            <?php if ($error !== null): ?><p class="form__error" id="canton-error"><?= e($error) ?></p><?php endif; ?>
        </div>

        <?php $error = $fieldError('besoin'); ?>
        <div class="form__row">
            <label for="besoin"><?= e($fields['besoin'] ?? 'Besoin') ?><span aria-hidden="true"> *</span></label>
            <select id="besoin" name="besoin"<?= $error !== null ? ' aria-invalid="true" aria-describedby="besoin-error"' : '' ?>>
                <?php foreach ((array) content('devis.besoins', []) as $besoin): ?>
                    <option value="<?= e($besoin) ?>"<?= $oldValue('besoin') === $besoin ? ' selected' : '' ?>><?= e($besoin) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($error !== null): ?><p class="form__error" id="besoin-error"><?= e($error) ?></p><?php endif; ?>
        </div>

        <?php $error = $fieldError('message'); ?>
        <div class="form__row">
            <label for="message"><?= e($fields['message'] ?? 'Message') ?><span aria-hidden="true"> *</span></label>
            <textarea id="message" name="message" rows="6" maxlength="4000" required
                      <?= $error !== null ? 'aria-invalid="true" aria-describedby="message-error"' : '' ?>><?= e($oldValue('message')) ?></textarea>
            <?php if ($error !== null): ?><p class="form__error" id="message-error"><?= e($error) ?></p><?php endif; ?>
        </div>

        <?php $error = $fieldError('consentement'); ?>
        <div class="form__row form__row--check">
            <input id="consentement" name="consentement" type="checkbox" value="1" required
                   <?= $oldValue('consentement') === '1' ? 'checked' : '' ?>
                   <?= $error !== null ? 'aria-invalid="true" aria-describedby="consentement-error"' : '' ?>>
            <label for="consentement"><?= e(content('devis.consent')) ?><span aria-hidden="true"> *</span></label>
            <?php if ($error !== null): ?><p class="form__error" id="consentement-error"><?= e($error) ?></p><?php endif; ?>
        </div>

        <button class="btn btn--primary" type="submit"><?= e(content('devis.submit')) ?></button>
    </form>
<?php endif; ?>
</section>
