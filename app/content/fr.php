<?php

declare(strict_types=1);

/**
 * Contenus français du site (source unique des textes).
 * Toute valeur commerciale ou légale non validée par le propriétaire porte la
 * mention « [À CONFIRMER] » et ne doit pas être publiée en l'état.
 */

return [
    'site' => [
        'name'        => 'La Fiduciaire',
        'tagline'     => 'Fiduciaire pour indépendants, TPE et PME de Suisse romande',
        'description' => 'Comptabilité, TVA, salaires, pilotage, création d’entreprise et bouclement pour les indépendants, TPE et PME de Suisse romande.',
    ],

    'nav' => [
        ['path' => '/',                 'label' => 'Accueil'],
        ['path' => '/prestations',      'label' => 'Prestations'],
        ['path' => '/tarifs',           'label' => 'Tarifs'],
        ['path' => '/zones',            'label' => 'Zones desservies'],
    ],

    'pages' => [
        'home'             => ['title' => 'La Fiduciaire — comptabilité et pilotage en Suisse romande', 'description' => 'Comptabilité, TVA, salaires, pilotage, création d’entreprise et bouclement pour indépendants, TPE et PME de Suisse romande. Demandez un devis.'],
        'prestations'      => ['title' => 'Prestations — La Fiduciaire', 'description' => 'Comptabilité, TVA, salaires, pilotage, création d’entreprise, bouclement : détail des prestations proposées.'],
        'tarifs'           => ['title' => 'Tarifs — La Fiduciaire', 'description' => 'Modalités d’honoraires de La Fiduciaire. Informations en cours de validation.'],
        'zones'            => ['title' => 'Zones desservies — La Fiduciaire', 'description' => 'Régions de Suisse romande desservies par La Fiduciaire.'],
        'devis'            => ['title' => 'Demander un devis — La Fiduciaire', 'description' => 'Décrivez votre situation et recevez une proposition adaptée.'],
        'mentions-legales' => ['title' => 'Mentions légales — La Fiduciaire', 'description' => 'Informations légales et coordonnées.'],
        '404'              => ['title' => 'Page introuvable — La Fiduciaire', 'description' => 'La page demandée n’existe pas.'],
    ],

    'hero' => [
        'eyebrow'     => 'La Fiduciaire · Suisse romande',
        'title'       => 'Votre comptabilité, en clair.',
        'subtitle'    => 'Comptabilité, TVA, salaires, pilotage, création d’entreprise et bouclement : un interlocuteur unique pour indépendants, TPE et PME.',
        'cta'         => 'Demander un devis',
        'cta_alt'     => 'Voir les 6 prestations',
        'video_pause' => 'Mettre la vidéo en pause',
        'video_play'  => 'Relancer la vidéo',
        'video_label' => 'Vidéo d’illustration en arrière-plan',
    ],

    'home' => [
        'proof_items'          => ['Indépendants, TPE et PME', 'Suisse romande', 'Un interlocuteur unique'],
        'prestations_title'    => 'Ce que nous prenons en charge',
        'prestations_answer'   => 'La Fiduciaire prend en charge six domaines : comptabilité, TVA, salaires, pilotage, création d’entreprise et bouclement.',
        'prestations_subtitle' => 'Six domaines, un seul interlocuteur.',
        'trust_title'          => 'Un accompagnement de proximité',
        'trust_answer'         => 'La Fiduciaire travaille avec des clients indépendants, des TPE et des PME de Suisse romande.',
        'trust_items'          => [
            'Indépendants, TPE et PME de Suisse romande',
            'Un interlocuteur unique pour la comptabilité et le pilotage',
            'Des prestations adaptées aux besoins de votre entreprise',
        ],
        'approche_answer' => 'Un interlocuteur unique suit votre dossier, de la tenue comptable courante au bouclement annuel.',
        'cta_title' => 'Parlons de votre situation',
        'cta_text'  => 'Décrivez votre activité et vos besoins : vous recevez une proposition adaptée.',
    ],

    'prestations' => [
        'title'  => 'Nos prestations',
        'answer' => 'Six prestations sont proposées : comptabilité, TVA, salaires, pilotage, création d’entreprise et bouclement.',
        'intro'  => 'Six domaines couverts, du quotidien comptable aux décisions de pilotage.',
        'items' => [
            ['nom' => 'Comptabilité',          'texte' => 'Tenue de la comptabilité courante et suivi des pièces justificatives.'],
            ['nom' => 'TVA',                   'texte' => 'Suivi des décomptes périodiques et des échéances liées à la TVA.'],
            ['nom' => 'Salaires',              'texte' => 'Établissement des salaires et suivi des obligations liées aux collaborateurs.'],
            ['nom' => 'Pilotage',              'texte' => 'Lecture des chiffres de l’entreprise pour appuyer les décisions courantes.'],
            ['nom' => 'Création d’entreprise', 'texte' => 'Accompagnement lors de la création et des démarches de mise en route.'],
            ['nom' => 'Bouclement',            'texte' => 'Préparation du bouclement annuel et des documents qui en découlent.'],
        ],
        'note' => '[À CONFIRMER] Le détail des modalités, des délais et des livrables reste à valider avec le propriétaire.',
    ],

    'tarifs' => [
        'title'  => 'Tarifs',
        'answer' => 'Les honoraires dépendent de votre situation : aucune grille tarifaire n’est publiée à ce jour.',
        'intro'  => 'Les honoraires dépendent de votre situation et des prestations demandées.',
        'body'   => 'Décrivez votre activité et le suivi dont vous avez besoin pour demander une proposition.',
        'cta'    => 'Demander une proposition',
    ],

    'zones' => [
        'title'  => 'Zones desservies',
        'answer' => 'La Fiduciaire accompagne des clients en Suisse romande.',
        'intro'  => 'La Fiduciaire accompagne des clients en Suisse romande.',
        'body'   => 'Indiquez votre lieu d’activité dans votre demande afin que nous puissions vous répondre.',
    ],

    'devis' => [
        'title' => 'Demander un devis',
        'intro' => 'Décrivez votre situation en quelques champs : la demande est ensuite traitée par la fiduciaire.',
        'note'  => 'Les demandes en ligne ne sont pas encore ouvertes sur ce site.',
        'fields' => [
            'nom'       => 'Nom et prénom',
            'email'     => 'Adresse e-mail',
            'telephone' => 'Téléphone (facultatif)',
            'activite'  => 'Activité ou forme juridique',
            'canton'    => 'Canton',
            'besoin'    => 'Besoin principal',
            'message'   => 'Votre message',
        ],
        'besoins' => ['Comptabilité', 'TVA', 'Salaires', 'Pilotage', 'Création d’entreprise', 'Bouclement', 'Plusieurs / autre'],
        'consent' => 'J’accepte que mes données soient utilisées pour traiter ma demande.',
        'submit'  => 'Envoyer ma demande de devis',
        'reassurance' => 'Vos informations servent uniquement à traiter cette demande. Aucun fichier comptable n’est accepté ici.',
        'indisponible' => [
            'title' => 'Demande non envoyée',
            'body'  => 'L’envoi n’a pas pu être confirmé. Revenez au formulaire pour vérifier l’état de votre demande.',
        ],
    ],

    'mentions' => [
        'title'         => 'Mentions légales',
        'body'          => 'La Fiduciaire est un cabinet fiduciaire actif en Suisse romande. Les informations légales complètes — raison sociale, numéro IDE et registre du commerce, adresse, affiliation professionnelle éventuelle, adresse e-mail et téléphone publics — sont en cours de validation par le cabinet et seront publiées avant l’ouverture du service.',
        'privacy_title' => 'Données personnelles',
        'privacy_body'  => 'Les données transmises via le formulaire de devis servent au traitement de votre demande. Les pièces déposées dans l’espace client sont destinées au suivi de votre dossier comptable.',
        'privacy_pending' => 'Les coordonnées du responsable du traitement ainsi que les modalités détaillées de conservation et d’exercice de vos droits seront publiées avant l’ouverture du service.',
    ],

    'footer' => [
        'legal'  => 'Mentions légales',
        'rights' => 'Tous droits réservés.',
    ],

    'home_why' => [
        'title' => 'Pourquoi nous choisir',
        'items' => [
            ['titre' => 'Proximité',            'texte' => 'Une fiduciaire de Suisse romande.'],
            ['titre' => 'Réactivité',           'texte' => 'Un interlocuteur unique, qui traite directement les demandes courantes.'],
            ['titre' => 'Expertise romande',    'texte' => 'Comptabilité, TVA, salaires et bouclement traités selon les usages romands.'],
        ],
    ],

    'home_temoignages' => [
        'title'    => 'Témoignages clients',
        'subtitle' => 'Trois retours de clients types. Chaque citation sera publiée après accord de la personne concernée.',
        'items'    => [
            ['role' => 'Indépendant', 'quote' => '[À FOURNIR PAR CLIENT]'],
            ['role' => 'TPE',         'quote' => '[À FOURNIR PAR CLIENT]'],
            ['role' => 'PME',         'quote' => '[À FOURNIR PAR CLIENT]'],
        ],
    ],

    'home_contact' => [
        'title' => 'Contact rapide',
        'intro' => 'Coordonnées à confirmer avant publication.',
        'items' => [
            ['label' => 'Téléphone', 'value' => '[À CONFIRMER]'],
            ['label' => 'E-mail',    'value' => '[À CONFIRMER]'],
            ['label' => 'Adresse',   'value' => '[À CONFIRMER]'],
            ['label' => 'Horaires',  'value' => '[À CONFIRMER]'],
        ],
        'cta' => 'Demander un devis',
    ],

    'portal' => [
        'categories' => [
            'achats' => 'Factures d’achat',
            'ventes' => 'Factures de vente',
            'banque' => 'Relevés bancaires',
            'salaires' => 'Salaires',
            'tva' => 'TVA',
            'autres' => 'Autres justificatifs',
        ],
        'statuses' => ['recu' => 'Reçu', 'traite' => 'Traité', 'a_remplacer' => 'À remplacer'],
    ],

    'ui' => [
        'skip'           => 'Aller au contenu',
        'menu'           => 'Navigation principale',
        'footer_nav'     => 'Plan du site',
        'notfound_title' => 'Page introuvable',
        'notfound_body'  => 'La page demandée n’existe pas ou a été déplacée.',
        'back_home'      => 'Retour à l’accueil',
    ],
];
