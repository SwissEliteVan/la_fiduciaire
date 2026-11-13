<?php

declare(strict_types=1);

/**
 * Modèle de configuration (JAMAIS de vrai secret).
 * Copier ce fichier en `app/config.local.php` sur la machine de développement
 * et sur l'hébergement : `app/config.local.php` est exclu par `.gitignore`.
 */

return [
    'env'      => 'local',
    'debug'    => false,
    'locale'   => 'fr',
    'base_url' => '',          // ex. 'https://la-fiduciaire.ch' en production
    'db'       => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'REMPLACER_PAR_LE_NOM_DE_BASE',
        'user'     => 'REMPLACER_PAR_UTILISATEUR',
        'password' => 'REMPLACER_PAR_MOT_DE_PASSE',
        'charset'  => 'utf8mb4',
    ],
    'portal' => [
        'enabled'      => false,
        'storage_dir'  => '',  // Chemin absolu hors racine publique, permissions 0700.
        'secret'       => '',  // bin2hex(random_bytes(32)), secret local uniquement.
        'scanner'      => '',  // Exécutable clamscan avec signatures à jour (requis hors test local).
    ],
    'quote_enabled' => false,
    // Coordonnées publiques : à confirmer par le propriétaire (voir projet/PRD.md).
    'contact'  => [
        'email'     => null,       // [À CONFIRMER]
        'telephone' => null,       // [À CONFIRMER]
    ],
];
