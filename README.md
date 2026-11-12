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
  bootstrap.php           configuration, contenu localisé, helpers, rendu
  routes.php              table des routes
  config.example.php      modèle de configuration (aucun secret)
  content/fr.php          textes français (source unique)
  views/                  gabarit + pages
assets/css/app.css        design system, responsive, prefers-reduced-motion
assets/js/hero.js         pause/lecture du hero, respect du mouvement réduit
assets/media-geres/       emplacement prévu pour les médias gérés (vide au départ)
database/schema.sql       table `demandes_devis` (préparation TASK-006)
tools/media-manager/      gestionnaire local de médias (outil, non publié)
storage/                  JSON de gestion, imports temporaires, sauvegardes (ignoré par Git)
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

## Base de données

```bash
mysql -u <utilisateur> -p <base> < database/schema.sql
```

La table existe mais **aucune donnée n'est encore collectée** : l'enregistrement
du formulaire fait l'objet de la tâche TASK-006 (validation serveur, anti-spam
natif, messages d'erreur). Aujourd'hui, un POST sur `/devis` répond 503 sans
rien conserver.

## État du projet

Voir `projet/NOW.md` (tâche active) et `projet/BACKLOG.md`. Éléments non validés
par le propriétaire encore marqués `[À CONFIRMER]` : tarifs, zones desservies,
mentions légales.

## Règles

- Aucun secret dans le dépôt, aucun service externe indispensable.
- Vidéo hero locale obligatoire : WebM + MP4 + poster, bouton pause, `prefers-reduced-motion`.
- Publication seulement après accord explicite ; aucun déploiement implicite.
