<?php

declare(strict_types=1);

/**
 * Amorçage de l'application : configuration, contenu localisé, helpers de rendu.
 * Cible de production : PHP 8.3 (hébergement mutualisé Hostinger Business).
 * Aucun secret ne doit être écrit dans ce fichier.
 */

define('APP_ROOT', dirname(__DIR__));

$configFile = __DIR__ . '/config.local.php';
$config = is_file($configFile)
    ? require $configFile
    : require __DIR__ . '/config.example.php';

if (!is_array($config)) {
    http_response_code(500);
    exit('Configuration invalide.');
}

$GLOBALS['app_config'] = $config;

$locale = (string) ($config['locale'] ?? 'fr');
$contentFile = __DIR__ . '/content/' . $locale . '.php';
$GLOBALS['app_locale'] = $locale;
$GLOBALS['app_content'] = is_file($contentFile) ? require $contentFile : [];

/*
 * Session serveur : support du jeton CSRF et des messages flash du formulaire
 * de devis uniquement. Aucun cookie de session n'est posé sur la vitrine.
 * Jamais démarrée en CLI (scripts de test purs).
 */
if (PHP_SAPI !== 'cli' && request_path() === '/devis' && session_status() === PHP_SESSION_NONE) {
    $secureCookie = str_starts_with((string) ($config['base_url'] ?? ''), 'https://');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('LFSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (($config['debug'] ?? false) === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

/** Valeur de configuration par chemin pointé : config('db.host'). */
function config(string $path, mixed $default = null): mixed
{
    $value = $GLOBALS['app_config'];
    foreach (explode('.', $path) as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return $default;
        }
        $value = $value[$key];
    }

    return $value;
}

/** Texte localisé par chemin pointé : content('home.hero.title'). */
function content(string $path, mixed $default = null): mixed
{
    $value = $GLOBALS['app_content'];
    foreach (explode('.', $path) as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return $default;
        }
        $value = $value[$key];
    }

    return $value;
}

function locale(): string
{
    return (string) $GLOBALS['app_locale'];
}

/** Échappement systématique de toute valeur affichée. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Chemin public absolu depuis la racine du site. */
function asset(string $path): string
{
    $relative = ltrim($path, '/');
    $file = APP_ROOT . '/' . $relative;
    $version = is_file($file) ? (string) filemtime($file) : '1';

    return '/' . $relative . '?v=' . $version;
}

/** Chemin de la requête, normalisé ; refuse toute tentative de traversée. */
function request_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    $path = is_string($path) ? rawurldecode($path) : '/';

    if (str_contains($path, '..')) {
        return '/chemin-invalide';
    }

    $path = '/' . trim($path, '/');

    return $path === '/' ? '/' : rtrim($path, '/');
}

function is_active(string $path): bool
{
    return request_path() === $path;
}

/**
 * Rend une vue dans le gabarit commun.
 *
 * @param array<string, mixed> $data
 */
function render(string $view, array $data = [], int $status = 200): void
{
    $file = __DIR__ . '/views/' . $view . '.php';

    if (!is_file($file)) {
        http_response_code(500);
        exit('Vue introuvable.');
    }

    http_response_code($status);
    $data['view'] = $view;
    extract($data, EXTR_SKIP);

    ob_start();
    require $file;
    $content = (string) ob_get_clean();

    require __DIR__ . '/views/layout.php';
}

/** Jeton CSRF de session, créé à la demande. Chaîne vide si aucune session. */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/** Vérifie un jeton CSRF en temps constant. */
function csrf_check(mixed $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        return false;
    }

    return is_string($token) && $token !== '' && hash_equals($_SESSION['csrf_token'], $token);
}

/** Renouvelle le jeton après une soumission acceptée (anti double envoi). */
function csrf_rotate(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/** Connexion MySQL paresseuse, erreurs en exceptions, requêtes préparées réelles. */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $name = (string) config('db.name', '');
    if ($name === '' || str_starts_with($name, 'REMPLACER')) {
        throw new RuntimeException('Base de données non configurée.');
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        (string) config('db.host', 'localhost'),
        $name,
        (string) config('db.charset', 'utf8mb4')
    );

    $pdo = new PDO($dsn, (string) config('db.user', ''), (string) config('db.password', ''), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

/**
 * Empreinte d'IP pour la colonne `ip_hash`.
 * Aucune valeur n'est stockée sans sel configuré : pas de sel inventé.
 */
function client_ip_hash(): ?string
{
    $salt = config('security.ip_salt');
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    if (!is_string($salt) || $salt === '' || $ip === '') {
        return null;
    }

    return hash_hmac('sha256', $ip, $salt);
}

/** Longueur de texte UTF-8 sans exiger l'extension mbstring. */
function text_length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return (int) mb_strlen($value, 'UTF-8');
    }

    $count = preg_match_all('/./us', $value);

    return $count === false ? strlen($value) : (int) $count;
}

/** Redirection interne (303 après POST : évite le double envoi au rafraîchissement). */
function redirect(string $path, int $status = 303): never
{
    header('Location: ' . $path, true, $status);
    exit;
}
