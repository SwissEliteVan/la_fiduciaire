<?php

declare(strict_types=1);

/**
 * Point d'entrée unique du site (voir .htaccess : toute requête non fichier
 * passe par ce fichier).
 */

// Serveur intégré PHP (développement seulement) : laisser passer les fichiers
// réellement présents (CSS, JS, médias) avant tout envoi d'en-tête HTML.
if (PHP_SAPI === 'cli-server') {
    $requested = __DIR__ . (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
    if (is_file($requested)) {
        return false;
    }
}

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/devis.php';

$routes = require __DIR__ . '/app/routes.php';
$path = request_path();

if (!isset($routes[$path])) {
    render('404', ['path' => $path], 404);
    exit;
}

$view = $routes[$path];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($view === 'devis') {
    // Données personnelles : aucune mise en cache intermédiaire, aucune indexation.
    header('Cache-Control: no-store, max-age=0');
    header('X-Robots-Tag: noindex, nofollow');
}

if ($view === 'devis' && $method === 'POST') {
    devis_handle_post();
    exit;
}

if (!in_array($method, ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD, POST');
    render('404', ['path' => $path], 405);
    exit;
}

render($view);
