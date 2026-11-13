<?php

declare(strict_types=1);

require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/store-files.php';
require_once __DIR__ . '/store-mysql.php';

/**
 * Façade de stockage : « files » (défaut, sans SQL) ou « mysql » (option).
 * Chaque appel choisit son implémentation ; aucune requête SQL n'est faite en mode fichiers.
 */
function lf_store_backend(): string
{
    $backend = config('portal.backend', 'files');
    if (!in_array($backend, ['files', 'mysql'], true)) {
        throw new RuntimeException('Mode de stockage du portail inconnu.');
    }

    return $backend;
}

function lf_files_mode(): bool
{
    return lf_store_backend() === 'files';
}

function lf_portal_dir(bool $create = true): string
{
    return lf_files_dir($create);
}

function lf_account_by_id(string $id): ?array
{
    return lf_files_mode() ? lf_files_account_by_id($id) : lf_sql_account_by_id($id);
}

function lf_account_by_email(string $email): ?array
{
    return lf_files_mode() ? lf_files_account_by_email($email) : lf_sql_account_by_email($email);
}

function lf_dossier_create(string $name): string
{
    return lf_files_mode() ? lf_files_dossier_create($name) : lf_sql_dossier_create($name);
}

function lf_dossiers(): array
{
    return lf_files_mode() ? lf_files_dossiers() : lf_sql_dossiers();
}

function lf_dossier_name(string $id): ?string
{
    return lf_files_mode() ? lf_files_dossier_name($id) : lf_sql_dossier_name($id);
}

function lf_account_create(string $email, ?string $dossierId, bool $cabinet, string $tokenHash, int $expire): string
{
    return lf_files_mode()
        ? lf_files_account_create($email, $dossierId, $cabinet, $tokenHash, $expire)
        : lf_sql_account_create($email, $dossierId, $cabinet, $tokenHash, $expire);
}

function lf_account_activate(string $tokenHash, string $passwordHash): bool
{
    return lf_files_mode()
        ? lf_files_account_activate($tokenHash, $passwordHash)
        : lf_sql_account_activate($tokenHash, $passwordHash);
}

function lf_account_reset(string $email, string $tokenHash, int $expire): bool
{
    return lf_files_mode()
        ? lf_files_account_reset($email, $tokenHash, $expire)
        : lf_sql_account_reset($email, $tokenHash, $expire);
}

function lf_account_revoke(string $email): bool
{
    return lf_files_mode() ? lf_files_account_revoke($email) : lf_sql_account_revoke($email);
}

function lf_piece_usage(string $dossierId): int
{
    return lf_files_mode() ? lf_files_piece_usage($dossierId) : lf_sql_piece_usage($dossierId);
}

function lf_piece_create(array $piece): void
{
    if (lf_files_mode()) {
        lf_files_piece_create($piece);
    } else {
        lf_sql_piece_create($piece);
    }
}

function lf_piece_get(string $id, ?string $dossierId = null): ?array
{
    return lf_files_mode() ? lf_files_piece_get($id, $dossierId) : lf_sql_piece_get($id, $dossierId);
}

function lf_piece_status(string $id, string $status, string $accountId): bool
{
    return lf_files_mode()
        ? lf_files_piece_status($id, $status, $accountId)
        : lf_sql_piece_status($id, $status, $accountId);
}

function lf_piece_event(string $pieceId, string $accountId, string $action, ?string $status = null): void
{
    if (lf_files_mode()) {
        lf_files_piece_event($pieceId, $accountId, $action, $status);
    } else {
        lf_sql_piece_event($pieceId, $accountId, $action, $status);
    }
}

/** @return array{pieces: array<int, array<string, mixed>>, more: bool} */
function lf_pieces(array $filters, int $page, int $pageSize): array
{
    return lf_files_mode()
        ? lf_files_pieces($filters, $page, $pageSize)
        : lf_sql_pieces($filters, $page, $pageSize);
}

function lf_rate_hit(string $key, int $window, int $max): bool
{
    return lf_files_mode()
        ? lf_files_rate_hit($key, $window, $max)
        : lf_sql_rate_hit($key, $window, $max);
}
