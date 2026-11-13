<?php

declare(strict_types=1);

/** Implémentation MySQL du portail (option historique, inchangée sur le fond). */

function lf_sql_account_by_id(string $id): ?array
{
    $row = lf_query('SELECT id, dossier_id, email, role, password_hash, version_session, actif
        FROM lf_comptes WHERE id = ? AND actif = 1', [$id])->fetch();

    return is_array($row) ? $row : null;
}

function lf_sql_account_by_email(string $email): ?array
{
    $row = lf_query('SELECT id, dossier_id, email, role, password_hash, version_session, actif
        FROM lf_comptes WHERE email = ? AND actif = 1', [$email])->fetch();

    return is_array($row) ? $row : null;
}

function lf_sql_dossier_create(string $name): string
{
    lf_query('INSERT INTO lf_dossiers (nom) VALUES (?)', [$name]);
    $id = lf_db()->lastInsertId();

    return (string) $id;
}

function lf_sql_dossiers(): array
{
    return lf_query('SELECT id, nom FROM lf_dossiers ORDER BY nom, id')->fetchAll();
}

function lf_sql_dossier_name(string $id): ?string
{
    $name = lf_query('SELECT nom FROM lf_dossiers WHERE id = ?', [$id])->fetchColumn();

    return $name === false ? null : (string) $name;
}

function lf_sql_account_create(string $email, ?string $dossierId, bool $cabinet, string $tokenHash, int $expire): string
{
    $db = lf_db();
    $db->beginTransaction();
    try {
        if (!$cabinet && ($dossierId === null || !lf_query('SELECT id FROM lf_dossiers WHERE id = ?', [$dossierId])->fetchColumn())) {
            throw new DomainException('Dossier introuvable.');
        }
        lf_query('INSERT INTO lf_comptes (dossier_id, email, role, activation_hash, activation_expire)
            VALUES (?, ?, ?, ?, FROM_UNIXTIME(?))',
            [$cabinet ? null : $dossierId, $email, $cabinet ? 'cabinet' : 'client', $tokenHash, $expire]);
        $id = (string) $db->lastInsertId();
        $db->commit();

        return $id;
    } catch (PDOException $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ((string) $error->getCode() === '23000') {
            throw new DomainException('Un compte existe déjà pour cette adresse.');
        }
        throw $error;
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}

function lf_sql_account_activate(string $tokenHash, string $passwordHash): bool
{
    $id = lf_query('SELECT id FROM lf_comptes WHERE activation_hash = ? AND activation_expire > UTC_TIMESTAMP() AND actif = 1',
        [$tokenHash])->fetchColumn();
    if (!$id) {
        return false;
    }

    return lf_query('UPDATE lf_comptes SET password_hash = ?, activation_hash = NULL, activation_expire = NULL,
        version_session = version_session + 1 WHERE id = ? AND activation_hash = ? AND activation_expire > UTC_TIMESTAMP() AND actif = 1',
        [$passwordHash, $id, $tokenHash])->rowCount() === 1;
}

function lf_sql_account_reset(string $email, string $tokenHash, int $expire): bool
{
    return lf_query('UPDATE lf_comptes SET activation_hash = ?, activation_expire = FROM_UNIXTIME(?),
        password_hash = NULL, version_session = version_session + 1 WHERE email = ? AND actif = 1',
        [$tokenHash, $expire, $email])->rowCount() === 1;
}

function lf_sql_account_revoke(string $email): bool
{
    return lf_query('UPDATE lf_comptes SET actif = 0, activation_hash = NULL, activation_expire = NULL,
        password_hash = NULL, version_session = version_session + 1 WHERE email = ? AND actif = 1',
        [$email])->rowCount() === 1;
}

function lf_sql_piece_usage(string $dossierId): int
{
    return (int) lf_query('SELECT COALESCE(SUM(octets), 0) FROM lf_pieces WHERE dossier_id = ?', [$dossierId])->fetchColumn();
}

function lf_sql_piece_create(array $piece): void
{
    $db = lf_db();
    $db->beginTransaction();
    try {
        lf_query('SELECT id FROM lf_dossiers WHERE id = ? FOR UPDATE', [$piece['dossier_id']])->fetchColumn();
        $used = (int) lf_query('SELECT COALESCE(SUM(octets), 0) FROM lf_pieces WHERE dossier_id = ?',
            [$piece['dossier_id']])->fetchColumn();
        if ($used + (int) $piece['octets'] > LF_DOSSIER_QUOTA) {
            throw new DomainException('Le dossier a atteint sa capacité. Contactez votre interlocuteur.');
        }
        lf_query('INSERT INTO lf_pieces (id, dossier_id, compte_id, nom, mime, octets, periode, categorie)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
            $piece['id'], $piece['dossier_id'], $piece['compte_id'], $piece['nom'], $piece['mime'],
            $piece['octets'], $piece['periode'], $piece['categorie'],
        ]);
        lf_query('INSERT INTO lf_evenements_pieces (piece_id, compte_id, action, statut) VALUES (?, ?, ?, ?)',
            [$piece['id'], $piece['compte_id'], 'depot', 'recu']);
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}

function lf_sql_piece_get(string $id, ?string $dossierId = null): ?array
{
    $sql = 'SELECT p.id, p.dossier_id, p.nom, p.mime, p.octets, p.periode, p.categorie, p.statut, p.cree_le, d.nom AS dossier
        FROM lf_pieces p INNER JOIN lf_dossiers d ON p.dossier_id = d.id WHERE p.id = ?';
    $params = [$id];
    if ($dossierId !== null) {
        $sql .= ' AND p.dossier_id = ?';
        $params[] = $dossierId;
    }
    $row = lf_query($sql, $params)->fetch();

    return is_array($row) ? $row : null;
}

function lf_sql_piece_status(string $id, string $status, string $accountId): bool
{
    $db = lf_db();
    $db->beginTransaction();
    try {
        $changed = lf_query('UPDATE lf_pieces SET statut = ? WHERE id = ? AND statut <> ?',
            [$status, $id, $status])->rowCount() > 0;
        if ($changed) {
            lf_query('INSERT INTO lf_evenements_pieces (piece_id, compte_id, action, statut) VALUES (?, ?, ?, ?)',
                [$id, $accountId, 'statut', $status]);
        }
        $db->commit();

        return $changed;
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}

function lf_sql_piece_event(string $pieceId, string $accountId, string $action, ?string $status = null): void
{
    lf_query('INSERT INTO lf_evenements_pieces (piece_id, compte_id, action, statut) VALUES (?, ?, ?, ?)',
        [$pieceId, $accountId, $action, $status]);
}

function lf_sql_pieces(array $filters, int $page, int $pageSize): array
{
    $where = [];
    $params = [];
    if ($filters['dossier'] !== null) {
        $where[] = 'p.dossier_id = ?';
        $params[] = $filters['dossier'];
    }
    if ($filters['period'] !== null) {
        $where[] = 'p.periode = ?';
        $params[] = $filters['period'];
    }
    if ($filters['status'] !== null) {
        $where[] = 'p.statut = ?';
        $params[] = $filters['status'];
    }
    $offset = max(0, ($page - 1) * $pageSize);
    $limit = $pageSize + 1;
    $rows = lf_query('SELECT p.id, p.nom, p.octets, p.periode, p.categorie, p.statut, p.cree_le, d.nom AS dossier
        FROM lf_pieces p INNER JOIN lf_dossiers d ON p.dossier_id = d.id'
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . " ORDER BY p.cree_le DESC, p.id DESC LIMIT $limit OFFSET $offset", $params)->fetchAll();

    return ['pieces' => array_slice($rows, 0, $pageSize), 'more' => count($rows) > $pageSize];
}

function lf_sql_rate_hit(string $key, int $window, int $max): bool
{
    $db = lf_db();
    $db->beginTransaction();
    try {
        lf_query('INSERT INTO lf_limites (cle, fenetre) VALUES (?, ?)
                  ON DUPLICATE KEY UPDATE essais = LEAST(essais + 1, 1000000)', [$key, $window]);
        $count = (int) lf_query('SELECT essais FROM lf_limites WHERE cle = ? AND fenetre = ?', [$key, $window])->fetchColumn();
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }

    return $count <= $max;
}
