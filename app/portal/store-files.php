<?php

declare(strict_types=1);

require_once __DIR__ . '/paths.php';

/** Stockage local privé : JSON verrouillé + fichiers binaires hors racine web. */
function lf_files_dir(bool $create = true): string
{
    $configured = config('portal.storage_dir', '');

    return lf_private_dir(is_string($configured) ? $configured : '', 'la-fiduciaire-portail', $create);
}

function lf_files_default_state(): array
{
    return [
        'version'      => 1,
        'next_dossier' => 1,
        'next_compte'  => 1,
        'dossiers'     => [],
        'comptes'      => [],
        'pieces'       => [],
        'evenements'   => [],
        'limites'      => [],
    ];
}

function lf_files_load(string $dir): array
{
    $raw = lf_private_read($dir . DIRECTORY_SEPARATOR . 'state.json');
    if ($raw === null) {
        return lf_files_default_state();
    }

    $state = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    if (!is_array($state) || ($state['version'] ?? null) !== 1) {
        throw new RuntimeException('État du portail invalide.');
    }
    foreach (['dossiers', 'comptes', 'pieces', 'evenements'] as $key) {
        if (!is_array($state[$key] ?? null)) {
            throw new RuntimeException('État du portail incomplet.');
        }
    }
    if (!is_int($state['next_dossier'] ?? null) || !is_int($state['next_compte'] ?? null)
        || !is_array($state['limites'] ?? null)) {
        throw new RuntimeException('Compteurs du portail invalides.');
    }

    return $state;
}

function lf_files_save(string $dir, array $state): void
{
    $payload = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    lf_private_write($dir . DIRECTORY_SEPARATOR . 'state.json', $payload);
}

function lf_files_find(array $state, string $collection, string $id): ?array
{
    foreach ($state[$collection] as $row) {
        if (is_array($row) && (string) ($row['id'] ?? '') === $id) {
            return $row;
        }
    }

    return null;
}

function lf_files_account_by_id(string $id): ?array
{
    $dir = lf_files_dir();
    $state = lf_files_load($dir);
    $row = lf_files_find($state, 'comptes', $id);

    return is_array($row) && ($row['actif'] ?? false) === true ? $row : null;
}

function lf_files_account_by_email(string $email): ?array
{
    $dir = lf_files_dir();
    $state = lf_files_load($dir);
    foreach ($state['comptes'] as $account) {
        if (is_array($account) && ($account['actif'] ?? false) === true
            && strtolower((string) ($account['email'] ?? '')) === $email) {
            return $account;
        }
    }

    return null;
}

function lf_files_dossier_create(string $name): string
{
    $dir = lf_files_dir();

    return lf_private_locked($dir, static function (string $dir) use ($name): string {
        $state = lf_files_load($dir);
        $id = (string) $state['next_dossier'];
        $state['next_dossier']++;
        $state['dossiers'][] = ['id' => $id, 'nom' => $name, 'cree_le' => gmdate(DATE_ATOM)];
        lf_files_save($dir, $state);

        return $id;
    });
}

function lf_files_dossiers(): array
{
    $state = lf_files_load(lf_files_dir());
    $rows = array_values(array_filter($state['dossiers'], 'is_array'));
    usort($rows, static fn (array $a, array $b): int => strcmp((string) $a['nom'], (string) $b['nom']));

    return $rows;
}

function lf_files_dossier_name(string $id): ?string
{
    $row = lf_files_find(lf_files_load(lf_files_dir()), 'dossiers', $id);

    return is_array($row) ? (string) $row['nom'] : null;
}

function lf_files_account_create(string $email, ?string $dossierId, bool $cabinet, string $tokenHash, int $expire): string
{
    $dir = lf_files_dir();

    return lf_private_locked($dir, static function (string $dir) use ($email, $dossierId, $cabinet, $tokenHash, $expire): string {
        $state = lf_files_load($dir);
        foreach ($state['comptes'] as $account) {
            if (strtolower((string) ($account['email'] ?? '')) === $email) {
                throw new DomainException('Un compte existe déjà pour cette adresse.');
            }
        }
        if (!$cabinet && ($dossierId === null || lf_files_find($state, 'dossiers', $dossierId) === null)) {
            throw new DomainException('Dossier introuvable.');
        }

        $id = (string) $state['next_compte'];
        $state['next_compte']++;
        $state['comptes'][] = [
            'id'                => $id,
            'dossier_id'        => $cabinet ? null : $dossierId,
            'email'             => $email,
            'role'              => $cabinet ? 'cabinet' : 'client',
            'password_hash'     => null,
            'activation_hash'   => $tokenHash,
            'activation_expire' => $expire,
            'version_session'   => 1,
            'actif'             => true,
            'cree_le'           => gmdate(DATE_ATOM),
        ];
        lf_files_save($dir, $state);

        return $id;
    });
}

