<?php

declare(strict_types=1);

/**
 * Point d'entrée unique du site (voir .htaccess : toute requête non fichier
 * passe par ce fichier).
 */

// Le serveur de développement ne sert que les actifs publics connus.
if (PHP_SAPI === 'cli-server') {
    $requested = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_string($requested)) {
        $requested = rawurldecode($requested);
        if ((preg_match('~^/assets/(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_.-]+\.(?:css|js|png|jpe?g|webp|avif|svg|woff2)$~D', $requested)
                || in_array($requested, ['/hero-video.webm', '/hero-video.mp4', '/hero-video-poster.jpg'], true))
            && is_file(__DIR__ . $requested)) {
            return false;
        }
    }
}

require __DIR__ . '/app/bootstrap.php';

$path = request_path();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($path === '/espace-client' || str_starts_with($path, '/espace-client/')) {
    require __DIR__ . '/app/portal/controller.php';
    lf_portal_dispatch($path, $method);
    exit;
}

$routes = require __DIR__ . '/app/routes.php';
if (!isset($routes[$path])) {
    render('404', ['path' => $path], 404);
    exit;
}

$view = $routes[$path];

if ($view === 'devis' && in_array($method, ['GET', 'HEAD', 'POST'], true)) {
    require __DIR__ . '/app/portal/quote.php';
    lf_quote($method);
    exit;
}

if (!in_array($method, ['GET', 'HEAD'], true)) {
    header('Allow: ' . ($view === 'devis' ? 'GET, HEAD, POST' : 'GET, HEAD'));
    render('404', ['path' => $path], 405);
    exit;
}

render($view);
