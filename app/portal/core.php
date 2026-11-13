<?php

declare(strict_types=1);

function lf_db(): PDO
{
    static $db;
    if ($db instanceof PDO) {
        return $db;
    }
    $host = (string) config('db.host');
    $name = (string) config('db.name');
    $port = (int) config('db.port', 3306);
    if (!preg_match('/^[a-zA-Z0-9.-]+$/D', $host) || !preg_match('/^[a-zA-Z0-9_-]+$/D', $name)
        || $name === 'REMPLACER_PAR_LE_NOM_DE_BASE' || $port < 1 || $port > 65535) {
        throw new RuntimeException('Base non configurée.');
    }
    $db = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", (string) config('db.user'), (string) config('db.password'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $db->exec("SET time_zone = '+00:00'");
    return $db;
}

function lf_query(string $sql, array $params = []): PDOStatement
{
    $statement = lf_db()->prepare($sql);
    $statement->execute($params);
    return $statement;
}

function lf_https(): bool
{
    return in_array(strtolower((string) ($_SERVER['HTTPS'] ?? '')), ['on', '1'], true);
}

function lf_local_http(): bool
{
    return config('env') === 'local' && PHP_SAPI === 'cli-server'
        && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
        && in_array($_SERVER['SERVER_NAME'] ?? '', ['127.0.0.1', '::1', 'localhost'], true);
}

function lf_session(): void
{
    if (!lf_https() && !lf_local_http()) {
        throw new RuntimeException('HTTPS requis.');
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_cache_limiter('');
    session_name(lf_https() ? '__Host-LFSESSION' : 'LFSESSION_LOCAL');
    session_set_cookie_params([
        'path' => '/', 'domain' => '', 'secure' => lf_https(), 'httponly' => true, 'samesite' => 'Lax',
    ]);
    if (!session_start()) {
        throw new RuntimeException('Session indisponible.');
    }
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function lf_text(array $source, string $key): string
{
    return is_string($source[$key] ?? null) ? trim($source[$key]) : '';
}

function lf_csrf_ok(): bool
{
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], lf_text($_POST, 'csrf'));
}

function lf_redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function lf_private_headers(): void
{
    header('Cache-Control: no-store, private');
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'self'; base-uri 'none'; object-src 'none'; frame-ancestors 'none'; form-action 'self'");
}

function lf_error(string $title, string $message, int $status): void
{
    render('portal-message', ['pageTitle' => $title . ' — La Fiduciaire', 'title' => $title, 'message' => $message], $status);
}

function lf_auth_user(): ?array
{
    if (!isset($_SESSION['uid'], $_SESSION['version'], $_SESSION['vu'], $_SESSION['debut'])) {
        return null;
    }
    if (time() - $_SESSION['vu'] > 1800 || time() - $_SESSION['debut'] > 28800) {
        lf_clear_identity();
        return null;
    }
    $user = lf_query('SELECT id, dossier_id, role, version_session FROM lf_comptes WHERE id = ? AND actif = 1 AND password_hash IS NOT NULL',
        [$_SESSION['uid']])->fetch();
    if (!$user || (string) $user['version_session'] !== $_SESSION['version']
        || (($user['role'] === 'client') !== ($user['dossier_id'] !== null))) {
        lf_clear_identity();
        return null;
    }
    $_SESSION['vu'] = time();
    return $user;
}

function lf_clear_identity(): void
{
    $_SESSION = [];
    if (!session_regenerate_id(true)) {
        session_destroy();
        throw new RuntimeException('Rotation de session impossible.');
    }
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function lf_email(string $email): ?string
{
    $email = strtolower(trim($email));
    return strlen($email) <= 180 && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
}

function lf_secret(): string
{
    $secret = config('portal.secret');
    if (!is_string($secret) || !preg_match('/^[a-f0-9]{64}$/D', $secret)) {
        throw new RuntimeException('Secret applicatif non configuré.');
    }
    return $secret;
}

function lf_rate(string $purpose, string $subject, int $max): bool
{
    $key = hash_hmac('sha256', $purpose . '|' . $subject, lf_secret());
    $window = intdiv(time(), 900);
    // L’incrément et la lecture sont sérialisés sur la ligne InnoDB.
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

function lf_categories(): array
{
    return (array) content('portal.categories', []);
}

function lf_statuses(): array
{
    return (array) content('portal.statuses', []);
}

function lf_audit(string $piece, array $user, string $action, ?string $status = null): void
{
    lf_query('INSERT INTO lf_evenements_pieces (piece_id, compte_id, action, statut) VALUES (?, ?, ?, ?)',
        [$piece, $user['id'], $action, $status]);
}
