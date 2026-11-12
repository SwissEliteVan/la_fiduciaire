<?php

declare(strict_types=1);

/**
 * API locale du gestionnaire de médias.
 * Toutes les mutations exigent le jeton de session (en-tête X-MM-Token).
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Inventory.php';
require_once __DIR__ . '/Contract.php';
require_once __DIR__ . '/Upload.php';
require_once __DIR__ . '/Apply.php';

/** État complet renvoyé à l'interface, avec décisions et contrats. */
function mm_public_state(array $state): array
{
    $inventory = [];
    foreach ((array) $state['inventaire'] as $media) {
        $decision = mm_decision_for($state, (string) $media['chemin']);
        $media['decision'] = $decision;
        $media['contrat'] = (($decision['decision'] ?? '') === 'replace' && is_array($decision['remplacement'] ?? null))
            ? mm_contract_media($state, $media, $decision['remplacement'])
            : mm_contract_media($state, $media);
        $inventory[] = $media;
    }

    return [
        'projet'       => $state['projet'],
        'stockage'     => MM_STATE_FILE,
        'stockage_rel' => 'storage/media-manager/media-manager.json',
        'inventaire'   => $inventory,
        'decisions'    => empty($state['decisions']) ? new stdClass() : $state['decisions'],
        'revision'     => (int) $state['revision'],
        'validation'   => $state['validation'],
        'application'  => $state['application'],
        'reglages'     => $state['reglages'],
        'genere_le'    => $state['genere_le'],
    ];
}

function mm_inventory_has(array $state, string $path): bool
{
    foreach ((array) $state['inventaire'] as $media) {
        if ((string) $media['chemin'] === $path) {
            return true;
        }
    }

    return false;
}

/** Reconstruit l'inventaire en préservant les décisions existantes. */
function mm_refresh_inventory(array &$state): void
{
    $state['inventaire'] = mm_build_inventory(MM_PROJECT_ROOT);
    $existing = [];
    foreach ($state['inventaire'] as $media) {
        $existing[(string) $media['chemin']] = true;
    }
    foreach (array_keys((array) $state['decisions']) as $path) {
        if (!isset($existing[(string) $path])) {
            unset($state['decisions'][$path]);
        }
    }
    $state['revision'] = (int) $state['revision'] + 1;
    $state['validation'] = null;
}

/** Sert un fichier local avec support Range (lecture vidéo). */
function mm_serve_file(string $absolute, bool $allowRange = true): never
{
    if (!is_file($absolute)) {
        mm_fail('Fichier introuvable.', 404);
    }

    $size = (int) @filesize($absolute);
    $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
    $mime = MM_MIME[$ext] ?? 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');

    $start = 0;
    $end = $size > 0 ? $size - 1 : 0;

    if ($allowRange && $size > 0 && isset($_SERVER['HTTP_RANGE'])
        && preg_match('/bytes=(\d*)-(\d*)/', (string) $_SERVER['HTTP_RANGE'], $match) === 1) {
        if ($match[1] !== '') {
            $start = (int) $match[1];
        }
        if ($match[2] !== '') {
            $end = (int) $match[2];
        }
        if ($start > $end || $start >= $size) {
            header('Content-Range: bytes */' . $size);
            http_response_code(416);
            exit;
        }
        $end = min($end, $size - 1);
        http_response_code(206);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }

    $length = $size > 0 ? ($end - $start + 1) : 0;
    header('Content-Length: ' . $length);

    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'HEAD' || $length === 0) {
        exit;
    }

    $handle = @fopen($absolute, 'rb');
    if ($handle === false) {
        http_response_code(500);
        exit;
    }

    fseek($handle, $start);
    $remaining = $length;
    while ($remaining > 0 && !feof($handle)) {
        $chunk = fread($handle, (int) min(1048576, $remaining));
        if ($chunk === false || $chunk === '') {
            break;
        }
        echo $chunk;
        $remaining -= strlen($chunk);
        @ob_flush();
        @flush();
    }
    fclose($handle);
    exit;
}

