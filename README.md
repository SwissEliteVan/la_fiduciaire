# La Fiduciaire

La Fiduciaire est un site vitrine en français pour les indépendants, TPE et PME de Suisse romande. Il présente les prestations, les tarifs, les zones desservies et permet d’envoyer une demande de devis.

## DÉMARRAGE RAPIDE — COMMANDES DEV

Toutes les commandes ci-dessous s’exécutent depuis la racine du projet : `C:\Users\Administrateur\Local Sites\la_fiduciaire`.

Le projet n’a pas de `package.json`, de lockfile npm/pnpm/yarn/bun, de `composer.json`, de `pyproject.toml`, de `Makefile` ni de configuration Docker. Il n’y a donc pas de commande d’installation de dépendances ni de build frontend à lancer. La source de cette constatation est l’inventaire des fichiers du dépôt ; la stack est décrite dans [`projet/DECISIONS.md`](./projet/DECISIONS.md).

### 1. Préparer la configuration locale

Source : [`app/config.example.php`](./app/config.example.php) et [`.gitignore`](./.gitignore).

```powershell
Copy-Item app/config.example.php app/config.local.php
```

Ouvrez ensuite `app/config.local.php` et renseignez uniquement les paramètres locaux de la base. Ce fichier est ignoré par Git et ne doit jamais contenir de valeur publiée dans le dépôt.

### 2. Préparer la base MySQL

Source : [`database/schema.sql`](./database/schema.sql). Prérequis : PHP 8.3 côté hébergement, l’extension `pdo_mysql` et une base MySQL ou MariaDB.

```bash
mysql -u <utilisateur> -p <base> < database/schema.sql
```

Les valeurs entre chevrons sont à remplacer dans votre terminal ; aucun mot de passe ne doit être écrit dans le README ou dans Git.

### 3. Démarrer le serveur PHP local

Source : [`index.php`](./index.php), qui sert de contrôleur frontal.

```bash
php -S 127.0.0.1:8123 index.php
```

Le site est alors accessible à l’adresse locale `http://127.0.0.1:8123`. Cette commande démarre le serveur de développement PHP intégré ; elle ne constitue pas un déploiement.

### 4. Lancer les tests ciblés

Test de validation sans base de données, depuis la racine :

```powershell
php tools/tests/devis-check.php
```

Test HTTP, depuis un second terminal. Le premier terminal doit garder le serveur lancé :

```powershell
$env:DEVIS_THROTTLE_FILE = "$PWD\storage\devis-throttle.test.json"
php -S 127.0.0.1:8123 index.php
```

Puis, dans le second terminal, toujours depuis la racine :

```powershell
php tools/tests/devis-http.php http://127.0.0.1:8123
```

Sources : [`tools/tests/devis-check.php`](./tools/tests/devis-check.php) et [`tools/tests/devis-http.php`](./tools/tests/devis-http.php). Ces tests couvrent la vitrine, le formulaire, le CSRF, les erreurs de validation, le piège à robots, la limitation de tentatives et le comportement sans base. L’insertion MySQL réelle n’est pas prouvée localement tant que `pdo_mysql` et une base de recette ne sont pas disponibles.

### 5. Vérifier l’outil local de médias

Source : [`tools/media-manager/lancer.ps1`](./tools/media-manager/lancer.ps1) et [`tools/media-manager/tests/`](./tools/media-manager/tests/).

```powershell
powershell -ExecutionPolicy Bypass -File tools/media-manager/lancer.ps1
```

Vérifications sans lancer l’interface :

```bash
php tools/media-manager/tests/inventory-check.php
php tools/media-manager/tests/contract-check.php
```

Il n’y a pas de commande de build dans les fichiers du projet. `php -S` sert uniquement au développement local.

## Sommaire

