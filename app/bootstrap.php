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

/**
 * Portail : fermé par défaut. Il est ouvert explicitement par `portal.enabled`
 * (voir `app/config.recette.php` pour la recette locale sans SQL).
 */
function lf_portal_enabled(): bool
{
    return config('portal.enabled', false) === true;
}
