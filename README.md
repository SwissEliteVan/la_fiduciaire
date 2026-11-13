# La Fiduciaire — site vitrine

Site vitrine de **La Fiduciaire** (indépendants, TPE et PME de Suisse romande) :
présentation des prestations, tarifs, zones desservies et demande de devis.

**Stack :** PHP 8.3 natif + MySQL (option A de l'ADR-001), zéro dépendance runtime,
hébergement cible Hostinger Business mutualisé. Aucun framework, aucun service externe.

## Structure

```
index.php                 contrôleur frontal (routes, méthode HTTP)
.htaccess                 réécriture, en-têtes, refus d'accès aux dossiers internes
app/
  bootstrap.php           configuration, session, contenu localisé, helpers, rendu
  devis.php               formulaire de devis : validation, CSRF, anti-spam, PDO
  routes.php              table des routes
  config.example.php      modèle de configuration (aucun secret)
  content/fr.php          textes français (source unique)
  views/                  gabarit + pages
assets/css/app.css        design system, responsive, prefers-reduced-motion
assets/js/hero.js         pause/lecture du hero, respect du mouvement réduit
assets/media-geres/       emplacement prévu pour les médias gérés (vide au départ)
database/schema.sql       table `demandes_devis`
tools/media-manager/      gestionnaire local de médias (outil, non publié)
tools/tests/              tests ciblés du formulaire de devis (CLI, sans base)
storage/                  JSON de gestion, throttle, imports, sauvegardes (ignoré par Git)
hero-video.webm           source fournie par le propriétaire (ne pas modifier)
hero-video.mp4            dérivé H.264 1080p/30, AAC 96k
hero-video-poster.jpg     poster 1920×1080
projet/                   PRD, roadmap, backlog, décisions, NOW
instructions/             consignes du kit (référence, non publiées)
```

## Développement local

```bash
cp app/config.example.php app/config.local.php   # puis renseigner la base
php -S 127.0.0.1:8123 index.php
```

`app/config.local.php` n'est jamais versionné (voir `.gitignore`).

## Gestionnaire local de médias

Outil local (PHP natif, aucune dépendance, aucun service externe) pour inventorier,
classer et remplacer les médias du projet sans édition manuelle.

```powershell
powershell -ExecutionPolicy Bypass -File tools/media-manager/lancer.ps1
```

Équivalent manuel :

```bash
php -d upload_max_filesize=256M -d post_max_size=300M -S 127.0.0.1:8124 tools/media-manager/server.php
```

- Galerie : <http://127.0.0.1:8124/> (écoute locale uniquement).
- Inventaire : images, vidéos, audio, SVG ; formats, dimensions, poids, empreintes,
  doublons, variantes regroupées et usages fichier:ligne.
- Décisions par média : CONSERVER, SUPPRIMER (candidat), REMPLACER (sélecteur de
  fichiers local, comparaison avant/après), NON DÉCIDÉ.
- JSON de gestion : `storage/media-manager/media-manager.json` ; imports temporaires
  dans `storage/media-manager/imports/` ; sauvegardes, corbeille et rapports dans
  `storage/media-manager/backups/` et `storage/media-manager/logs/`.
- Aucune suppression avant validation explicite **et** saisie de `APPLIQUER`.
  Les fichiers retirés sont déplacés en corbeille restaurable ; les NON DÉCIDÉS ne
  sont jamais modifiés.
- Contrat média vérifié avant application (AVIF/WebP/JPEG, largeurs, dimensions,
  poster vidéo, attributs, budgets de poids). Les écarts bloquants exigent une
  dérogation motivée, enregistrée dans le JSON.

Vérifications en ligne de commande :

```bash
php tools/media-manager/tests/inventory-check.php
php tools/media-manager/tests/contract-check.php
```

## Formulaire de devis (MySQL)

Le formulaire `/devis` est actif : validation serveur, jeton CSRF de session,
piège à robots, limitation à 10 tentatives par IP et par quart d'heure, puis
insertion PDO préparée dans `demandes_devis`. Après succès, redirection (303)
vers `/devis` avec confirmation : un rafraîchissement ou le rejeu de l'ancien
jeton n'enregistre pas une seconde demande.

Prérequis serveur : PHP 8.3 avec `pdo_mysql` et une base MySQL. Créer la table :

```bash
mysql -u <utilisateur> -p <base> < database/schema.sql
```

Renseigner `app/config.local.php` (jamais versionné). Sans base configurée,
`POST /devis` répond **503** avec un message de reprise, conserve la saisie et
n'affiche aucune erreur technique. `ip_hash` reste `NULL` tant que
`security.ip_salt` n'est pas défini : aucune adresse IP n'est alors conservée,
même hachée. Les journaux ne contiennent jamais le contenu du formulaire.

Tests locaux (serveur intégré, sans MySQL) :

```powershell
php tools/tests/devis-check.php
$env:DEVIS_THROTTLE_FILE = "$PWD\storage\devis-throttle.json"
php -S 127.0.0.1:8123 index.php
# dans un second terminal :
php tools/tests/devis-http.php http://127.0.0.1:8123
```

Limite connue : l'insertion MySQL réelle n'est pas prouvée par ces tests
(`pdo_mysql` absent de l'environnement local) ; elle doit être vérifiée sur une
base de recette avant de déclarer TASK-006 `DONE`.

## État du projet

Voir `projet/NOW.md` (tâche active) et `projet/BACKLOG.md`. Éléments non validés
par le propriétaire encore marqués `[À CONFIRMER]` : tarifs, zones desservies,
mentions légales.

## Règles

- Aucun secret dans le dépôt, aucun service externe indispensable.
- Vidéo hero locale obligatoire : WebM + MP4 + poster, bouton pause, `prefers-reduced-motion`.
- Publication seulement après accord explicite ; aucun déploiement implicite.
