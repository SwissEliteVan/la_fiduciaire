<?php

declare(strict_types=1);

/** Le défaut est stable pour la CLI et le serveur web, sans exposer les devis. */
function lf_quote_dir(bool $create = true): string
{
    $project = realpath(APP_ROOT);
    $docroot = PHP_SAPI === 'cli' ? $project : realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($project === false || $docroot === false) {
        throw new RuntimeException('Racine de stockage non déterminée.');
    }
    $default = dirname($project) . '/.la-fiduciaire-devis-' . substr(hash('sha256', $project), 0, 12);
    $path = config('quote_storage_dir') ?: $default;
    if (!is_string($path) || !str_starts_with($path, '/') || str_contains($path, "\0")) {
        throw new RuntimeException('Chemin absolu requis.');
    }
    $path = rtrim($path, '/');
    $name = basename($path);
    $parent = realpath(dirname($path));
    if ($parent === false || $name === '' || $name === '.' || $name === '..') {
        throw new RuntimeException('Parent du stockage indisponible.');
    }
    $candidate = rtrim($parent, '/') . '/' . $name;
    foreach ([$project, $docroot] as $root) {
        if ($root === '/' || $candidate === $root || str_starts_with($candidate, $root . '/') || str_starts_with($root, $candidate . '/')) {
            throw new RuntimeException('Le stockage doit être hors racine publique et hors projet.');
        }
    }
    if (is_link($candidate)) {
        throw new RuntimeException('Lien symbolique refusé.');
    }
    if (!is_dir($candidate) && $create && !@mkdir($candidate, 0700) && !is_dir($candidate)) {
        throw new RuntimeException('Création du stockage impossible.');
    }
    clearstatcache(true, $candidate);
    $mode = @fileperms($candidate);
    if (is_link($candidate) || !is_dir($candidate) || $mode === false || ($mode & 0077) !== 0 || !is_readable($candidate)) {
        throw new RuntimeException('Stockage absent, inaccessible ou permissions trop larges.');
    }
    return $candidate;
}

function lf_quote_read(string $path): array
{
    $mode = @fileperms($path);
    if (is_link($path) || !is_file($path) || $mode === false || ($mode & 0077) !== 0) {
        throw new RuntimeException('Fichier privé inaccessible.');
    }
    $raw = @file_get_contents($path, false, null, 0, 262144);
    $decoded = is_string($raw) ? json_decode($raw, true, 32, JSON_THROW_ON_ERROR) : null;
    if (!is_array($decoded)) {
        throw new RuntimeException('Fichier privé illisible.');
    }
    return $decoded;
}

/** Doit être appelé sous le verrou du dossier. Ne publie jamais de fichier partiel. */
function lf_quote_write(string $path, array $value): void
{
    $payload = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    $temporary = dirname($path) . '/.tmp-' . bin2hex(random_bytes(16));
    $mask = umask(0077);
    try {
        $file = @fopen($temporary, 'xb');
        if ($file === false) {
            throw new RuntimeException('Écriture privée impossible.');
        }
        try {
            if (@fwrite($file, $payload) !== strlen($payload) || !@fflush($file) || !@fsync($file)) {
                throw new RuntimeException('Écriture incomplète.');
            }
        } finally {
            fclose($file);
        }
        if (!@rename($temporary, $path)) {
            throw new RuntimeException('Publication du fichier impossible.');
        }
    } finally {
        umask($mask);
        if (is_file($temporary)) {
            @unlink($temporary);
        }
    }
}

function lf_quote_locked(callable $operation): mixed
{
    $dir = lf_quote_dir();
    $path = $dir . '/.verrou';
    if (is_link($path) || (file_exists($path) && !is_file($path))) {
        throw new RuntimeException('Verrou de stockage invalide.');
    }
    $mask = umask(0077);
    try {
        $lock = @fopen($path, 'c+b');
    } finally {
        umask($mask);
    }
    if ($lock === false) {
        throw new RuntimeException('Verrou de stockage indisponible.');
    }
    try {
        $stat = fstat($lock);
        if ($stat === false || ($stat['mode'] & 0170077) !== 0100000 || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('Verrou de stockage indisponible.');
        }
        return $operation($dir);
    } finally {
        fclose($lock);
    }
}

/** Une seule fenêtre conservée, sans IP en clair. Le sel change à chaque fenêtre. */
function lf_quote_file_rate(string $address): bool
{
    return lf_quote_locked(static function (string $dir) use ($address): bool {
        $path = $dir . '/.limites.json';
        $window = intdiv(time(), 900);
        $state = file_exists($path) || is_link($path) ? lf_quote_read($path) : [];
        if (($state['window'] ?? null) !== $window) {
            $state = ['window' => $window, 'salt' => bin2hex(random_bytes(32)), 'counts' => []];
        }
        if (!is_string($state['salt'] ?? null) || !is_array($state['counts'] ?? null)) {
            throw new RuntimeException('Compteur de limitation illisible.');
        }
        $key = hash_hmac('sha256', $address, $state['salt']);
        $count = $state['counts'][$key] ?? 0;
        if ($count >= 10 || ($count === 0 && count($state['counts']) >= 1024)) {
            return false;
        }
        $state['counts'][$key] = $count + 1;
        lf_quote_write($path, $state);
        return true;
    });
}

function lf_quote_file_save(string $id, array $values): void
{
    if (!preg_match('/^[a-f0-9]{32}$/D', $id)) {
        throw new RuntimeException('Identifiant de devis invalide.');
    }
    lf_quote_locked(static function (string $dir) use ($id, $values): void {
        $path = $dir . '/' . $id . '.json';
        if (file_exists($path) || is_link($path)) {
            $record = lf_quote_read($path);
            if (($record['id'] ?? null) !== $id) {
                throw new RuntimeException('Enregistrement de devis invalide.');
            }
            return;
        }
        lf_quote_write($path, ['id' => $id, 'recu_le' => gmdate(DATE_ATOM)] + $values);
    });
}
