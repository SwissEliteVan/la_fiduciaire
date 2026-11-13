# NOW — tâche active unique

**TASK-016 · Refonte visuelle dérivée du service (livré, recette locale).**

- Date : 2026-11-13. Branche unique `main`, aucun commit, aucun déploiement.
- Périmètre : fiche service et dérivation dans `projet/CHARTE-GRAPHIQUE.md` ; tokens 8 px et échelle typographique 1,25 dans `brand.css` ; cinq états de contrôle, focus lisible sur fond bleu, case à cocher 24 × 24, `forced-colors` dans `app.css` et `portal.css` ; hero mobile-first avec signaux de confiance ; registre de prestations ; pied de page avec plan du site et coordonnées conditionnelles ; JSON-LD `AccountingService` et `og:*` dans `layout.php`.
- Preuves exécutées : `php -l` sur 29 fichiers PHP (0 erreur) ; `python tools/tests/devis-sans-sql.py` PASS ; `python tools/tests/portail-sans-sql.py` PASS ; rendu local des six pages en 200, un seul `h1` par page, aucun `[À CONFIRMER]` affiché, `/espace-client` toujours en 503 quand le portail est fermé ; contrastes recalculés (texte ≥ 6,34:1, focus blanc sur bleu 11,44:1).
- NON EXÉCUTÉ : Lighthouse et captures (interdits sans accord, AGENTS §14), recette clavier et lecteur d’écran réels, mobile matériel, hébergement.
- Décision en attente : ordre des `<source>` vidéo (WebM 3,15 Mo avant MP4 704 Ko) et variantes AVIF/WebP du poster ; aucun média n’a été régénéré.

**TASK-014 / TASK-015 · Portail sans SQL et consultation cabinet (livré, recette Windows).**

- Date : 2026-11-13. Branche unique `main`. Livraison fonctionnelle : `74b045f22026a0fbd138a70bde3df211ddabd267`. Fusion de `origin/main` (`f4c1b08`) : `ece9510`. Aucun déploiement.
- Objectif : rendre les trois parcours utilisables sans MySQL ni SQLite, avec la charte LF, et prouver l’enregistrement unique du devis, le dépôt/téléchargement client, le cloisonnement entre clients et le statut cabinet visible par le bon client.
- Livré :
  - stockage privé JSON verrouillé + pièces hors racine web (`app/portal/store.php`, `store-files.php`, `store-mysql.php`, `paths.php`) ; `portal.backend = files` pour la recette, MySQL conservé en option ;
  - devis sans SQL réparé sous Windows (chemins, permissions POSIX conservées sur Unix, écritures atomiques) ;
  - espace cabinet : liste des demandes de devis, invitation avec code affiché une seule fois, dossiers, téléchargement, statuts « Traité » / « À remplacer » ;
  - espace client : activation 24 h, dépôt PDF/JPG/PNG réel, historique, téléchargement contrôlé ;
  - vues `portal-invite.php`, `portal-quotes.php` et composants de charte (panneaux, barre d’actions, états, code, messages) ; logos officiels et vidéo hero conservés avec poster et bouton pause.
- Preuves exécutées le 2026-11-13 (Windows, PHP 8.5.5, sans `pdo_mysql` ni `pdo_sqlite`) :
  - `php -l` sur 43 fichiers PHP : 0 erreur ;
  - `python tools/tests/devis-sans-sql.py` : PASS (devis unique, confirmation après écriture réelle, CSRF, validation, taille, honeypot, quota, CLI privée) ;
  - `python tools/tests/portail-sans-sql.py` : PASS (invitation + code unique, activation, devis unique et consultation cabinet, refus client, dépôt réel de 2 pièces, téléchargement à l’identique, second client sans vue et 404, statuts « Traité » et « À remplacer » visibles côté client, reset/révocation, JSON cohérent, aucun warning ni donnée personnelle dans les journaux) ;
  - vérification statique charte/mobile : logos LF, `brand.css`, `vitrine.css`, `portal.css`, `@media (max-width: 720px)`, `@media (max-width: 640px)`, contrôles de 44 px ; aucune capture (AGENTS.md §14).
