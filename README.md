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
database/schema.sql       table `demandes_devis` (préparation TASK-006)
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
