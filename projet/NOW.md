# NOW — tâche active unique

```text
ID : TASK-003
Statut : TESTED
Objectif utilisateur (1 phrase) : Obtenir un squelette PHP 8.3 natif + MySQL servable localement, avec l'accueil FR et son hero vidéo réel.
Résultat observable et test d'acceptation : `php -S 127.0.0.1:8123` sert `/` en 200 ; le HTML contient `<video>` avec `hero-video.webm`, `hero-video.mp4`, poster local, bouton pause et respect de `prefers-reduced-motion` ; aucun secret réel dans les fichiers suivis.
Pourquoi maintenant / priorité : ADR-001 tranché par le propriétaire (option A), TASK-002 terminée : le socle technique est la dépendance de toutes les tâches suivantes.
Département et fichiers réservés : `index.php`, `.htaccess`, `app\**`, `assets\**`, `database\**`, `.gitignore`, `README.md`. Interdits d'écriture : `hero-video.webm`, `instructions\**`, `projet\**` (hors ce fichier).
Agent auteur IA A/B : IA A (seule IA ; aucune relecture IA B inventée).
GitHub issue CLAIM : aucune (dépôt Git non initialisé, ADR-003).
HEAD/base SHA : aucun dépôt Git. Référence média : `hero-video.webm` = 3 149 260 octets, SHA-256 `9A90BDE6…741A627`.
Dépendance/accord propriétaire : ADR-001 option A validée le 2026-11-10 ; vérification des capacités Hostinger reportée à la publication.
Tests prévus / réellement exécutés : **exécutés le 2026-11-10** — `php -l` sur 14 fichiers PHP (0 erreur) ; `php -S 127.0.0.1:8125` : `/` 200 (6 116 o), `/prestations` 200, `/tarifs` 200, `/zones` 200, `/devis` 200, `/mentions-legales` 200, `/absent` **404**, CSS `text/css` (9 673 o), JS `application/javascript`, `hero-video.webm` `video/webm` (3 149 260 o), `hero-video.mp4` `video/mp4` (704 735 o), poster `image/jpeg` (48 511 o) ; `POST /devis` → **503** sans stockage ; HTML accueil : 1 balise `<video>`, poster, 2 sources (WebM + MP4), bouton pause, `lang="fr"`, aucune `Warning`/`Fatal` ; aucun `app/config.local.php`, aucun secret réel.
Blocage P0 : aucun. MySQL absent localement : persistance du formulaire (TASK-006) non testable ici.
Prochain petit pas unique : TASK-006 — implémenter le traitement POST `/devis` (validation serveur, CSRF, anti-spam natif, insertion MySQL préparée), test HTTP négatif possible sans base, insertion réelle à valider sur un MySQL de recette.
```

**TASK-002 — preuves réelles (2026-11-10) :**
- `ffprobe hero-video.webm` → `vp9 3840x2160 60/1` + `opus`, `matroska,webm`, 10,056 s, 3 149 260 octets.
- MP4 produit → code 0, `h264 1920x1080 30/1` + `aac`, 10,048 s, **704 735 octets**.
- Poster produit → code 0, `mjpeg 1920x1080`, **48 511 octets**.
- Source **intacte** : 3 149 260 octets, 2026-09-13 14:27:43, SHA-256 `9A90BDE6…741A627` inchangé.

**Règles :** fichier unique, pas de journal. Seul le détenteur du CLAIM édite ce fichier.

