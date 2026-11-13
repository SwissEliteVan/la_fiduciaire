<?php

declare(strict_types=1);

const LF_MAX_FILES = 5;
const LF_MAX_BYTES = 10 * 1024 * 1024;
const LF_PAGE_SIZE = 25;
const LF_DOSSIER_QUOTA = 1024 * 1024 * 1024;
const LF_MIMES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

/** Dossier des pièces, sous la racine privée vérifiée hors racine web. */
function lf_storage(): string
{
    $dir = lf_portal_dir() . DIRECTORY_SEPARATOR . 'documents';
    if (!is_dir($dir)) {
        $mask = umask(0077);
        try {
            $created = @mkdir($dir, 0700);
        } finally {
            umask($mask);
        }
        if (!$created && !is_dir($dir)) {
            throw new RuntimeException('Création du stockage des pièces impossible.');
        }
        lf_chmod_private($dir, 0700);
    }
    if (is_link($dir) || !is_dir($dir) || !is_writable($dir)) {
        throw new RuntimeException('Stockage des pièces inaccessible.');
    }
    if (!lf_windows()) {
        $mode = @fileperms($dir);
        if ($mode === false || ($mode & 0077) !== 0) {
            throw new RuntimeException('Permissions du stockage des pièces trop larges.');
        }
    }

    return $dir;
}

function lf_ini_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    $unit = strtolower(substr($value, -1));
    $number = (int) $value;

    return match ($unit) {
        'g'     => $number * 1024 * 1024 * 1024,
        'm'     => $number * 1024 * 1024,
        'k'     => $number * 1024,
        default => $number,
    };
}

/** Taille réellement acceptée par PHP et par l'application. */
function lf_upload_max(): int
{
    $ini = lf_ini_bytes((string) ini_get('upload_max_filesize'));

    return $ini > 0 ? min(LF_MAX_BYTES, $ini) : LF_MAX_BYTES;
}

function lf_upload_limit_label(): string
{
    $bytes = lf_upload_max();
    if ($bytes >= 1024 * 1024) {
        return rtrim(rtrim(number_format($bytes / 1024 / 1024, 1, ',', ''), '0'), ',') . ' Mo';
    }

    return max(1, (int) ceil($bytes / 1024)) . ' Ko';
}

/** Signature binaire : indépendant de l'extension fileinfo. */
function lf_detect_mime(string $file): ?string
{
    $handle = @fopen($file, 'rb');
    if (!is_resource($handle)) {
        return null;
    }
    $head = (string) fread($handle, 16);
    fclose($handle);

    if (str_starts_with($head, '%PDF-')) {
        return 'application/pdf';
    }
    if (str_starts_with($head, "\x89PNG\r\n\x1a\n")) {
        return 'image/png';
    }
    if (str_starts_with($head, "\xFF\xD8\xFF")) {
        return 'image/jpeg';
    }

    return null;
}

/** Commande d'analyse : réellement externe hors recette locale. */
function lf_scanner(): array
{
    $command = config('portal.scanner_command');
    if (is_array($command) && $command !== []) {
        if (!lf_local_http()) {
            throw new RuntimeException('Commande de test refusée hors boucle locale.');
        }
        foreach ($command as $part) {
            if (!is_string($part) || $part === '' || str_contains($part, "\0")) {
                throw new RuntimeException('Commande d’analyse invalide.');
            }
        }

        return array_values($command);
    }

    $scanner = config('portal.scanner');
    if (!is_string($scanner) || !lf_absolute_path($scanner) || !is_file($scanner)
        || !is_executable($scanner) || !function_exists('proc_open')) {
        throw new RuntimeException('Analyse de fichiers indisponible.');
    }
    $resolved = realpath($scanner);
    $project = realpath(APP_ROOT);
    if ($resolved === false || $project === false
        || (lf_path_within($resolved, $project) && !lf_local_http())) {
        throw new RuntimeException('Le scanner doit être installé hors projet.');
    }

    return [$resolved];
}

