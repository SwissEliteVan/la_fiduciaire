<?php

declare(strict_types=1);

$fields = (array) content('devis.fields', []);
?>
<section class="page-head">
    <h1><?= e(content('devis.title')) ?></h1>
    <p><?= e(content('devis.intro')) ?></p>
</section>

<section class="section">
    <p class="notice"><?= e(content('devis.note')) ?></p>

    <form class="form" method="post" action="/devis" novalidate>
        <div class="form__row">
            <label for="nom"><?= e($fields['nom'] ?? 'Nom') ?></label>
            <input id="nom" name="nom" type="text" autocomplete="name" required maxlength="120">
        </div>

        <div class="form__row">
            <label for="email"><?= e($fields['email'] ?? 'E-mail') ?></label>
            <input id="email" name="email" type="email" autocomplete="email" required maxlength="180">
        </div>

        <div class="form__row">
            <label for="telephone"><?= e($fields['telephone'] ?? 'Téléphone') ?></label>
            <input id="telephone" name="telephone" type="tel" autocomplete="tel" maxlength="40">
        </div>

        <div class="form__row">
            <label for="activite"><?= e($fields['activite'] ?? 'Activité') ?></label>
            <input id="activite" name="activite" type="text" maxlength="160">
        </div>

        <div class="form__row">
            <label for="canton"><?= e($fields['canton'] ?? 'Canton') ?></label>
            <input id="canton" name="canton" type="text" maxlength="60">
        </div>

        <div class="form__row">
            <label for="besoin"><?= e($fields['besoin'] ?? 'Besoin') ?></label>
            <select id="besoin" name="besoin">
                <?php foreach ((array) content('devis.besoins', []) as $besoin): ?>
                    <option value="<?= e($besoin) ?>"><?= e($besoin) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form__row">
            <label for="message"><?= e($fields['message'] ?? 'Message') ?></label>
            <textarea id="message" name="message" rows="6" maxlength="4000" required></textarea>
        </div>

        <div class="form__row form__row--check">
            <input id="consentement" name="consentement" type="checkbox" value="1" required>
            <label for="consentement"><?= e(content('devis.consent')) ?></label>
        </div>

        <button class="btn btn--primary" type="submit"><?= e(content('devis.submit')) ?></button>
    </form>
</section>
