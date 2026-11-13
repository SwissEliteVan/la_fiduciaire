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
        'backend'      => 'files', // files = JSON privé, sans SQL ; mysql = option historique.
        'storage_dir'  => '',  // Vide = dossier privé créé à côté du site, hors racine web.
        'secret'       => '',  // Vide en mode fichiers = secret généré dans le dossier privé.
        'scanner'      => '',  // Exécutable clamscan externe, signatures à jour (hors recette).
        'scanner_command' => [], // Tableau argv réservé à la recette locale sur 127.0.0.1.
    ],
    'quote_enabled' => true,
    'quote_backend' => 'files',  // files = privé, sans SQL ; mysql = option historique.
    'quote_storage_dir' => '',   // Optionnel, chemin absolu hors racine web et hors dépôt.
    // Coordonnées publiques : à confirmer par le propriétaire (voir projet/PRD.md).
    'contact'  => [
        'email'     => null,       // [À CONFIRMER]
        'telephone' => null,       // [À CONFIRMER]
    ],
];
