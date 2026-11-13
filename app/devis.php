<?php

declare(strict_types=1);

/**
 * Traitement du formulaire de devis (TASK-006).
 *
 * - validation serveur stricte (aucune confiance dans le navigateur) ;
 * - jeton CSRF de session, renouvelé après chaque soumission acceptée ;
 * - anti-spam natif : piège à robots + limitation de tentatives par IP ;
 * - insertion MySQL par requête préparée réelle ;
 * - aucune donnée personnelle dans les journaux, aucun contenu technique dans
 *   les messages d'erreur affichés.
 *
 * Les fonctions de validation sont pures et testables sans base ni session
 * (voir tools/tests/devis-check.php).
 */

if (!defined('APP_ROOT')) {
    http_response_code(500);
    exit('Amorçage applicatif manquant.');
}

const DEVIS_MAX_POST_BYTES = 32768; // 32 Kio : formulaire texte, aucun fichier.
const DEVIS_MAX_MESSAGE    = 4000;
const DEVIS_MAX_ATTEMPTS   = 10;
const DEVIS_WINDOW_SECONDS = 900;   // 15 minutes.
const DEVIS_MAX_NOM        = 120;
const DEVIS_MAX_EMAIL      = 180;
const DEVIS_MAX_TELEPHONE  = 40;
const DEVIS_MAX_ACTIVITE   = 160;
const DEVIS_MAX_CANTON     = 60;

/**
 * Valide et normalise les champs du formulaire.
 *
 * @param array<string, mixed> $input
 * @return array{data: array<string, string>, errors: array<string, true>}
 */
function devis_validate(array $input): array
{
    $keys = ['nom', 'email', 'telephone', 'activite', 'canton', 'besoin', 'message'];
    $data = [];

    foreach ($keys as $key) {
        $raw = $input[$key] ?? '';
        $data[$key] = is_string($raw) ? trim($raw) : '';
    }

    $errors = [];

    // Caractères de contrôle : aucun champ légitime n'en contient.
    foreach ($data as $key => $value) {
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value) === 1) {
            $errors[$key] = true;
        }
    }

    $nom = $data['nom'];
    if (!isset($errors['nom'])
        && (text_length($nom) < 2
            || text_length($nom) > DEVIS_MAX_NOM
            || preg_match("/^[\p{L}\p{M} .'’\-]+$/u", $nom) !== 1)
    ) {
        $errors['nom'] = true;
    }

    if (!isset($errors['email'])
        && (text_length($data['email']) > DEVIS_MAX_EMAIL
            || filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false)
    ) {
        $errors['email'] = true;
    }

    $telephone = $data['telephone'];
    if ($telephone !== ''
        && (text_length($telephone) < 6
            || text_length($telephone) > DEVIS_MAX_TELEPHONE
            || preg_match('/^[0-9 +().\/-]+$/', $telephone) !== 1)
    ) {
        $errors['telephone'] = true;
    }

    if (!isset($errors['activite']) && text_length($data['activite']) > DEVIS_MAX_ACTIVITE) {
        $errors['activite'] = true;
    }

    if (!isset($errors['canton']) && text_length($data['canton']) > DEVIS_MAX_CANTON) {
        $errors['canton'] = true;
    }

    $besoins = (array) content('devis.besoins', []);
    if (!in_array($data['besoin'], $besoins, true)) {
        $errors['besoin'] = true;
    }

    $messageLength = text_length($data['message']);
    if (!isset($errors['message']) && ($messageLength < 10 || $messageLength > DEVIS_MAX_MESSAGE)) {
        $errors['message'] = true;
    }

    if (($input['consentement'] ?? '') !== '1') {
        $errors['consentement'] = true;
    }
    $data['consentement'] = ($input['consentement'] ?? '') === '1' ? '1' : '';

    return ['data' => $data, 'errors' => $errors];
}

/** Fichier de limitation des tentatives (hors racine web, surchargeable en test). */
function devis_throttle_file(): string
{
    $override = getenv('DEVIS_THROTTLE_FILE');

    if (is_string($override) && $override !== '') {
        return $override;
    }

    return APP_ROOT . '/storage/devis-throttle.json';
}

/**
 * Enregistre une tentative et indique si elle est autorisée.
 * Fail-open journalisé si le fichier local est indisponible : une panne de
 * stockage ne doit pas rendre le formulaire inutilisable.
 */