function lf_scan(string $file, array $command): bool
{
    $null = lf_windows() ? 'NUL' : '/dev/null';
    $process = proc_open(array_merge($command, ['--no-summary', '--', $file]), [
        0 => ['file', $null, 'r'],
        1 => ['file', $null, 'w'],
        2 => ['file', $null, 'w'],
    ], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Analyse de fichiers indisponible.');
    }
    $deadline = microtime(true) + 20;
    do {
        $status = proc_get_status($process);
        if (!$status['running']) {
            $closed = proc_close($process);
            $code = $status['exitcode'] >= 0 ? $status['exitcode'] : $closed;
            if ($code === 0 || $code === 1) {
                return $code === 0;
            }
            throw new RuntimeException('Échec de l’analyse du fichier.');
        }
        usleep(50000);
    } while (microtime(true) < $deadline);
    proc_terminate($process, 9);
    proc_close($process);
    throw new RuntimeException('Délai d’analyse dépassé.');
}

function lf_period(string $period): bool
{
    return (bool) preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])$/D', $period);
}

function lf_uploads(array $files): array
{
    $names = $files['name'] ?? null;
    if (!is_array($names) || !$names || !array_is_list($names) || count($names) > LF_MAX_FILES) {
        throw new DomainException('Choisissez entre 1 et 5 fichiers.');
    }

    $max = lf_upload_max();
    $label = lf_upload_limit_label();
    $uploads = [];
    foreach ($names as $i => $name) {
        $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        $tmp = $files['tmp_name'][$i] ?? null;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new DomainException('Un fichier dépasse la taille autorisée de ' . $label . '.');
        }
        if ($error !== UPLOAD_ERR_OK || !is_string($tmp) || !is_uploaded_file($tmp)) {
            throw new DomainException('Choisissez des fichiers complets et réessayez.');
        }
        $size = filesize($tmp);
        if ($size === false || $size < 1 || $size > $max) {
            throw new DomainException('Chaque fichier doit être non vide et faire au maximum ' . $label . '.');
        }

        $mime = lf_detect_mime($tmp);
        $extension = is_string($name) ? strtolower(pathinfo($name, PATHINFO_EXTENSION)) : '';
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        if ($mime === null || !isset(LF_MIMES[$mime]) || LF_MIMES[$mime] !== $extension) {
            throw new DomainException('Les seuls formats acceptés sont PDF, JPG et PNG.');
        }
        if ($mime !== 'application/pdf') {
            $image = @getimagesize($tmp);
            if (!is_array($image) || ($image['mime'] ?? null) !== $mime) {
                throw new DomainException('Une image ne peut pas être lue. Exportez-la de nouveau.');
            }
        }

        $original = basename(str_replace('\\', '/', (string) $name));
        $original = preg_replace('/[^\p{L}\p{N} ._()\-]/u', '_', $original) ?? '';
        $original = trim($original, ". \t");
        if ($original === '' || strlen($original) > 170) {
            $original = 'justificatif.' . $extension;
        }
        $uploads[] = ['name' => $original, 'tmp' => $tmp, 'mime' => $mime, 'size' => $size];
    }

    return $uploads;
}

