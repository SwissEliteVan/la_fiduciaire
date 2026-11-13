<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require APP_ROOT . '/app/portal/core.php';

$command = $argv[1] ?? '';
$email = lf_email($argv[2] ?? '');
$extra = trim($argv[3] ?? '');
if (!$email || !in_array($command, ['nouveau-client', 'client-dossier', 'cabinet', 'reinitialiser', 'revoquer'], true)
    || ($command === 'nouveau-client' && ($extra === '' || strlen($extra) > 160 || !preg_match('//u', $extra)))
    || ($command === 'client-dossier' && (!ctype_digit($extra) || (int) $extra < 1))) {
    fwrite(STDERR, "Usage : php tools/portal/comptes.php nouveau-client EMAIL 'NOM DU DOSSIER'\n"
        . "        php tools/portal/comptes.php client-dossier EMAIL ID_DOSSIER\n"
        . "        php tools/portal/comptes.php cabinet|reinitialiser|revoquer EMAIL\n");
    exit(2);
}

try {
    $db = lf_db();
    $db->beginTransaction();
    if ($command === 'revoquer') {
        $changed = lf_query('UPDATE lf_comptes SET actif = 0, activation_hash = NULL, activation_expire = NULL,
            version_session = version_session + 1 WHERE email = ? AND actif = 1', [$email])->rowCount();
        if ($changed !== 1) {
            throw new DomainException('Compte actif introuvable.');
        }
        $db->commit();
        fwrite(STDOUT, "Accès révoqué et sessions invalidées.\n");
        exit;
    }

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $dossier = null;
    if ($command === 'reinitialiser') {
        $changed = lf_query('UPDATE lf_comptes SET activation_hash = ?, activation_expire = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR),
            password_hash = NULL, version_session = version_session + 1 WHERE email = ? AND actif = 1', [$hash, $email])->rowCount();
        if ($changed !== 1) {
            throw new DomainException('Compte actif introuvable.');
        }
    } else {
        if ($command === 'nouveau-client') {
            lf_query('INSERT INTO lf_dossiers (nom) VALUES (?)', [$extra]);
            $dossier = $db->lastInsertId();
        } elseif ($command === 'client-dossier') {
            $dossier = lf_query('SELECT id FROM lf_dossiers WHERE id = ?', [$extra])->fetchColumn();
            if (!$dossier) {
                throw new DomainException('Dossier introuvable.');
            }
        }
        lf_query('INSERT INTO lf_comptes (dossier_id, email, role, activation_hash, activation_expire)
            VALUES (?, ?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR))',
            [$dossier, $email, $command === 'cabinet' ? 'cabinet' : 'client', $hash]);
    }
    $db->commit();
    if ($dossier !== null) {
        fwrite(STDOUT, "Identifiant du dossier : $dossier\n");
    }
    fwrite(STDOUT, "Code à usage unique, valable 24 h. À remettre par canal privé :\n$token\n");
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, $error instanceof DomainException ? $error->getMessage() . "\n"
        : "Opération non effectuée. Vérifiez la configuration, la migration et l’unicité du compte.\n");
    exit(1);
}