- [Comprendre le projet](#comprendre-le-projet)
- [Installation pas à pas](#installation-pas-à-pas)
- [Utiliser le site](#utiliser-le-site)
- [Médias réellement présents](#médias-réellement-présents)
- [Tests et état connu](#tests-et-état-connu)
- [Dépannage](#dépannage)
- [Organisation technique](#organisation-technique)
- [Git et déploiement](#git-et-déploiement)
- [Documents utiles](#documents-utiles)

## Comprendre le projet

Le navigateur demande une URL à `index.php`. Le fichier [`app/routes.php`](./app/routes.php) associe chaque chemin à une vue, [`app/bootstrap.php`](./app/bootstrap.php) charge la configuration et le contenu français, puis [`app/views/layout.php`](./app/views/layout.php) ajoute le gabarit commun.

La stack actuelle est volontairement simple : PHP natif, templates PHP, PDO pour MySQL et Apache avec [`.htaccess`](./.htaccess) en hébergement. Aucun framework ni service externe n’est requis.

## Installation pas à pas

1. Installez PHP CLI sur votre machine et vérifiez sa présence :

   ```powershell
   php -v
   ```

2. Depuis la racine du projet, copiez [`app/config.example.php`](./app/config.example.php) vers `app/config.local.php`.
3. Renseignez la connexion MySQL dans la copie locale. Ne copiez jamais ces valeurs dans un fichier suivi par Git.
4. Importez [`database/schema.sql`](./database/schema.sql) dans la base.
5. Démarrez le serveur avec la commande de la section [Démarrage rapide](#démarrage-rapide--commandes-dev).

L’hébergement cible indiqué par le projet est Hostinger Business mutualisé. Les capacités réelles du compte, les sauvegardes et les conditions de déploiement restent à vérifier sur le compte concerné ; aucune URL de production n’est documentée ici.

## Utiliser le site

Les routes actuellement déclarées dans [`app/routes.php`](./app/routes.php) sont :

- `/` : accueil et hero vidéo ;
- `/prestations` : six domaines de prestation ;
- `/tarifs` : informations d’honoraires encore à confirmer ;
- `/zones` : Suisse romande, avec détails encore à confirmer ;
- `/devis` : formulaire de demande ;
- `/mentions-legales` : informations légales encore à confirmer.

Le formulaire `/devis` vérifie côté serveur les champs, le consentement, le jeton CSRF et le type de besoin. Il applique aussi un piège à robots et une limite de dix tentatives par adresse IP et par fenêtre de quinze minutes. Une demande valide est enregistrée par requête PDO préparée lorsque MySQL est disponible, puis la page redirige en `303` pour éviter le double envoi au rafraîchissement.

Lorsque la base n’est pas configurée ou indisponible, la page répond `503` avec un message de reprise et sans afficher de détail technique. La preuve d’insertion MySQL en conditions réelles reste à faire.

## Médias réellement présents

Le hero contient une vraie vidéo locale, un poster local, un repli MP4 et un bouton de pause. Le script [`assets/js/hero.js`](./assets/js/hero.js) prend aussi en charge `prefers-reduced-motion`, et le markup se trouve dans [`app/views/home.php`](./app/views/home.php).

| Fichier | Origine et droits | Dimensions / caractéristiques | Taille constatée |
| --- | --- | --- | ---: |
| [`hero-video.webm`](./hero-video.webm) | Fichier fourni par le propriétaire ; décision d’exploitation consignée dans [`projet/DECISIONS.md`](./projet/DECISIONS.md) | WebM, VP9, 3840×2160, 60 i/s, audio Opus, 10,056 s | 3 149 260 octets |
| [`hero-video.mp4`](./hero-video.mp4) | Dérivé local autorisé à partir de la source propriétaire | H.264, 1920×1080, 30 i/s, audio AAC, 10,048 s | 704 735 octets |
| [`hero-video-poster.jpg`](./hero-video-poster.jpg) | Poster local extrait dans le périmètre média autorisé | JPEG, 1920×1080 | 48 511 octets |

La source WebM est conservée à la racine et ne doit pas être modifiée. Le dépôt ne contient pas de licence publique séparée pour cette vidéo : le droit d’exploitation est déclaré par décision du propriétaire dans [`projet/DECISIONS.md`](./projet/DECISIONS.md). Aucun média distant n’est utilisé pour le hero.

## Tests et état connu

Les commandes de test sont celles de [`tools/tests/devis-check.php`](./tools/tests/devis-check.php) et [`tools/tests/devis-http.php`](./tools/tests/devis-http.php). Le suivi [`projet/NOW.md`](./projet/NOW.md) indique :

- `php -l` sur les fichiers PHP : **PASS**, 0 erreur ;
- test de validation du devis : **25 PASS** ;
- test HTTP local : **42 PASS** ;
- insertion MySQL réelle et double envoi après insertion : **NON EXÉCUTÉS** (`pdo_mysql` et MySQL absents de l’environnement local) ;
- QA navigateur, mobile, clavier et captures : **NON EXÉCUTÉE**.

Le site n’est donc pas à qualifier comme totalement validé ou prêt à publier sur la seule base des tests locaux.

## Dépannage

### `php` ou `mysql` est introuvable

Installez l’outil correspondant dans votre environnement local, puis relancez `php -v` ou la commande MySQL. Le projet n’installe pas ces outils automatiquement.

### `POST /devis` répond `503`

Vérifiez que `app/config.local.php` existe, que les identifiants sont corrects, que la base est joignable et que `pdo_mysql` est chargé par PHP. Les détails de connexion ne doivent pas être publiés dans un journal ou un commit.

### Le serveur local affiche une erreur de démarrage

Vérifiez qu’aucun autre processus n’utilise le port `8123`, puis choisissez un port local libre dans la commande `php -S` et transmettez la même URL au test HTTP.

### Le formulaire refuse la demande

Rechargez `/devis` afin d’obtenir un nouveau jeton CSRF. Contrôlez ensuite les champs signalés, le besoin sélectionné et le consentement. Le navigateur ne remplace pas la validation serveur.

## Organisation technique

```text
index.php                 contrôleur frontal
.htaccess                 réécriture Apache et accès aux dossiers internes
app/bootstrap.php         configuration, session, CSRF, PDO et helpers
app/devis.php             validation, anti-spam et traitement du devis
app/routes.php             routes publiques
app/content/fr.php         textes français
app/views/                 gabarits des pages
assets/css/app.css         styles et responsive
assets/js/hero.js          lecture et pause accessibles du hero
database/schema.sql        schéma MySQL des demandes de devis
tools/tests/               tests CLI ciblés
tools/media-manager/       outil local d’inventaire des médias
storage/                   données locales ignorées par Git
projet/                    PRD, décisions, suivi et backlog
instructions/              règles de travail du kit
```

Les fichiers internes (`app`, `database`, `projet`, `instructions`, `tools`, `storage`) sont refusés par [`.htaccess`](./.htaccess) lorsqu’Apache est utilisé. `app/config.local.php` et les fichiers de stockage ne doivent pas être publiés.

## Git et déploiement

La politique du propriétaire impose la branche `main` exclusivement : pas de branche secondaire, de pull request, de worktree ou de force-push. Avant toute écriture :

```powershell
git branch --show-current
git status -sb
```

Si la branche n’est pas `main`, arrêtez-vous sans changer de branche automatiquement. Les commits et les push nécessitent un accord explicite.

Aucune commande de déploiement n’est fournie dans les fichiers du projet. La publication sur l’hébergement réel, les capacités PHP/MySQL, TLS, les sauvegardes et la restauration doivent être vérifiées séparément et autorisées par le propriétaire. Le serveur PHP intégré ne sert qu’au développement local.

## Documents utiles

- [`projet/PRD.md`](./projet/PRD.md) : objectifs produit et critères d’acceptation ;
- [`projet/NOW.md`](./projet/NOW.md) : tâche active, preuves et limites actuelles ;
- [`projet/BACKLOG.md`](./projet/BACKLOG.md) : backlog unique ;
- [`projet/DECISIONS.md`](./projet/DECISIONS.md) : choix de stack, hébergement et médias ;
- [`database/schema.sql`](./database/schema.sql) : structure de la table de devis ;
- [`tools/media-manager/README.md`](./tools/media-manager/README.md) : fonctionnement détaillé du gestionnaire de médias.

Les tarifs, les zones précises, les coordonnées et les mentions légales restent marqués `[À CONFIRMER]` dans le contenu du projet. Ne les remplacez par des informations réelles qu’après validation du propriétaire.
