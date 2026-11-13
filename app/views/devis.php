<?php
declare(strict_types=1);
$fields = (array) content('devis.fields', []);
$errors = $errors ?? [];
$values = $values ?? [];
$enabled = $enabled ?? false;
$inputError = static fn(string $field): string => isset($errors[$field]) ? 'aria-invalid="true" aria-describedby="erreur-' . e($field) . '"' : '';
?>
<section class="quote-hero">
    <div class="quote-hero__inner">
        <p class="section-kicker">La Fiduciaire <span>Premier contact</span></p>
        <h1><?= e(content('devis.title')) ?></h1>
        <p><?= e(content('devis.intro')) ?></p>
    </div>
</section>

<section class="section quote-section" aria-label="Votre demande de devis">
    <div class="quote-card">
        <?php if (!empty($success)): ?>
            <p class="notice" role="status">Votre demande a été enregistrée. La Fiduciaire vous répondra aux coordonnées indiquées.</p>
        <?php elseif (!$enabled): ?>
            <p class="notice"><?= e(content('devis.note')) ?></p>
        <?php else: ?>
            <p class="notice">N’ajoutez aucune pièce comptable à votre message.
                <?php if (config('portal.enabled', false) === true): ?>
                    Pour transmettre vos justificatifs, utilisez l’<a href="/espace-client">espace client</a>.
                <?php endif; ?>
            </p>
            <?php if ($errors): ?><p class="notice" role="alert">Vérifiez les champs signalés avant d’envoyer de nouveau.</p><?php endif; ?>
            <form class="form" method="post" action="/devis">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <input type="hidden" name="envoi" value="<?= e($_SESSION['quote_id']) ?>">
                <div hidden><label for="site-web">Laisser vide</label><input id="site-web" name="site_web" tabindex="-1" autocomplete="off"></div>
                <?php foreach ([
                    'nom' => ['text', 120, 'name', true],
                    'email' => ['email', 180, 'email', true],
                    'telephone' => ['tel', 40, 'tel', false],
                    'activite' => ['text', 160, 'organization', false],
                    'canton' => ['text', 60, 'address-level1', false],
                ] as $key => [$type, $max, $autocomplete, $required]): ?>
                    <div class="form__row">
                        <label for="<?= e($key) ?>"><?= e($fields[$key]) ?></label>
                        <input id="<?= e($key) ?>" name="<?= e($key) ?>" type="<?= e($type) ?>" maxlength="<?= e($max) ?>"
                               autocomplete="<?= e($autocomplete) ?>" value="<?= e($values[$key] ?? '') ?>" <?= $required ? 'required' : '' ?> <?= $inputError($key) ?>>
                        <?php if (isset($errors[$key])): ?><small id="erreur-<?= e($key) ?>"><?= e($errors[$key]) ?></small><?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="form__row">
                    <label for="besoin"><?= e($fields['besoin']) ?></label>
                    <select id="besoin" name="besoin" <?= $inputError('besoin') ?>>
                        <option value="">Choisir une prestation (facultatif)</option>
                        <?php foreach ((array) content('devis.besoins', []) as $besoin): ?>
                            <option value="<?= e($besoin) ?>" <?= ($values['besoin'] ?? '') === $besoin ? 'selected' : '' ?>><?= e($besoin) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['besoin'])): ?><small id="erreur-besoin"><?= e($errors['besoin']) ?></small><?php endif; ?>
                </div>
                <div class="form__row">
                    <label for="message"><?= e($fields['message']) ?></label>
                    <textarea id="message" name="message" rows="6" maxlength="4000" required <?= $inputError('message') ?>><?= e($values['message'] ?? '') ?></textarea>
                    <?php if (isset($errors['message'])): ?><small id="erreur-message"><?= e($errors['message']) ?></small><?php endif; ?>
                </div>
                <div class="form__row form__row--check">
                    <input id="consentement" name="consentement" type="checkbox" value="1" required
                           <?= !empty($values['consentement']) ? 'checked' : '' ?> <?= $inputError('consentement') ?>>
                    <label for="consentement"><?= e(content('devis.consent')) ?></label>
                    <?php if (isset($errors['consentement'])): ?><small id="erreur-consentement"><?= e($errors['consentement']) ?></small><?php endif; ?>
                </div>
                <button class="btn btn--primary" type="submit"><?= e(content('devis.submit')) ?></button>
                <p class="form__reassurance"><?= e(content('devis.reassurance')) ?></p>
            </form>
        <?php endif; ?>
    </div>
</section>