function devis_throttle_allows(string $ip): bool
{
    $now = time();
    $handle = @fopen(devis_throttle_file(), 'c+');

    if ($handle === false) {
        error_log('devis: limitation de tentatives indisponible (fichier)');
        return true;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        error_log('devis: limitation de tentatives indisponible (verrou)');
        return true;
    }

    $raw = stream_get_contents($handle);
    $state = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

    if (!is_array($state) || !isset($state['salt'], $state['entries'])
        || !is_string($state['salt']) || !is_array($state['entries'])
    ) {
        $state = ['salt' => bin2hex(random_bytes(16)), 'entries' => []];
    }

    $windowStart = $now - DEVIS_WINDOW_SECONDS;
    $entries = [];

    foreach ($state['entries'] as $hash => $timestamps) {
        if (!is_string($hash) || !is_array($timestamps)) {
            continue;
        }

        $recent = [];
        foreach ($timestamps as $timestamp) {
            if (is_int($timestamp) && $timestamp > $windowStart) {
                $recent[] = $timestamp;
            }
        }

        if ($recent !== []) {
            $entries[$hash] = $recent;
        }
    }

    $key = hash_hmac('sha256', $ip, $state['salt']);
    $current = $entries[$key] ?? [];
    $allowed = count($current) < DEVIS_MAX_ATTEMPTS;

    if ($allowed) {
        $current[] = $now;
        $entries[$key] = $current;
    }

    $encoded = json_encode(['salt' => $state['salt'], 'entries' => $entries], JSON_UNESCAPED_SLASHES);
    ftruncate($handle, 0);
    rewind($handle);
    if (is_string($encoded)) {
        fwrite($handle, $encoded);
    }
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return $allowed;
}

/**
 * Insère une demande validée. Requête préparée, aucun identifiant dynamique.
 *
 * @param array<string, string> $data
 */
function devis_store(PDO $pdo, array $data, ?string $ipHash): void
{
    $statement = $pdo->prepare(
        'INSERT INTO demandes_devis
            (nom, email, telephone, activite, canton, besoin, message, consentement, ip_hash)
         VALUES
            (:nom, :email, :telephone, :activite, :canton, :besoin, :message, 1, :ip_hash)'
    );

    $statement->execute([
        ':nom'       => $data['nom'],
        ':email'     => $data['email'],
        ':telephone' => $data['telephone'] !== '' ? $data['telephone'] : null,
        ':activite'  => $data['activite'] !== '' ? $data['activite'] : null,
        ':canton'    => $data['canton'] !== '' ? $data['canton'] : null,
        ':besoin'    => $data['besoin'],
        ':message'   => $data['message'],
        ':ip_hash'   => $ipHash,
    ]);
}

/** Message flash de succès, consommé une seule fois (POST → redirection → GET). */
function devis_take_flash(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE
        || !isset($_SESSION['devis_flash'])
        || !is_array($_SESSION['devis_flash'])
    ) {
        return null;
    }

    $flash = $_SESSION['devis_flash'];
    unset($_SESSION['devis_flash']);

    return $flash;
}

/** Traite un POST /devis et termine la requête. */
function devis_handle_post(): void
{
    header('Cache-Control: no-store, max-age=0');

    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > DEVIS_MAX_POST_BYTES || ($contentLength > 0 && $_POST === [])) {
        render('devis', ['errors' => ['global' => 'toolarge']], 413);
        return;
    }

    if (!csrf_check($_POST['csrf_token'] ?? null)) {
        csrf_rotate();
        render('devis', ['errors' => ['global' => 'csrf']], 403);
        return;
    }

    if (trim((string) ($_POST['site_web'] ?? '')) !== '') {
        // Piège à robots : même réponse qu'un succès, aucune écriture.
        csrf_rotate();
        $_SESSION['devis_flash'] = ['succes' => true];
        redirect('/devis');
    }

    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (!devis_throttle_allows($ip)) {
        render('devis', ['errors' => ['global' => 'rate']], 429);
        return;
    }

    $result = devis_validate($_POST);
    if ($result['errors'] !== []) {
        render('devis', ['errors' => $result['errors'], 'old' => $result['data']], 422);
        return;
    }

    try {
        devis_store(db(), $result['data'], client_ip_hash());
    } catch (Throwable $exception) {
        // Aucun contenu, aucune donnée personnelle dans le journal.
        error_log('devis: enregistrement impossible (' . $exception::class . ')');
        render('devis', ['errors' => ['global' => 'db'], 'old' => $result['data']], 503);
        return;
    }

    csrf_rotate();
    $_SESSION['devis_flash'] = ['succes' => true];
    redirect('/devis');
}
