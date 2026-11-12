# Gestionnaire local de médias — La Fiduciaire

Outil local, sans dépendance runtime, réservé à `127.0.0.1`. Il inventorie les médias
du projet, détecte leurs usages, permet de décider CONSERVER / SUPPRIMER / REMPLACER /
NON DÉCIDÉ, valide un contrat média et applique les changements avec sauvegardes.

## Lancement

```powershell
powershell -ExecutionPolicy Bypass -File tools/media-manager/lancer.ps1
```

ou :

```bash
php -d upload_max_filesize=256M -d post_max_size=300M -S 127.0.0.1:8124 tools/media-manager/server.php
```

Interface : <http://127.0.0.1:8124/>. Le routeur ne sert que `public/` et l'API ;
aucun fichier du site n'est exposé par cette origine.

## Fichiers

- `server.php` : routeur du serveur intégré (API + interface), garde localhost.
- `src/Inventory.php` : parcours, empreintes SHA-256, dimensions (`ffprobe`, en-têtes),
  regroupement des variantes, doublons, usages exacts/partiels.
- `src/Usage.php` : (fonctions d'usages dans `Inventory.php`) recherche des références.
- `src/Contract.php` : contrat image/vidéo/audio, règles bloquantes et avertissements.
- `src/Upload.php` : réception du sélecteur de fichiers, signature binaire, dossier
  temporaire `storage/media-manager/imports/`.
- `src/Apply.php` : validation, sauvegarde, remplacement, mise à jour des références,
  variantes AVIF/WebP avec mesure du gain, corbeille, `php -l`, test HTTP, rollback.
- `src/api.php` : routes JSON, service des médias avec Range, jeton CSRF.
- `public/` : interface (HTML/CSS/JS vanilla, responsive, `prefers-reduced-motion`).
- `tests/inventory-check.php` et `tests/contract-check.php` : preuves textuelles.

## Données et garanties

- JSON de gestion : `storage/media-manager/media-manager.json` (écriture atomique,
  instantanés dans `storage/media-manager/history/`).
- Sauvegardes, corbeille et rapports : `storage/media-manager/backups/<id>/` et
  `storage/media-manager/logs/apply-<id>.json`.
- Aucune suppression avant validation **et** saisie explicite de `APPLIQUER`.
- Un média encore référencé n'est jamais supprimé ; les fichiers retirés vont en
  corbeille (restaurables via l'historique d'application).
- Les NON DÉCIDÉS et les CONSERVÉS ne sont jamais modifiés.
- Les imports restent dans le dossier temporaire tant que l'application n'est pas
  confirmée ; un remplacement abandonné peut être supprimé depuis l'interface.
- Une règle de contrat bloquante exige soit un correctif, soit une dérogation
  motivée enregistrée par l'utilisateur (jamais automatique).
