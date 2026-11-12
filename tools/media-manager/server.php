<?php

declare(strict_types=1);

/**
 * Routeur du serveur local (PHP CLI server uniquement).
 *
 * Lancement :
 *   php -d upload_max_filesize=256M -d post_max_size=300M \
 *       -S 127.0.0.1:8124 tools/media-manager/server.php
 *
 * Le document root du serveur intégré reste la racine du projet, mais ce
 * routeur ne sert QUE l'interface du gestionnaire : aucun fichier du site
 * n'est exposé par cette origine locale.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

set_exception_handler(static function (Throwable $error): void {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo json_encode(
        ['ok' => false, 'erreur' => 'Erreur interne du gestionnaire : ' . $error->getMessage()],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
});

$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($requestUri, PHP_URL_PATH);
$path = is_string($path) ? rawurldecode($path) : '/';
$method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');

if (str_starts_with($path, '/api/')) {
    require __DIR__ . '/src/api.php';

    $route = trim(substr($path, strlen('/api/')), '/');
    if ($route === '') {
        mm_fail('Route API vide.', 404);
    }

    mm_api_handle($route, $method);
}

$mimeTypes = [
    'html'        => 'text/html; charset=utf-8',
    'css'         => 'text/css; charset=utf-8',
    'js'          => 'application/javascript; charset=utf-8',
    'json'        => 'application/json; charset=utf-8',
    'svg'         => 'image/svg+xml',
    'png'         => 'image/png',
    'ico'         => 'image/x-icon',
    'webmanifest' => 'application/manifest+json',
];

$publicRoot = realpath(__DIR__ . '/public');
$relative = $path === '/' ? '/index.html' : $path;
$absolute = realpath(__DIR__ . '/public' . $relative);

if ($publicRoot === false || $absolute === false || !is_file($absolute)
    || !str_starts_with(str_replace('\\', '/', $absolute), str_replace('\\', '/', $publicRoot) . '/')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Ressource introuvable.';
    return;
}

$extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));

header('Content-Type: ' . ($mimeTypes[$extension] ?? 'application/octet-stream'));
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if ($extension === 'html') {
    header(
        "Content-Security-Policy: default-src 'self'; " .
        "img-src 'self' data: blob:; media-src 'self' blob:; " .
        "style-src 'self' 'unsafe-inline'; script-src 'self'; " .
        "connect-src 'self'; frame-ancestors 'none'; base-uri 'none'"
    );
}

readfile($absolute);