function lf_save_uploads(array $user, string $period, string $category, array $uploads): int
{
    $storage = lf_storage();
    $scanner = lf_scanner();
    foreach ($uploads as $upload) {
        if (!lf_scan($upload['tmp'], $scanner)) {
            throw new DomainException('Un fichier n’a pas passé le contrôle. Vérifiez-le puis sélectionnez de nouveau les pièces.');
        }
    }
    $incoming = array_sum(array_column($uploads, 'size'));
    if (lf_piece_usage((string) $user['dossier_id']) + $incoming > LF_DOSSIER_QUOTA) {
        throw new DomainException('Le dossier a atteint sa capacité. Contactez votre interlocuteur avant un nouvel envoi.');
    }

    $written = [];
    $previousMask = umask(0077);
    try {
        foreach ($uploads as $upload) {
            $id = bin2hex(random_bytes(16));
            $destination = $storage . DIRECTORY_SEPARATOR . $id;
            $temporary = $storage . DIRECTORY_SEPARATOR . '.tmp-' . bin2hex(random_bytes(12));
            if (!move_uploaded_file($upload['tmp'], $temporary)) {
                throw new RuntimeException('Écriture du fichier impossible.');
            }
            lf_chmod_private($temporary);
            if (!@rename($temporary, $destination)) {
                @unlink($temporary);
                throw new RuntimeException('Publication du fichier impossible.');
            }
            $written[] = $destination;
            lf_piece_create([
                'id'          => $id,
                'dossier_id'  => (string) $user['dossier_id'],
                'compte_id'   => (string) $user['id'],
                'nom'         => $upload['name'],
                'mime'        => $upload['mime'],
                'octets'      => $upload['size'],
                'periode'     => $period,
                'categorie'   => $category,
            ]);
        }
    } catch (Throwable $error) {
        foreach ($written as $path) {
            @unlink($path);
        }
        throw $error;
    } finally {
        umask($previousMask);
    }

    return count($written);
}

function lf_piece(array $user, string $id): ?array
{
    if (!preg_match('/^[a-f0-9]{32}$/D', $id)) {
        return null;
    }
    $dossierId = $user['role'] === 'cabinet' ? null : (string) $user['dossier_id'];

    return lf_piece_get($id, $dossierId);
}

function lf_download(array $user, string $id, bool $head): void
{
    $piece = lf_piece($user, $id);
    if (!$piece) {
        lf_error('Pièce introuvable', 'Cette pièce n’est pas disponible dans votre espace.', 404);
        return;
    }
    $path = lf_storage() . DIRECTORY_SEPARATOR . $piece['id'];
    if (is_link($path) || !is_file($path) || filesize($path) !== (int) $piece['octets']
        || !isset(LF_MIMES[$piece['mime']]) || lf_detect_mime($path) !== $piece['mime']) {
        lf_error('Pièce indisponible', 'La pièce ne peut pas être téléchargée pour le moment.', 503);
        return;
    }
    $stream = @fopen($path, 'rb');
    if (!is_resource($stream)) {
        throw new RuntimeException('Ouverture du fichier impossible.');
    }
    try {
        if (!$head) {
            lf_audit($id, $user, 'telechargement');
        }
        session_write_close();
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=piece." . LF_MIMES[$piece['mime']]
            . "; filename*=UTF-8''" . rawurlencode($piece['nom']));
        header('Content-Length: ' . $piece['octets']);
        if (!$head) {
            fpassthru($stream);
        }
    } finally {
        fclose($stream);
    }
}
function lf_listing(array $user): array
{
    $filters = [
        'period'  => lf_text($_GET, 'periode'),
        'status'  => lf_text($_GET, 'statut'),
        'dossier' => lf_text($_GET, 'dossier'),
    ];
    $cabinet = $user['role'] === 'cabinet';
    $page = max(1, min(100000, (int) lf_text($_GET, 'page')));
    $dossierFilter = $cabinet
        ? (ctype_digit($filters['dossier']) ? $filters['dossier'] : null)
        : (string) $user['dossier_id'];
    $result = lf_pieces([
        'dossier' => $dossierFilter,
        'period'  => lf_period($filters['period']) ? $filters['period'] : null,
        'status'  => isset(lf_statuses()[$filters['status']]) ? $filters['status'] : null,
    ], $page, LF_PAGE_SIZE);

    return [
        'pieces'   => $result['pieces'],
        'more'     => $result['more'],
        'dossiers' => $cabinet ? lf_dossiers() : [],
        'dossier'  => $cabinet ? null : lf_dossier_name((string) $user['dossier_id']),
        'filters'  => $filters,
        'page'     => $page,
    ];
}
