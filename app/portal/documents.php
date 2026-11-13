<?php

declare(strict_types=1);

const LF_MAX_FILES = 5;
const LF_MAX_BYTES = 10 * 1024 * 1024;
const LF_PAGE_SIZE = 25;
const LF_DOSSIER_QUOTA = 1024 * 1024 * 1024;
const LF_MIMES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

function lf_storage(): string
{
    $configured = config('portal.storage_dir');
    if (!is_string($configured) || !str_starts_with($configured, '/') || !is_dir($configured)) {
        throw new RuntimeException('Stockage privé non configuré.');
    }
    $root = realpath($configured);
    $docroot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? APP_ROOT));
    $project = realpath(APP_ROOT);
    if (!$root || !$docroot || !$project || $root === '/' || !is_writable($root) || (fileperms($root) & 0077) !== 0) {
        throw new RuntimeException('Stockage privé inaccessible ou permissions trop larges.');
    }
    foreach ([$docroot, $project] as $public) {
        if ($root === $public || str_starts_with($root, $public . '/') || str_starts_with($public, $root . '/')) {
            throw new RuntimeException('Stockage dans un emplacement public ou parent.');
        }
    }
    return $root;
}

function lf_scanner(): string
{
    $scanner = config('portal.scanner');
    if (!is_string($scanner) || !str_starts_with($scanner, '/') || !is_file($scanner)
        || !is_executable($scanner) || !function_exists('proc_open')) {
        throw new RuntimeException('Analyse de fichiers indisponible.');
    }
    $resolved = realpath($scanner);
    $project = realpath(APP_ROOT);
    if ($resolved === false || $project === false
        || (str_starts_with($resolved, $project . '/') && !lf_local_http())) {
        throw new RuntimeException('Le scanner doit être installé hors projet.');
    }
    return $resolved;
}

function lf_scan(string $file, string $scanner): bool
{
    $process = proc_open([$scanner, '--no-summary', '--', $file], [
        0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w'],
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
    $info = new finfo(FILEINFO_MIME_TYPE);
    $uploads = [];
    foreach ($names as $i => $name) {
        $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        $tmp = $files['tmp_name'][$i] ?? null;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new DomainException('Un fichier dépasse la taille autorisée de 10 Mo.');
        }
        if ($error !== UPLOAD_ERR_OK || !is_string($tmp) || !is_uploaded_file($tmp)) {
            throw new DomainException('Choisissez des fichiers complets et réessayez.');
        }
        $size = filesize($tmp);
        if ($size === false || $size < 1 || $size > LF_MAX_BYTES) {
            throw new DomainException('Chaque fichier doit être non vide et faire au maximum 10 Mo.');
        }
        $mime = $info->file($tmp);
        $extension = is_string($name) ? strtolower(pathinfo($name, PATHINFO_EXTENSION)) : '';
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        if (!is_string($mime) || !isset(LF_MIMES[$mime]) || LF_MIMES[$mime] !== $extension) {
            throw new DomainException('Les seuls formats acceptés sont PDF, JPG et PNG.');
        }
        if ($mime === 'application/pdf') {
            if (file_get_contents($tmp, false, null, 0, 5) !== '%PDF-') {
                throw new DomainException('Un PDF ne peut pas être reconnu. Exportez-le de nouveau.');
            }
        } else {
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
    $db = lf_db();
    $db->beginTransaction();
    $written = [];
    $previousMask = umask(0077);
    try {
        lf_query('SELECT id FROM lf_dossiers WHERE id = ? FOR UPDATE', [$user['dossier_id']])->fetchColumn();
        $used = (int) lf_query('SELECT COALESCE(SUM(octets), 0) FROM lf_pieces WHERE dossier_id = ?', [$user['dossier_id']])->fetchColumn();
        if ($used + array_sum(array_column($uploads, 'size')) > LF_DOSSIER_QUOTA) {
            throw new DomainException('Le dossier a atteint sa capacité. Contactez votre interlocuteur avant un nouvel envoi.');
        }
        foreach ($uploads as $upload) {
            $id = bin2hex(random_bytes(16));
            $destination = $storage . '/' . $id;
            if (!move_uploaded_file($upload['tmp'], $destination)) {
                throw new RuntimeException('Écriture du fichier impossible.');
            }
            $written[] = $destination;
            if (!chmod($destination, 0600)) {
                throw new RuntimeException('Permissions du fichier non appliquées.');
            }
            lf_query('INSERT INTO lf_pieces (id, dossier_id, compte_id, nom, mime, octets, periode, categorie)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$id, $user['dossier_id'], $user['id'], $upload['name'], $upload['mime'],
                $upload['size'], $period, $category]);
            lf_audit($id, $user, 'depot', 'recu');
        }
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
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
    $sql = 'SELECT id, dossier_id, nom, mime, octets FROM lf_pieces WHERE id = ?';
    $params = [$id];
    if ($user['role'] !== 'cabinet') {
        $sql .= ' AND dossier_id = ?';
        $params[] = $user['dossier_id'];
    }
    return lf_query($sql, $params)->fetch() ?: null;
}

function lf_download(array $user, string $id, bool $head): void
{
    $piece = lf_piece($user, $id);
    if (!$piece) {
        lf_error('Pièce introuvable', 'Cette pièce n’est pas disponible dans votre espace.', 404);
        return;
    }
    $storage = lf_storage();
    $path = $storage . '/' . $piece['id'];
    if (is_link($path) || !is_file($path) || filesize($path) !== (int) $piece['octets']
        || !isset(LF_MIMES[$piece['mime']])) {
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
    $filters = ['period' => lf_text($_GET, 'periode'), 'status' => lf_text($_GET, 'statut'), 'dossier' => lf_text($_GET, 'dossier')];
    $cabinet = $user['role'] === 'cabinet';
    $page = max(1, min(100000, (int) lf_text($_GET, 'page')));
    $where = [];
    $params = [];
    if (!$cabinet || ctype_digit($filters['dossier'])) {
        $where[] = 'p.dossier_id = ?';
        $params[] = $cabinet ? $filters['dossier'] : $user['dossier_id'];
    }
    if (lf_period($filters['period'])) {
        $where[] = 'p.periode = ?';
        $params[] = $filters['period'];
    }
    if (isset(lf_statuses()[$filters['status']])) {
        $where[] = 'p.statut = ?';
        $params[] = $filters['status'];
    }
    $offset = ($page - 1) * LF_PAGE_SIZE;
    $limit = LF_PAGE_SIZE + 1;
    $pieces = lf_query('SELECT p.id, p.nom, p.octets, p.periode, p.categorie, p.statut, p.cree_le, d.nom AS dossier
        FROM lf_pieces p INNER JOIN lf_dossiers d ON p.dossier_id = d.id'
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . " ORDER BY p.cree_le DESC, p.id DESC LIMIT $limit OFFSET $offset", $params)->fetchAll();
    $dossiers = $cabinet ? lf_query('SELECT id, nom FROM lf_dossiers ORDER BY nom, id')->fetchAll() : [];
    $dossier = $cabinet ? null : lf_query('SELECT nom FROM lf_dossiers WHERE id = ?', [$user['dossier_id']])->fetchColumn();
    return compact('pieces', 'dossiers', 'dossier', 'filters', 'page');
}
