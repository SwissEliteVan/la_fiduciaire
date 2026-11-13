<?php

declare(strict_types=1);

/**
 * Gestion CLI des comptes : fonctionne en mode fichiers privés (sans SQL)
 * comme en mode MySQL. Le code d'activation n'est affiché qu'ici : à remettre
 * par un canal privé, jamais dans un commit ou un journal.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require APP_ROOT . '/app/portal/core.php';

$command = $argv[1] ?? '';
$email = lf_email($argv[2] ?? '');
$extra = trim($argv[3] ?? '');
$valid = in_array($command, ['nouveau-client', 'client-dossier', 'cabinet', 'reinitialiser', 'revoquer'], true)
    && $email !== null
    && ($command !== 'nouveau-client' || ($extra !== '' && strlen($extra) <= 160 && preg_match('//u', $extra) === 1))
    && ($command !== 'client-dossier' || (ctype_digit($extra) && (int) $extra >= 1));

if (!$valid) {
    fwrite(STDERR, "Usage : php tools/portal/comptes.php nouveau-client EMAIL 'NOM DU DOSSIER'\n"
        . "        php tools/portal/comptes.php client-dossier EMAIL ID_DOSSIER\n"
        . "        php tools/portal/comptes.php cabinet|reinitialiser|revoquer EMAIL\n");
    exit(2);
}

try {
    if ($command === 'revoquer') {
        if (!lf_account_revoke($email)) {
            throw new DomainException('Compte actif introuvable.');
        }
        fwrite(STDOUT, "Accès révoqué et sessions invalidées.\n");
        exit;
    }

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expire = time() + 86400;
    $dossier = null;

    if ($command === 'reinitialiser') {
        if (!lf_account_reset($email, $hash, $expire)) {
            throw new DomainException('Compte actif introuvable.');
        }
    } else {
        if ($command === 'nouveau-client') {
            $dossier = lf_dossier_create($extra);
        } elseif ($command === 'client-dossier') {
            $dossier = lf_dossier_name($extra) === null ? null : $extra;
            if ($dossier === null) {
                throw new DomainException('Dossier introuvable.');
            }
        }
        if (lf_account_by_email($email) !== null) {
            throw new DomainException('Un compte existe déjà pour cette adresse.');
        }
        lf_account_create($email, $dossier, $command === 'cabinet', $hash, $expire);
    }

    if ($dossier !== null) {
        fwrite(STDOUT, "Identifiant du dossier : $dossier\n");
    }
    fwrite(STDOUT, "Code à usage unique, valable 24 h. À remettre par canal privé :\n$token\n");
} catch (Throwable $error) {
    fwrite(STDERR, $error instanceof DomainException ? $error->getMessage() . "\n"
        : "Opération non effectuée. Vérifiez la configuration et l’unicité du compte.\n");
    exit(1);
}
