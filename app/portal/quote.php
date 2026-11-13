<?php

declare(strict_types=1);

require_once __DIR__ . '/core.php';

function lf_quote(string $method): void
{
    ini_set('display_errors', '0');
    header('Cache-Control: no-store, private');
    $enabled = config('quote_enabled', false) === true;
    if (!$enabled) {
        render($method === 'POST' ? 'devis-indisponible' : 'devis', ['enabled' => false], $method === 'POST' ? 503 : 200);
        return;
    }
    try {
        lf_session();
        $values = [];
        $errors = [];
        if ($method !== 'POST') {
            if (!empty($_SESSION['quote_sent'])) {
                unset($_SESSION['quote_sent']);
                render('devis', ['enabled' => true, 'success' => true]);
                return;
            }
            $_SESSION['quote_id'] ??= bin2hex(random_bytes(16));
            render('devis', ['enabled' => true]);
            return;
        }
        if (!lf_csrf_ok()) {
            lf_error('Formulaire expiré', 'Rouvrez le formulaire de devis avant de l’envoyer.', 403);
            return;
        }
        if (!lf_rate('devis', (string) ($_SERVER['REMOTE_ADDR'] ?? ''), 10)) {
            lf_error('Envois temporairement limités', 'Réessayez dans quelques minutes.', 429);
            return;
        }
        $quoteId = lf_text($_POST, 'envoi');
        if (isset($_SESSION['quote_last']) && hash_equals($_SESSION['quote_last'], $quoteId)) {
            $_SESSION['quote_sent'] = true;
            lf_redirect('/devis');
        }
        if (!isset($_SESSION['quote_id']) || !hash_equals($_SESSION['quote_id'], $quoteId)) {
            lf_error('Formulaire expiré', 'Rouvrez le formulaire de devis avant de l’envoyer.', 403);
            return;
        }
        $limits = ['nom' => 120, 'email' => 180, 'telephone' => 40, 'activite' => 160, 'canton' => 60, 'besoin' => 60, 'message' => 4000];
        foreach ($limits as $key => $limit) {
            $value = lf_text($_POST, $key);
            if (!preg_match('//u', $value) || strlen($value) > $limit * 4) {
                $errors[$key] = 'Saisie invalide.';
                continue;
            }
            $length = preg_match_all('/./us', $value);
            if ($length > $limit) {
                $errors[$key] = 'Réduisez la longueur de ce champ.';
            }
            $values[$key] = $value;
        }
        foreach (['nom', 'message'] as $key) {
            if (empty($values[$key])) {
                $errors[$key] = 'Ce champ est obligatoire.';
            }
        }
        $email = lf_email($values['email'] ?? '');
        if (!$email) {
            $errors['email'] = 'Indiquez une adresse e-mail valide.';
        }
        if (!empty($values['besoin']) && !in_array($values['besoin'], (array) content('devis.besoins'), true)) {
            $errors['besoin'] = 'Sélectionnez un besoin proposé.';
        }
        $values['consentement'] = lf_text($_POST, 'consentement') === '1';
        if (!$values['consentement']) {
            $errors['consentement'] = 'Confirmez que vos données peuvent être utilisées pour répondre à votre demande.';
        }
        if ($errors) {
            render('devis', compact('enabled', 'values', 'errors'), 422);
            return;
        }
        if (lf_text($_POST, 'site_web') !== '') {
            lf_error('Envoi refusé', 'Rechargez le formulaire et réessayez.', 422);
            return;
        }
        lf_query('INSERT INTO demandes_devis (nom, email, telephone, activite, canton, besoin, message, consentement)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)', [$values['nom'], $email, $values['telephone'] ?: null,
            $values['activite'] ?: null, $values['canton'] ?: null, $values['besoin'] ?: null, $values['message']]);
        $_SESSION['quote_last'] = $quoteId;
        unset($_SESSION['quote_id']);
        $_SESSION['quote_sent'] = true;
        lf_redirect('/devis');
    } catch (Throwable $error) {
        error_log('[LF devis] Service indisponible (' . get_class($error) . ').');
        render('devis-indisponible', [], 503);
    }
}
