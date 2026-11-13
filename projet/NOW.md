# NOW — tâche active unique

```text
ID : TASK-006
Statut : TESTED (insertion MySQL NON EXÉCUTÉE localement)
Objectif utilisateur (1 phrase) : Un prospect envoie une demande de devis valide ; elle est enregistrée en MySQL et une confirmation s'affiche, une saisie invalide produisant une erreur sans perte de données.
Résultat observable et test d'acceptation : `GET /devis` 200 avec jeton CSRF et champ piège ; POST sans jeton 403 ; POST invalide 422 avec valeurs conservées et contenu échappé ; POST valide 503 si base absente (reprise sans fuite) et 303 + confirmation si base disponible ; 11e POST en 15 min 429 ; `PUT /devis` 405.
Pourquoi maintenant / priorité : prochain pas unique documenté dans NOW ; aucune dépendance nouvelle, aucune migration de stack.
Département et fichiers réservés : `index.php`, `app/bootstrap.php`, `app/devis.php` (nouveau), `app/views/devis.php`, `app/views/layout.php`, `app/content/fr.php`, `app/config.example.php`, `assets/css/app.css`, `tools/tests/**` (nouveau), `README.md`, `projet/NOW.md`, `projet/BACKLOG.md`.
Agent auteur IA A/B : IA A (aucune relecture IA B réelle sollicitée dans cette session).
GitHub issue CLAIM : aucune (pas d'issue de coordination).
HEAD/base SHA : `6b4a51098e1f9bc6b4a547aaaed8fbc4d745e329` (main, arbre initialement propre).
Dépendance/accord propriétaire : aucune dépense ; MySQL de recette et hébergement réel toujours non vérifiés (TASK-001 BLOCKED).
Tests prévus / réellement exécutés : exécutés le 2026-11-13 — `php -l` sur 27 fichiers PHP : PASS, 0 erreur ; `php tools/tests/devis-check.php` : **25 PASS**, exit 0 ; `php tools/tests/devis-http.php http://127.0.0.1:8128` : **42 PASS**, exit 0 ; journaux du serveur : 0 warning PHP, 0 donnée personnelle.
NON EXÉCUTÉ : insertion MySQL réelle et double envoi avec succès (`pdo_mysql` et MySQL absents localement) ; QA navigateur, mobile, clavier et captures (non autorisées).
Blocage P0 : aucun blocage de code. Preuve d'insertion MySQL à faire sur un environnement disposant de `pdo_mysql`.
Prochain petit pas unique : vérifier sur une base MySQL de recette l'insertion réelle, la confirmation 303 et l'absence de doublon au rejeu de l'ancien jeton ; puis passer TASK-006 à DONE.
```

**TASK-002 — preuves réelles (2026-11-10) :**
- `ffprobe hero-video.webm` → `vp9 3840x2160 60/1` + `opus`, `matroska,webm`, 10,056 s, 3 149 260 octets.
- MP4 produit → code 0, `h264 1920x1080 30/1` + `aac`, 10,048 s, **704 735 octets**.
- Poster produit → code 0, `mjpeg 1920x1080`, **48 511 octets**.
- Source **intacte** : 3 149 260 octets, 2026-09-13 14:27:43, SHA-256 `9A90BDE6…741A627` inchangé.

**Règles :** fichier unique, pas de journal. Seul le détenteur du CLAIM édite ce fichier.

