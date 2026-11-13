<?php

declare(strict_types=1);

/**
 * Chemins privés, portables Windows/POSIX.
 *
 * Les contrôles de permissions POSIX (0700/0600) restent stricts sur Unix.
 * Sous Windows, PHP ne restitue pas les ACL NTFS : on vérifie alors le
 * placement hors racine web, l'absence de lien et l'accessibilité en écriture.
 * La production Hostinger reste sur Unix : aucun contrôle n'y est retiré.
 */

function lf_windows(): bool
{
    return DIRECTORY_SEPARATOR === '\\';
}

function lf_absolute_path(string $path): bool
{
    return $path !== ''
        && !str_contains($path, "\0")
        && (str_starts_with($path, '/')
            || str_starts_with($path, '\\\\')
            || preg_match('~^[A-Za-z]:[\\\\/]~D', $path) === 1);
}

function lf_norm_path(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $prefix = '';

    if (preg_match('~^([A-Za-z]):~', $path, $match) === 1) {
        $prefix = strtoupper($match[1]) . ':';
        $path = substr($path, 2);
    } elseif (str_starts_with($path, '//')) {
        $prefix = '//';
        $path = ltrim($path, '/');
    }

    $parts = [];
    foreach (explode('/', $path) as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            array_pop($parts);
            continue;
        }
        $parts[] = $part;
    }

    return $prefix . '/' . implode('/', $parts);
}

function lf_path_key(string $path): string
{
    $normalized = lf_norm_path($path);

    return lf_windows() ? strtolower($normalized) : $normalized;
}

/** Vrai si $path est égal à $root ou contenu dedans. */
function lf_path_within(string $path, string $root): bool
{
    $candidate = rtrim(lf_path_key($path), '/');
    $base = rtrim(lf_path_key($root), '/');

    return $candidate === $base || str_starts_with($candidate, $base . '/');
}

/** Vrai si l'un des deux chemins contient l'autre. */
function lf_paths_overlap(string $a, string $b): bool
{
    return lf_path_within($a, $b) || lf_path_within($b, $a);
}

function lf_chmod_private(string $path, int $mode = 0600): void
{
    @chmod($path, $mode);
}

function lf_private_file_ok(string $path): bool
{
    if (is_link($path) || !is_file($path)) {
        return false;
    }

    if (!lf_windows()) {
        $mode = @fileperms($path);
        if ($mode === false || ($mode & 0077) !== 0) {
            return false;
        }
    }

    return is_readable($path);
}

/**
 * Dossier privé absolu, hors racine web et hors dépôt.
 * Créé en 0700 sur Unix ; permissions vérifiées à chaque appel.
 */
function lf_private_dir(string $configured, string $prefix, bool $create = true): string
{
    $project = realpath(APP_ROOT);
    $docroot = PHP_SAPI === 'cli' ? $project : realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($project === false || $docroot === false) {
        throw new RuntimeException('Racine de stockage non déterminée.');
    }

    $default = dirname($project) . DIRECTORY_SEPARATOR . '.' . $prefix
        . '-' . substr(hash('sha256', $project), 0, 12);
    $path = $configured !== '' ? $configured : $default;

    if (!lf_absolute_path($path)) {
        throw new RuntimeException('Chemin absolu requis.');
    }

    $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    $name = basename($path);
    $parent = realpath(dirname($path));
    if ($parent === false || $name === '' || $name === '.' || $name === '..') {
        throw new RuntimeException('Parent du stockage indisponible.');
    }

    $candidate = $parent . DIRECTORY_SEPARATOR . $name;
    foreach ([$project, $docroot] as $root) {
        if ($root === DIRECTORY_SEPARATOR || lf_paths_overlap($candidate, $root)) {
            throw new RuntimeException('Le stockage doit être hors racine publique et hors projet.');
        }
    }

    if (file_exists($candidate) && is_link($candidate)) {
        throw new RuntimeException('Lien symbolique refusé.');
    }

    if (!is_dir($candidate)) {
        if (!$create) {
            throw new RuntimeException('Stockage absent.');
        }
        $mask = umask(0077);
        try {
            $created = @mkdir($candidate, 0700);
        } finally {
            umask($mask);
        }
        if (!$created && !is_dir($candidate)) {
            throw new RuntimeException('Création du stockage impossible.');
        }
        lf_chmod_private($candidate, 0700);
    }

    clearstatcache(true, $candidate);
    if (is_link($candidate) || !is_dir($candidate) || !is_writable($candidate)) {
        throw new RuntimeException('Stockage absent, inaccessible ou non inscriptible.');
    }
    if (!lf_windows()) {
        $mode = @fileperms($candidate);
        if ($mode === false || ($mode & 0077) !== 0) {
            throw new RuntimeException('Permissions du stockage trop larges.');
        }
    }

    $resolved = realpath($candidate);

    return $resolved === false ? $candidate : $resolved;
}

/** Écriture atomique d'un fichier privé, sans publication partielle. */
function lf_private_write(string $path, string $payload): void
{
    $temporary = dirname($path) . DIRECTORY_SEPARATOR . '.tmp-' . bin2hex(random_bytes(16));
    $mask = umask(0077);

    try {
        $file = @fopen($temporary, 'xb');
        if ($file === false) {
            throw new RuntimeException('Écriture privée impossible.');
        }
        try {
            if (@fwrite($file, $payload) !== strlen($payload) || !@fflush($file)) {
                throw new RuntimeException('Écriture incomplète.');
            }
            if (function_exists('fsync')) {
                @fsync($file);
            }
        } finally {
            fclose($file);
        }
        lf_chmod_private($temporary);
        if (!@rename($temporary, $path)) {
            // Windows ne remplace pas toujours un fichier existant via rename().
            if (!is_file($path) || !@unlink($path) || !@rename($temporary, $path)) {
                throw new RuntimeException('Publication du fichier impossible.');
            }
        }
        lf_chmod_private($path);
    } finally {
        umask($mask);
        if (is_file($temporary)) {
            @unlink($temporary);
        }
    }
}

function lf_private_read(string $path, int $maxBytes = 16777216): ?string
{
    if (!file_exists($path)) {
        return null;
    }
    if (!lf_private_file_ok($path)) {
        throw new RuntimeException('Fichier privé inaccessible.');
    }

    $size = @filesize($path);
    if ($size === false || $size > $maxBytes) {
        throw new RuntimeException('Fichier privé trop volumineux.');
    }

    $raw = @file_get_contents($path, false, null, 0, $maxBytes);

    return is_string($raw) ? $raw : null;
}

/** Exécute une opération sous verrou exclusif du dossier privé. */
function lf_private_locked(string $dir, callable $operation): mixed
{
    $path = $dir . DIRECTORY_SEPARATOR . 'state.lock';
    if (is_link($path) || (file_exists($path) && !is_file($path))) {
        throw new RuntimeException('Verrou de stockage invalide.');
    }

    $mask = umask(0077);
    try {
        $handle = @fopen($path, 'c+b');
    } finally {
        umask($mask);
    }
    if ($handle === false) {
        throw new RuntimeException('Verrou de stockage indisponible.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Verrou de stockage indisponible.');
        }
        lf_chmod_private($path);

        return $operation($dir);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