- NON EXÉCUTÉ : recette MySQL (`portail-http.py`, `pdo_mysql` absent), scanner antivirus réel (double de test local uniquement), permissions POSIX et ACL Linux réelles, Hostinger/Apache/TLS, sauvegarde/restauration, recette navigateur mobile/clavier, déploiement.
- Limites : sous Windows, PHP ne restitue pas les ACL NTFS ; les contrôles 0700/0600 restent obligatoires sur Linux. La limite PHP locale `upload_max_filesize` (2 Mo par défaut) s’applique ; la recette lance le serveur avec 10 Mo. Le JSON convient au MVP, pas à plusieurs instances PHP.
- Recette locale sans SQL :
  ```powershell
  Copy-Item app/config.recette.php app/config.local.php
  php tools/portal/comptes.php cabinet cabinet@example.invalid
  php -S 127.0.0.1:8123 index.php
  ```
  puis `http://127.0.0.1:8123/espace-client/activation` avec le code affiché par la commande. Tests : `python tools/tests/devis-sans-sql.py` et `python tools/tests/portail-sans-sql.py`.
- README local non commité conservé à l’identique et inclus dans la livraison : SHA-256 `11807EAE89B456E003E2A7A3D7D623813D3A4C0FD7A320ADCF380FFCFD634A02`. Aucune modification de son contenu.
- Aucune seconde IA de revue mandatée.

## Passation de TASK-013

- Demande du propriétaire : appliquer visiblement la charte et rendre le site utilisable sans SQL. Coordination [#2](https://github.com/SwissEliteVan/la_fiduciaire/issues/2).
- Livré : accueil en deux panneaux, bleu LF et filets verticaux, logo agrandi, vidéo locale conservée, formulaire assorti. Le lien vers le portail reste masqué tant que celui-ci est désactivé.
- Le devis est actif par défaut, stocké hors racine publique en JSON privé. Consultation par CLI ou gestionnaire de fichiers. Aucun e-mail automatique. Aucun accès public au dossier. Le mode MySQL est toujours disponible sur configuration explicite.
- Recette HTTP/CLI avec PHP 8.3 sans PDO ni configuration locale, sur fichiers fictifs. Dépôt valide et unique, validation, CSRF, honeypot, taille, quota et défaut de stockage vérifiés. Hébergement réel, mobile visuel et clavier non vérifiés. Rien n’a été déployé.

## Passation de TASK-011

- Base de départ : `6b4a51098e1f9bc6b4a547aaaed8fbc4d745e329`, branche unique `main`, coordination [#1](https://github.com/SwissEliteVan/la_fiduciaire/issues/1).
- Livré dans le dépôt : charte LF, prompt de finalisation, portail PHP/MySQL sur invitation avec deux rôles, dépôt et suivi, devis enregistré si activé.
- Vérifié localement le 13 novembre 2026, PHP 8.3.36 et MariaDB 11.4.13 : lint PHP, recette HTTP avec deux dossiers clients et un compte cabinet, formats/quantité/taille, double de test du scanner (acceptation/refus/panne), contrôles d’accès, statuts, révocation, devis invalide/valide sans doublon. Les logos fournis sont inchangés et les paires de couleurs clés ont été mesurées.
- Non vérifié : Hostinger/Apache réel, HTTPS et scanner réels, restauration sur l’hébergeur, recette visuelle et clavier. Le contrôle média signale encore les variantes du poster et le poids du WebM historique. Les PNG de marque sont conservés comme fournis, sans variantes photo. Aucune seconde IA de revue n’a été mandatée.
- À ce jalon historique, les fonctionnalités étaient désactivées par défaut. Aucun déploiement, aucune donnée réelle. Le push n’autorise pas la mise en production.
