<?php

declare(strict_types=1);

/**
 * Configuration de recette locale, sans SQL et sans secret.
 * Usage : copier ce fichier en `app/config.local.php`, puis lancer
 * `php -S 127.0.0.1:8123 index.php`. Les données restent dans un dossier
 * privé créé à côté du site. Ne pas publier en production.
 */

return [
    'env'      => 'local',
    'debug'    => false,
    'locale'   => 'fr',
    'base_url' => '',
    'db'       => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'REMPLACER_PAR_LE_NOM_DE_BASE',
        'user'     => 'REMPLACER_PAR_UTILISATEUR',
        'password' => 'REMPLACER_PAR_MOT_DE_PASSE',
        'charset'  => 'utf8mb4',
    ],
    'portal' => [
        'enabled'         => true,
        'backend'         => 'files',
        'storage_dir'     => '',
        'secret'          => '',
        'scanner'         => '',
        'scanner_command' => [PHP_BINARY, APP_ROOT . '/tools/tests/scanner-factice.php'],
    ],
    'quote_enabled'     => true,
    'quote_backend'     => 'files',
    'quote_storage_dir' => '',
    'contact'  => [
        'email'     => null,
        'telephone' => null,
    ],
];
