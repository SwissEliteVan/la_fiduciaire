<?php

declare(strict_types=1);

/**
 * Modèle de configuration (JAMAIS de vrai secret).
 * Copier ce fichier en `app/config.local.php` sur la machine de développement
 * et sur l'hébergement : `app/config.local.php` est exclu par `.gitignore`.
 */

return [
    'env'      => 'local',
    'debug'    => true,
    'locale'   => 'fr',
    'base_url' => '',          // ex. 'https://la-fiduciaire.ch' en production
    'db'       => [
        'host'     => 'localhost',
        'name'     => 'REMPLACER_PAR_LE_NOM_DE_BASE',
        'user'     => 'REMPLACER_PAR_UTILISATEUR',
        'password' => 'REMPLACER_PAR_MOT_DE_PASSE',
        'charset'  => 'utf8mb4',
    ],
    // Coordonnées publiques : à confirmer par le propriétaire (voir projet/PRD.md).
    'contact'  => [
        'email'     => null,       // [À CONFIRMER]
        'telephone' => null,       // [À CONFIRMER]
    ],
    // Empreinte d'IP facultative pour la colonne `ip_hash` du formulaire de devis.
    // Laisser null : aucune adresse IP n'est conservée, même hachée.
    'security' => [
        'ip_salt' => null,         // secret aléatoire long, propre à l'environnement
    ],
];