/** Vérifie qu'un chemin local peut être servi (média uniquement, hors interne). */
function mm_resolve_servable(string $relative): ?string
{
    $relative = str_replace('\\', '/', $relative);

    if ($relative === '' || str_contains($relative, '..') || preg_match('/^[a-zA-Z]:/', $relative) === 1) {
        return null;
    }

    foreach (['storage/', 'tools/', '.git/', 'instructions/', 'projet/'] as $forbidden) {
        if (str_starts_with($relative, $forbidden)) {
            return null;
        }
    }

    if (!mm_is_media_path($relative)) {
        return null;
    }

    $absolute = realpath(MM_PROJECT_ROOT . '/' . $relative);
    $root = realpath(MM_PROJECT_ROOT);

    if ($absolute === false || $root === false
        || !str_starts_with(str_replace('\\', '/', $absolute), str_replace('\\', '/', $root) . '/')) {
        return null;
    }

    return $absolute;
}

/**
 * Point d'entrée de l'API.
 */
function mm_api_handle(string $route, string $method): never
{
    mm_guard_local();
    mm_session_start();
    $method = strtoupper($method);

    if ($route === 'session' && $method === 'GET') {
        mm_ok(['token' => mm_token(), 'version' => MM_VERSION]);
    }

    if ($route === 'state' && $method === 'GET') {
        $state = mm_state_load();
        if (empty($state['inventaire'])) {
            $state['inventaire'] = mm_build_inventory(MM_PROJECT_ROOT);
            mm_state_save($state, 'inventaire initial');
        }
        mm_ok(['etat' => mm_public_state($state), 'token' => mm_token()]);
    }

    // Les lectures de fichiers/rapports valident le jeton en paramètre.
    if (in_array($route, ['file', 'import-file', 'export', 'report'], true)) {
        $sent = (string) ($_GET['t'] ?? '');
        if ($sent === '' || !hash_equals(mm_token(), $sent)) {
            mm_fail('Jeton de session invalide.', 419);
        }
    } else {
        mm_require_token();
    }

    if ($route === 'file' && $method === 'GET') {
        $absolute = mm_resolve_servable((string) ($_GET['path'] ?? ''));
        if ($absolute === null) {
            mm_fail('Chemin non servi par le gestionnaire.', 403);
        }
        mm_serve_file($absolute);
    }

    if ($route === 'import-file' && $method === 'GET') {
        $state = mm_state_load();
        $id = (string) ($_GET['id'] ?? '');
        $decision = null;
        foreach ((array) $state['decisions'] as $path => $candidate) {
            if (mm_media_id((string) $path) === $id) {
                $decision = $candidate;
                break;
            }
        }
        $temp = is_array($decision) && is_array($decision['remplacement'] ?? null)
            ? (string) ($decision['remplacement']['chemin_temp'] ?? '')
            : '';
        if ($temp === '') {
            mm_fail('Aucun fichier importé pour ce média.', 404);
        }
        mm_serve_file(mm_import_abs($temp));
    }

    if ($route === 'report' && $method === 'GET') {
        $id = (string) ($_GET['id'] ?? '');
        if (preg_match('/^\d{8}-\d{6}(-[0-9a-f]{6})?$/', $id) !== 1) {
            mm_fail('Identifiant de rapport invalide.', 422);
        }
        $file = null;
        foreach (['logs/apply-' . $id . '.json', 'logs/apply-' . $id . '-echec.json'] as $candidate) {
            if (is_file(MM_STORAGE_DIR . '/' . $candidate)) {
                $file = MM_STORAGE_DIR . '/' . $candidate;
                break;
            }
        }
        if ($file === null) {
            mm_fail('Rapport introuvable.', 404);
        }
        header('Content-Type: application/json; charset=utf-8');
        readfile($file);
        exit;
    }

    if ($route === 'scan' && $method === 'POST') {
        $state = mm_state_load();
        mm_refresh_inventory($state);
        mm_state_save($state, 'nouveau scan');
        mm_ok(['etat' => mm_public_state($state), 'medias' => count($state['inventaire'])]);
    }


    if ($route === 'decision' && $method === 'POST') {
        $body = mm_body();
        $path = mm_safe_rel((string) ($body['chemin'] ?? ''));
        $state = mm_state_load();

        if (!mm_inventory_has($state, $path)) {
            mm_fail('Média inconnu dans l’inventaire.', 404);
        }

        $choice = (string) ($body['decision'] ?? '');
        if (!in_array($choice, ['keep', 'delete', 'replace', 'undecided'], true)) {
            mm_fail('Décision invalide.', 422);
        }

        $decision = mm_decision_for($state, $path);
        $decision['decision'] = $choice;
        $decision['maj_le'] = mm_now();

        if (array_key_exists('note', $body)) {
            $decision['note'] = mm_substr((string) $body['note'], 0, 1000);
        }
        if (isset($body['portee']) && in_array($body['portee'], ['globale', 'pages'], true)) {
            $decision['portee'] = (string) $body['portee'];
        }
        if (isset($body['cibles']) && is_array($body['cibles'])) {
            $decision['cibles'] = array_values(array_map('strval', $body['cibles']));
        }
        if (array_key_exists('generer_variantes', $body)) {
            $decision['generer_variantes'] = (bool) $body['generer_variantes'];
        }

        if ($choice !== 'replace') {
            $decision['remplacement'] = null;
            unset($decision['applique_le']);
        }
        if ($choice !== 'delete') {
            $decision['retrait'] = null;
        }
        if (isset($body['retrait']) && is_array($body['retrait'])) {
            $decision['retrait'] = [
                'demande'  => (bool) ($body['retrait']['demande'] ?? false),
                'fichiers' => array_values(array_map('strval', (array) ($body['retrait']['fichiers'] ?? []))),
            ];
        }

        $state['decisions'][$path] = $decision;
        mm_touch_state($state);
        mm_state_save($state, 'décision ' . $choice . ' ' . $path);

        mm_ok([
            'decision'      => $decision,
            'revision'      => $state['revision'],
            'enregistre_le' => mm_now(),
        ]);
    }

    if ($route === 'bulk' && $method === 'POST') {
        $body = mm_body();
        $paths = array_values(array_map('strval', (array) ($body['chemins'] ?? [])));
        $choice = (string) ($body['decision'] ?? '');

        if ($paths === []) {
            mm_fail('Aucun média sélectionné.', 422);
        }
        if (!in_array($choice, ['keep', 'delete', 'replace', 'undecided'], true)) {
            mm_fail('Décision invalide.', 422);
        }

        $state = mm_state_load();
        $changed = 0;

        foreach ($paths as $path) {
            if (!mm_inventory_has($state, $path)) {
                continue;
            }
            $decision = mm_decision_for($state, $path);
            $decision['decision'] = $choice;
            $decision['maj_le'] = mm_now();
            if ($choice !== 'replace') {
                $decision['remplacement'] = null;
                unset($decision['applique_le']);
            }
            if ($choice !== 'delete') {
                $decision['retrait'] = null;
            }
            $state['decisions'][$path] = $decision;
            $changed++;
        }

        if ($changed === 0) {
            mm_fail('Aucun média valide dans la sélection.', 422);
        }

        mm_touch_state($state);
        mm_state_save($state, 'décisions en lot ' . $choice);
        mm_ok(['modifies' => $changed, 'revision' => $state['revision']]);
    }

    if ($route === 'reset' && $method === 'POST') {
        $body = mm_body();
        $state = mm_state_load();
        $paths = array_values(array_map('strval', (array) ($body['chemins'] ?? [])));

        if (($body['tout'] ?? false) === true) {
            $paths = array_map(static fn (array $media): string => (string) $media['chemin'], (array) $state['inventaire']);
        }
        if ($paths === []) {
            mm_fail('Aucun média à réinitialiser.', 422);
        }

        $count = 0;
        foreach ($paths as $path) {
            if (isset($state['decisions'][$path])) {
                unset($state['decisions'][$path]);
                $count++;
            }
        }

        mm_touch_state($state);
        mm_state_save($state, 'réinitialisation');
        mm_ok(['reinitialises' => $count, 'revision' => $state['revision']]);
    }

    if ($route === 'save' && $method === 'POST') {
        $state = mm_state_load();
        mm_state_save($state, 'enregistrement explicite');
        mm_ok([
            'fichier'    => MM_STATE_FILE,
            'empreinte'  => mm_sha256(MM_STATE_FILE),
            'revision'   => $state['revision'],
            'enregistre_le' => mm_now(),
        ]);
    }

    if ($route === 'export' && $method === 'GET') {
        $state = mm_state_load();
        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="media-manager-' . date('Ymd-His') . '.json"');
        header('Cache-Control: no-store');
        echo is_string($json) ? $json : '{}';
        exit;
    }


    if ($route === 'import' && $method === 'POST') {
        $body = mm_body();
        $incoming = is_array($body['etat'] ?? null) ? $body['etat'] : $body;

        if (!isset($incoming['decisions']) || !is_array($incoming['decisions'])) {
            mm_fail('JSON invalide : la clé « decisions » est absente.', 422);
        }

        $state = mm_state_load();
        $known = [];
        foreach ((array) $state['inventaire'] as $media) {
            $known[(string) $media['chemin']] = true;
        }

        $applied = 0;
        $ignored = [];
        $replacementsIgnored = [];

        foreach ($incoming['decisions'] as $path => $raw) {
            $path = str_replace('\\', '/', (string) $path);

            if (!isset($known[$path]) || !is_array($raw)) {
                $ignored[] = $path;
                continue;
            }

            $choice = (string) ($raw['decision'] ?? 'undecided');
            if (!in_array($choice, ['keep', 'delete', 'replace', 'undecided'], true)) {
                $ignored[] = $path;
                continue;
            }

            $decision = mm_decision_for($state, $path);
            $decision['decision'] = $choice;
            $decision['maj_le'] = mm_now();

            if (isset($raw['note'])) {
                $decision['note'] = mm_substr((string) $raw['note'], 0, 1000);
            }
            if (isset($raw['portee']) && in_array($raw['portee'], ['globale', 'pages'], true)) {
                $decision['portee'] = (string) $raw['portee'];
            }
            if (isset($raw['cibles']) && is_array($raw['cibles'])) {
                $decision['cibles'] = array_values(array_map('strval', $raw['cibles']));
            }
            if (isset($raw['generer_variantes'])) {
                $decision['generer_variantes'] = (bool) $raw['generer_variantes'];
            }
            if ($choice === 'replace' && is_array($raw['remplacement'] ?? null)) {
                $replacement = $raw['remplacement'];
                $temp = (string) ($replacement['chemin_temp'] ?? '');
                $valid = false;
                if ($temp !== '') {
                    try {
                        $absolute = mm_import_abs($temp);
                        $valid = is_file($absolute);
                    } catch (Throwable $error) {
                        $valid = false;
                    }
                }
                if ($valid) {
                    $decision['remplacement'] = $replacement;
                } else {
                    $decision['remplacement'] = null;
                    $replacementsIgnored[] = $path;
                }
            }

            $state['decisions'][$path] = $decision;
            $applied++;
        }

        mm_touch_state($state);
        mm_state_save($state, 'import JSON');
        mm_ok([
            'importes'              => $applied,
            'ignores'               => $ignored,
            'remplacements_ignores' => $replacementsIgnored,
            'revision'              => $state['revision'],
            'etat'                  => mm_public_state($state),
        ]);
    }


    if ($route === 'upload' && $method === 'POST') {
        $path = mm_safe_rel((string) ($_POST['chemin'] ?? ''));
        $state = mm_state_load();
        $media = null;

        foreach ((array) $state['inventaire'] as $candidate) {
            if ((string) $candidate['chemin'] === $path) {
                $media = $candidate;
                break;
            }
        }
        if ($media === null) {
            mm_fail('Média inconnu dans l’inventaire.', 404);
        }
        if (!isset($_FILES['fichier']) || !is_array($_FILES['fichier'])) {
            mm_fail('Aucun fichier reçu.', 422);
        }

        $replacement = mm_store_upload($_FILES['fichier'], (string) $media['type']);

        $decision = mm_decision_for($state, $path);
        $decision['decision'] = 'replace';
        $decision['remplacement'] = $replacement;
        $decision['maj_le'] = mm_now();

        if (isset($_POST['portee']) && in_array($_POST['portee'], ['globale', 'pages'], true)) {
            $decision['portee'] = (string) $_POST['portee'];
        }
        if (isset($_POST['generer_variantes'])) {
            $decision['generer_variantes'] = $_POST['generer_variantes'] === '1';
        }

        $state['decisions'][$path] = $decision;
        mm_touch_state($state);
        mm_state_save($state, 'import média ' . $path);

        mm_ok([
            'remplacement' => $replacement,
            'decision'     => $decision,
            'contrat'      => mm_contract_media($state, $media, $replacement),
            'revision'     => $state['revision'],
            'apercu'       => '/api/import-file?id=' . mm_media_id($path) . '&t=' . mm_token(),
        ]);
    }

    if ($route === 'discard-import' && $method === 'POST') {
        $body = mm_body();
        $temp = (string) ($body['chemin_temp'] ?? '');
        $state = mm_state_load();
        $cleared = 0;

        foreach ((array) $state['decisions'] as $path => $decision) {
            if (is_array($decision['remplacement'] ?? null)
                && (string) ($decision['remplacement']['chemin_temp'] ?? '') === $temp) {
                $state['decisions'][$path]['remplacement'] = null;
                if (($state['decisions'][$path]['decision'] ?? '') === 'replace') {
                    $state['decisions'][$path]['decision'] = 'undecided';
                }
                $cleared++;
            }
        }

        $removed = mm_discard_import($temp);

        if ($cleared > 0) {
            mm_touch_state($state);
            mm_state_save($state, 'abandon import');
        }

        mm_ok(['fichier_supprime' => $removed, 'decisions_annulees' => $cleared, 'revision' => $state['revision']]);
    }

    if ($route === 'validate' && $method === 'POST') {
        $state = mm_state_load();
        $result = mm_validate_state($state);
        $state['validation'] = array_merge($result, [
            'revision'  => (int) $state['revision'],
            'genere_le' => mm_now(),
        ]);
        mm_state_save($state, 'validation');
        mm_ok([
            'validation' => $state['validation'],
            'bloquants'  => mm_validation_blocking($result),
        ]);
    }

    if ($route === 'derogation' && $method === 'POST') {
        $body = mm_body();
        $path = mm_safe_rel((string) ($body['chemin'] ?? ''));
        $state = mm_state_load();

        if (!mm_inventory_has($state, $path)) {
            mm_fail('Média inconnu dans l’inventaire.', 404);
        }

        $accordee = (bool) ($body['accordee'] ?? false);
        $motif = trim((string) ($body['motif'] ?? ''));

        if ($accordee && $motif === '') {
            mm_fail('Un motif est obligatoire pour enregistrer une dérogation.', 422);
        }

        $decision = mm_decision_for($state, $path);
        $decision['derogation'] = [
            'accordee' => $accordee,
            'motif'    => mm_substr($motif, 0, 500),
            'le'       => mm_now(),
        ];
        $state['decisions'][$path] = $decision;
        mm_touch_state($state);
        mm_state_save($state, 'dérogation ' . $path);
        mm_ok(['decision' => $decision, 'revision' => $state['revision']]);
    }

    if ($route === 'apply' && $method === 'POST') {
        $body = mm_body();
        $state = mm_state_load();
        $result = mm_apply_state($state, (string) ($body['confirmation'] ?? ''));
        mm_ok($result);
    }

    if ($route === 'rollback' && $method === 'POST') {
        $body = mm_body();
        $state = mm_state_load();
        $result = mm_rollback_application($state, (string) ($body['id'] ?? ''));
        mm_ok($result);
    }

    mm_fail('Route inconnue ou méthode non autorisée.', 404);
}

