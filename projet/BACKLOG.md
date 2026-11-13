# Backlog unique — La Fiduciaire

**Statut :** actif depuis le 2026-11-10. Un seul backlog. Statuts : `IDEA`, `READY`, `CLAIMED`, `BLOCKED`, `DONE`. Parmi `READY` : priorité = impact utilisateur × confiance / (effort × risque), échelle 1–5 (heuristique).

| ID | P | Résultat attendu | Dépendances | Test/critère | Effort | Statut |
|---|---|---|---|---|---|---|
| TASK-001 | P3 | Vérifier les capacités réelles du compte Hostinger au moment de publier (PHP 8.3, MySQL, `.htaccess`, sauvegardes) | décision : reporté à la publication | preuve sur compte réel dans `architecture/HEBERGEMENT.md` | S | BLOCKED |
| TASK-002 | P0 | Médias hero : `ffprobe` sur `hero-video.webm`, MP4 H.264 + poster JPG à côté | autorisation média (accordée) | fichiers produits + checksums, source inchangée | S | DONE |
| TASK-003 | P0 | Squelette technique PHP 8.3 natif + MySQL (structure, config hors secrets, accueil FR locale, hero intégré) | ADR-001 option A (actée) | `php -S` sert `/` en 200, aucun secret versionné, F001 vérifié | M | DONE |
| TASK-004 | P1 | Hero accessible intégré : vidéo + poster + `prefers-reduced-motion` + bouton pause + repli | TASK-002, TASK-003 | critère F001 | M | BLOCKED |
| TASK-005 | P1 | Contenu FR : prestations détaillées, tarifs, zones desservies, mentions légales | réponses propriétaire | relecture propriétaire (F002/F003/F004/F006) | M | BLOCKED |
| TASK-006 | P1 | Formulaire de devis → MySQL : validation serveur, anti-spam natif, messages d'erreur, confirmation | TASK-003 | test d'intégration, insertion réelle en base de recette | L | BLOCKED |
| TASK-007 | P2 | SEO technique et accessibilité : titres, meta, JSON-LD local, contraste, navigation clavier | TASK-004 | checklist a11y + inspection HTML (sans capture) | M | IDEA |
| TASK-008 | P2 | Tests ciblés et lint/CI locale du projet (après `git init` autorisé) | TASK-003 | commande de test reproductible, code de sortie 0 | M | IDEA |
| TASK-009 | P3 | EN/DE/IT sur accueil, services et tarifs | TASK-005 | parité des clés + `hreflang` | L | IDEA |
| TASK-010 | P3 | Application de gestion Laravel (hors périmètre vitrine) | décision propriétaire | — | L | IDEA |
| TASK-011 | P0 | Charte LF, prompt de finalisation et MVP de transmission des pièces | demande propriétaire, ADR-004 | test HTTP de bout en bout avec deux dossiers et le cabinet | L | DONE |
| TASK-012 | P0 | Recette hébergeur : HTTPS, scanner réel, sauvegarde/restauration, comptes cabinet et textes publics | accès et configuration validés | dérouler la recette avant d’activer les pièces réelles | L | BLOCKED |

**Notes :** le test local n’établit pas la compatibilité de l’hébergeur. TASK-001 et TASK-012 sont des prérequis à l’ouverture, pas au développement local du MVP. Aucun déploiement implicite.
