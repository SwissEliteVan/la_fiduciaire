# La Fiduciaire — vitrine et portail client

Site vitrine de **La Fiduciaire** (indépendants, TPE et PME de Suisse romande) :
présentation des prestations, tarifs, zones desservies et demande de devis. Le portail
sur invitation permet le dépôt de pièces par dossier, le suivi et le traitement côté cabinet.

- [Charte graphique](projet/CHARTE-GRAPHIQUE.md)
- [Prompt de finalisation](projet/PROMPT-FINALISATION.md)
- [État de la recette](projet/NOW.md)

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
assets/css/               marque, vitrine responsive et portail
assets/brand/             logotype horizontal et icône LF fournis
assets/js/hero.js         pause/lecture du hero, respect du mouvement réduit
assets/media-geres/       emplacement prévu pour les médias gérés (vide au départ)
database/schema.sql       table des devis
database/portal.sql       migration additive des dossiers, comptes, pièces et événements
app/portal/               accès, dépôt, téléchargement privé, historique et devis
tools/portal/comptes.php  gestion CLI des invitations et révocations
tools/tests/              recette HTTP du portail
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

## Portail et devis en recette

L’espace client et le formulaire sont désactivés par défaut. Utiliser uniquement des comptes et fichiers fictifs en recette.

1. Sur une base MySQL/MariaDB dédiée, appliquer les migrations avec un compte autorisé à créer les tables.

   ```bash
   mysql -u <utilisateur> -p <base> < database/schema.sql
   mysql -u <utilisateur> -p <base> < database/portal.sql
   ```

2. Copier `app/config.example.php` en `app/config.local.php`, renseigner la base et générer le secret local avec `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`. Protéger le fichier de configuration et ne jamais le committer. Renseigner `portal.storage_dir` avec un répertoire absolu dédié, appartenant à l’utilisateur PHP, en mode 0700, hors projet et hors racine web. Le serveur doit reconnaître HTTPS via sa configuration de confiance, sans se fier aux en-têtes fournis par le client.
3. Renseigner `portal.scanner` avec le chemin absolu vers `clamscan` installé hors du projet doté de signatures tenues à jour. Vérifier `proc_open` et les codes de sortie 0 (accepté), 1 (refusé), autre (échec). Aucun enregistrement si le contrôle manque, refuse le fichier ou échoue. Les exécutions sont séquentielles, avec une limite de 20 s par fichier. Tester le débit et les délais PHP/proxy avec un lot de cinq fichiers avant ouverture.
4. En recette, activer `portal.enabled` et/ou `quote_enabled` selon le parcours à tester. Le devis utilise aussi le secret et la table des limites de `portal.sql`. Dimensionner PHP et le serveur web, par exemple `upload_max_filesize=10M`, `post_max_size=55M`, `max_file_uploads=6` au minimum pour pouvoir rejeter un sixième fichier. Vérifier aussi les sessions, le dossier temporaire et l’espace disque. Le portail limite à cinq fichiers de 10 Mio par dépôt et 1 Gio par dossier.

### Ouvrir ou révoquer un accès

```bash
php tools/portal/comptes.php nouveau-client client@example.invalid 'Dossier de recette'
php tools/portal/comptes.php cabinet cabinet@example.invalid
php tools/portal/comptes.php client-dossier autre@example.invalid 1
php tools/portal/comptes.php reinitialiser client@example.invalid
php tools/portal/comptes.php revoquer client@example.invalid
```

L’opérateur communique le code (24 h, usage unique) en privé après avoir vérifié l’identité du destinataire. Le client utilise `/espace-client/activation`, puis `/espace-client/connexion`. Ne pas inclure le code dans une URL ni un journal. Une réinitialisation invalide le mot de passe et les sessions en cours. Le rôle cabinet couvre tous les dossiers. Aucun envoi d’e-mail automatique, ni OCR.

### Vérifier avec des données fictives

Sur une base et un stockage **jetables** uniquement, utiliser le chemin absolu du double de test `tools/tests/scanner-factice.sh` comme scanner. Ce script ne détecte aucun logiciel malveillant. Ne jamais le configurer en production. Démarrer ensuite le serveur, puis exécuter la recette HTTP.

```bash
php -d upload_max_filesize=11M -d post_max_size=55M -d max_file_uploads=6 -S 127.0.0.1:8123 index.php
python3 tools/tests/portail-http.py 8123
```

### Avant ouverture réelle

- Vérifier sur l’hébergement de recette PHP 8.3, PDO MySQL, Fileinfo, HTTPS, protections Apache, accès CLI, stockage privé et analyse effective des fichiers. Aucun test sur données réelles.
- Recetter l’ergonomie mobile et au clavier. Valider la notice de confidentialité, les coordonnées, la conservation des pièces et des journaux, ainsi que l’authentification renforcée du rôle cabinet.
- Préparer des sauvegardes privées et protégées. Mettre les écritures en pause pendant la sauvegarde coordonnée de la base et du stockage. Restaurer les deux ensemble dans un environnement isolé et comparer les nombres de pièces, leurs tailles/empreintes et leurs droits avant réouverture. Surveiller les quotas disque et la croissance des tables. Planifier la purge de `lf_limites` au-delà des fenêtres de limitation sans toucher aux pièces ni aux événements métier.
- En cas de retour arrière, désactiver les deux fonctions avant toute intervention. Conserver la base et le stockage privés, puis restaurer le code autorisé sans supprimer de données. Un push sur `main` n’est pas un déploiement.
