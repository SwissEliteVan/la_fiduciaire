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

$routes = require __DIR__ . '/app/routes.php';
$path = request_path();

if (!isset($routes[$path])) {
    render('404', ['path' => $path], 404);
    exit;
}

$view = $routes[$path];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Le formulaire de devis est visible, mais son enregistrement MySQL relève de
// TASK-006 : aucune donnée n'est acceptée ni stockée tant que ce n'est pas fait.
if ($view === 'devis' && $method === 'POST') {
    render('devis-indisponible', [], 503);
    exit;
}

if (!in_array($method, ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD, POST');
    render('404', ['path' => $path], 405);
    exit;
}

render($view);