function lf_files_account_activate(string $tokenHash, string $passwordHash): bool
{
    $dir = lf_files_dir();

    return lf_private_locked($dir, static function (string $dir) use ($tokenHash, $passwordHash): bool {
        $state = lf_files_load($dir);
        $now = time();
        foreach ($state['comptes'] as $index => $account) {
            if (!is_array($account) || ($account['actif'] ?? false) !== true
                || !hash_equals((string) ($account['activation_hash'] ?? ''), $tokenHash)) {
                continue;
            }
            if ((int) ($account['activation_expire'] ?? 0) <= $now) {
                return false;
            }
            $state['comptes'][$index]['password_hash'] = $passwordHash;
            $state['comptes'][$index]['activation_hash'] = null;
            $state['comptes'][$index]['activation_expire'] = null;
            $state['comptes'][$index]['version_session'] = (int) $account['version_session'] + 1;
            lf_files_save($dir, $state);

            return true;
        }

        return false;
    });
}

function lf_files_account_reset(string $email, string $tokenHash, int $expire): bool
{
    $dir = lf_files_dir();

    return lf_private_locked($dir, static function (string $dir) use ($email, $tokenHash, $expire): bool {
        $state = lf_files_load($dir);
        foreach ($state['comptes'] as $index => $account) {
            if (!is_array($account) || ($account['actif'] ?? false) !== true
                || strtolower((string) ($account['email'] ?? '')) !== $email) {
                continue;
            }
            $state['comptes'][$index]['activation_hash'] = $tokenHash;
            $state['comptes'][$index]['activation_expire'] = $expire;
            $state['comptes'][$index]['password_hash'] = null;
            $state['comptes'][$index]['version_session'] = (int) $account['version_session'] + 1;
            lf_files_save($dir, $state);

            return true;
        }

        return false;
    });
}

function lf_files_account_revoke(string $email): bool
{
    $dir = lf_files_dir();

    return lf_private_locked($dir, static function (string $dir) use ($email): bool {
        $state = lf_files_load($dir);
        foreach ($state['comptes'] as $index => $account) {
            if (!is_array($account) || ($account['actif'] ?? false) !== true
                || strtolower((string) ($account['email'] ?? '')) !== $email) {
                continue;
            }
            $state['comptes'][$index]['actif'] = false;
            $state['comptes'][$index]['activation_hash'] = null;
            $state['comptes'][$index]['activation_expire'] = null;
            $state['comptes'][$index]['password_hash'] = null;
            $state['comptes'][$index]['version_session'] = (int) $account['version_session'] + 1;
            lf_files_save($dir, $state);

            return true;
        }

        return false;
    });
}


function lf_files_piece_usage(string $dossierId): int
{
    $state = lf_files_load(lf_files_dir());
    $used = 0;
    foreach ($state['pieces'] as $piece) {
        if (is_array($piece) && (string) ($piece['dossier_id'] ?? '') === $dossierId) {
            $used += (int) ($piece['octets'] ?? 0);
        }
    }

    return $used;
}

function lf_files_piece_create(array $piece): void
{
    $dir = lf_files_dir();

    lf_private_locked($dir, static function (string $dir) use ($piece): void {
        $state = lf_files_load($dir);
        if (lf_files_find($state, 'pieces', (string) $piece['id']) !== null) {
            throw new RuntimeException('Pièce déjà enregistrée.');
        }
        if (lf_files_find($state, 'dossiers', (string) $piece['dossier_id']) === null) {
            throw new DomainException('Dossier introuvable.');
        }
        $used = 0;
        foreach ($state['pieces'] as $row) {
            if (is_array($row) && (string) ($row['dossier_id'] ?? '') === (string) $piece['dossier_id']) {
                $used += (int) ($row['octets'] ?? 0);
            }
        }
        if ($used + (int) $piece['octets'] > LF_DOSSIER_QUOTA) {
            throw new DomainException('Le dossier a atteint sa capacité. Contactez votre interlocuteur.');
        }
        $state['pieces'][] = $piece + ['statut' => 'recu', 'cree_le' => gmdate(DATE_ATOM)];
        $state['evenements'][] = [
            'piece_id'  => (string) $piece['id'],
            'compte_id' => (string) $piece['compte_id'],
            'action'    => 'depot',
            'statut'    => 'recu',
            'cree_le'   => gmdate(DATE_ATOM),
        ];
        if (count($state['evenements']) > 5000) {
            $state['evenements'] = array_slice($state['evenements'], -5000);
        }
        lf_files_save($dir, $state);
    });
}

