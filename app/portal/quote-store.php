<?php

declare(strict_types=1);

require_once __DIR__ . '/paths.php';

/** Le défaut est stable pour la CLI et le serveur web, sans exposer les devis. */
function lf_quote_dir(bool $create = true): string
{
    $configured = config('quote_storage_dir', '');

    return lf_private_dir(is_string($configured) ? $configured : '', 'la-fiduciaire-devis', $create);
}

function lf_quote_read(string $path): array
{
    $raw = lf_private_read($path, 262144);
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
    lf_private_write($path, $payload);
}

function lf_quote_locked(callable $operation): mixed
{
    return lf_private_locked(lf_quote_dir(), $operation);
}

/** Une seule fenêtre conservée, sans IP en clair. Le sel change à chaque fenêtre. */
function lf_quote_file_rate(string $address): bool
{
    return lf_quote_locked(static function (string $dir) use ($address): bool {
        $path = $dir . DIRECTORY_SEPARATOR . '.limites.json';
        $window = intdiv(time(), 900);
        $state = (file_exists($path) || is_link($path)) ? lf_quote_read($path) : [];
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
        $path = $dir . DIRECTORY_SEPARATOR . $id . '.json';
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

/** Liste privée pour le cabinet : fichiers JSON ou table MySQL optionnelle. */
function lf_quote_list(int $limit = 50): array
{
    $limit = max(1, min(200, $limit));
    if (config('quote_backend', 'files') === 'mysql') {
        if (!function_exists('lf_query')) {
            throw new RuntimeException('Connexion SQL indisponible.');
        }

        return lf_query('SELECT id, cree_le, nom, email, telephone, activite, canton, besoin, message
            FROM demandes_devis ORDER BY cree_le DESC, id DESC LIMIT ' . $limit)->fetchAll();
    }

    try {
        $dir = lf_quote_dir(false);
    } catch (Throwable) {
        return [];
    }

    $found = [];
    foreach (new DirectoryIterator($dir) as $file) {
        if ($file->isDot() || $file->isLink() || !$file->isFile()
            || preg_match('/^([a-f0-9]{32})\.json$/D', $file->getFilename()) !== 1) {
            continue;
        }
        try {
            $record = lf_quote_read($file->getPathname());
        } catch (Throwable) {
            continue;
        }
        $found[] = ['mtime' => $file->getMTime(), 'record' => $record];
    }
    usort($found, static function (array $a, array $b): int {
        return strcmp((string) ($b['record']['recu_le'] ?? ''), (string) ($a['record']['recu_le'] ?? ''))
            ?: ($b['mtime'] <=> $a['mtime']);
    });

    return array_values(array_map(
        static fn (array $entry): array => $entry['record'],
        array_slice($found, 0, $limit)
    ));
}
