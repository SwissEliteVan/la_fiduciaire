# Backlog unique — La Fiduciaire

**Statut :** actif depuis le 2026-11-10. Un seul backlog. Statuts : `IDEA`, `READY`, `CLAIMED`, `TESTED`, `BLOCKED`, `DONE`. Parmi `READY` : priorité = impact utilisateur × confiance / (effort × risque), échelle 1–5 (heuristique).

| ID | P | Résultat attendu | Dépendances | Test/critère | Effort | Statut |
|---|---|---|---|---|---|---|
| TASK-001 | P3 | Vérifier les capacités réelles du compte Hostinger au moment de publier (PHP 8.3, MySQL, `.htaccess`, sauvegardes) | décision : reporté à la publication | preuve sur compte réel dans `architecture/HEBERGEMENT.md` | S | BLOCKED |
| TASK-002 | P0 | Médias hero : `ffprobe` sur `hero-video.webm`, MP4 H.264 + poster JPG à côté | autorisation média (accordée) | fichiers produits + checksums, source inchangée | S | DONE |
| TASK-003 | P0 | Squelette technique PHP 8.3 natif + MySQL (structure, config hors secrets, accueil FR locale, hero intégré) | ADR-001 option A (actée) | `php -S` sert `/` en 200, aucun secret versionné, F001 vérifié | M | DONE |
| TASK-004 | P1 | Hero accessible intégré : vidéo + poster + `prefers-reduced-motion` + bouton pause + repli | TASK-002, TASK-003 | critère F001 | M | BLOCKED |
| TASK-005 | P1 | Contenu FR : prestations détaillées, tarifs, zones desservies, mentions légales | réponses propriétaire | relecture propriétaire (F002/F003/F004/F006) | M | BLOCKED |
| TASK-006 | P1 | Formulaire de devis → MySQL : validation serveur, CSRF, anti-spam natif, messages d'erreur, confirmation | TASK-003 | tests unitaires + HTTP locaux PASS ; insertion réelle MySQL à prouver en recette | L | TESTED |
| TASK-007 | P2 | SEO technique et accessibilité : titres, meta, JSON-LD local, contraste, navigation clavier | TASK-004 | checklist a11y + inspection HTML (sans capture) | M | IDEA |
| TASK-008 | P2 | Tests ciblés et lint/CI locale du projet (après `git init` autorisé) | TASK-003 | commande de test reproductible, code de sortie 0 | M | IDEA |
| TASK-009 | P3 | EN/DE/IT sur accueil, services et tarifs | TASK-005 | parité des clés + `hreflang` | L | IDEA |
| TASK-010 | P3 | Application de gestion Laravel (hors périmètre vitrine) | décision propriétaire | — | L | IDEA |

**Notes :** TASK-001 conditionne le choix définitif entre les options A et B de l'ADR-001 ; aucun démarrage de code avant cette réponse. Toute tâche `BLOCKED` le reste jusqu'à preuve ou accord, sans contournement. **TASK-006 :** code livré et tests locaux PASS ; l'insertion MySQL réelle reste `NON EXÉCUTÉE` (`pdo_mysql` et MySQL absents localement), donc statut `TESTED` et non `DONE`.