function lf_files_piece_get(string $id, ?string $dossierId = null): ?array
{
    $dir = lf_files_dir();
    $state = lf_files_load($dir);
    $row = lf_files_find($state, 'pieces', $id);
    if ($row === null) {
        return null;
    }
    if ($dossierId !== null && (string) $row['dossier_id'] !== $dossierId) {
        return null;
    }
    $dossier = lf_files_find($state, 'dossiers', (string) $row['dossier_id']);
    $row['dossier'] = is_array($dossier) ? (string) $dossier['nom'] : '';

    return $row;
}

function lf_files_piece_status(string $id, string $status, string $accountId): bool
{
    $dir = lf_files_dir();

    return lf_private_locked($dir, static function (string $dir) use ($id, $status, $accountId): bool {
        $state = lf_files_load($dir);
        foreach ($state['pieces'] as $index => $piece) {
            if (!is_array($piece) || (string) ($piece['id'] ?? '') !== $id) {
                continue;
            }
            if ((string) ($piece['statut'] ?? '') === $status) {
                return false;
            }
            $state['pieces'][$index]['statut'] = $status;
            $state['evenements'][] = [
                'piece_id'  => $id,
                'compte_id' => $accountId,
                'action'    => 'statut',
                'statut'    => $status,
                'cree_le'   => gmdate(DATE_ATOM),
            ];
            if (count($state['evenements']) > 5000) {
                $state['evenements'] = array_slice($state['evenements'], -5000);
            }
            lf_files_save($dir, $state);

            return true;
        }

        return false;
    });
}

function lf_files_piece_event(string $pieceId, string $accountId, string $action, ?string $status = null): void
{
    $dir = lf_files_dir();

    lf_private_locked($dir, static function (string $dir) use ($pieceId, $accountId, $action, $status): void {
        $state = lf_files_load($dir);
        $state['evenements'][] = [
            'piece_id'  => $pieceId,
            'compte_id' => $accountId,
            'action'    => $action,
            'statut'    => $status,
            'cree_le'   => gmdate(DATE_ATOM),
        ];
        if (count($state['evenements']) > 5000) {
            $state['evenements'] = array_slice($state['evenements'], -5000);
        }
        lf_files_save($dir, $state);
    });
}

function lf_files_pieces(array $filters, int $page, int $pageSize): array
{
    $state = lf_files_load(lf_files_dir());
    $rows = [];
    foreach ($state['pieces'] as $piece) {
        if (!is_array($piece)) {
            continue;
        }
        if ($filters['dossier'] !== null && (string) ($piece['dossier_id'] ?? '') !== (string) $filters['dossier']) {
            continue;
        }
        if ($filters['period'] !== null && (string) ($piece['periode'] ?? '') !== $filters['period']) {
            continue;
        }
        if ($filters['status'] !== null && (string) ($piece['statut'] ?? '') !== $filters['status']) {
            continue;
        }
        $dossier = lf_files_find($state, 'dossiers', (string) $piece['dossier_id']);
        $piece['dossier'] = is_array($dossier) ? (string) $dossier['nom'] : '';
        $rows[] = $piece;
    }
    usort($rows, static function (array $a, array $b): int {
        $compare = strcmp((string) ($b['cree_le'] ?? ''), (string) ($a['cree_le'] ?? ''));

        return $compare !== 0 ? $compare : strcmp((string) $b['id'], (string) $a['id']);
    });
    $offset = max(0, ($page - 1) * $pageSize);

    return [
        'pieces' => array_slice($rows, $offset, $pageSize),
        'more'   => count($rows) > $offset + $pageSize,
    ];
}

function lf_files_rate_hit(string $key, int $window, int $max): bool
{
    $dir = lf_files_dir();

    return lf_private_locked($dir, static function (string $dir) use ($key, $window, $max): bool {
        $state = lf_files_load($dir);
        $limits = [];
        foreach ($state['limites'] as $stored => $count) {
            if (is_string($stored) && str_ends_with($stored, '|' . $window) && is_int($count)) {
                $limits[$stored] = $count;
            }
        }
        $k = $key . '|' . $window;
        $count = $limits[$k] ?? 0;
        if ($count >= $max) {
            $state['limites'] = $limits;
            lf_files_save($dir, $state);

            return false;
        }
        $limits[$k] = $count + 1;
        if (count($limits) > 2048) {
            $limits = array_slice($limits, -2048, null, true);
        }
        $state['limites'] = $limits;
        lf_files_save($dir, $state);

        return true;
    });
}
