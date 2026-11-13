# Backlog unique — La Fiduciaire

**Statut :** actif depuis le 2026-11-10. Un seul backlog. Statuts : `IDEA`, `READY`, `CLAIMED`, `BLOCKED`, `DONE`. Parmi `READY` : priorité = impact utilisateur × confiance / (effort × risque), échelle 1–5 (heuristique).

| ID | P | Résultat attendu | Dépendances | Test/critère | Effort | Statut |
|---|---|---|---|---|---|---|
| TASK-001 | P3 | Vérifier les capacités réelles du compte Hostinger au moment de publier (PHP 8.3, MySQL, `.htaccess`, sauvegardes) | décision : reporté à la publication | preuve sur compte réel dans `architecture/HEBERGEMENT.md` | S | BLOCKED |
| TASK-002 | P0 | Médias hero : `ffprobe` sur `hero-video.webm`, MP4 H.264 + poster JPG à côté | autorisation média (accordée) | fichiers produits + checksums, source inchangée | S | DONE |
| TASK-003 | P0 | Squelette technique PHP 8.3 natif + MySQL (structure, config hors secrets, accueil FR locale, hero intégré) | ADR-001 option A (actée) | `php -S` sert `/` en 200, aucun secret versionné, F001 vérifié | M | DONE |
| TASK-004 | P1 | Hero accessible intégré : vidéo + poster + `prefers-reduced-motion` + bouton pause + repli | TASK-002, TASK-003 | critère F001 | M | DONE |
| TASK-005 | P1 | Contenu FR : prestations détaillées, tarifs, zones desservies, mentions légales | réponses propriétaire | relecture propriétaire (F002/F003/F004/F006) | M | BLOCKED |
| TASK-006 | P1 | Formulaire de devis : validation serveur, anti-spam natif, messages d'erreur, confirmation après écriture | TASK-003 | `devis-sans-sql.py` PASS ; MySQL optionnel non rejoué | L | DONE |
| TASK-007 | P2 | SEO technique et accessibilité : titres, meta, JSON-LD local, contraste, navigation clavier | TASK-004 | checklist a11y + inspection HTML (sans capture) | M | IDEA |
| TASK-008 | P2 | Tests ciblés et lint/CI locale du projet | TASK-003 | commande de test reproductible, code de sortie 0 | M | DONE |
| TASK-009 | P3 | EN/DE/IT sur accueil, services et tarifs | TASK-005 | parité des clés + `hreflang` | L | IDEA |
| TASK-010 | P3 | Application de gestion Laravel (hors périmètre vitrine) | décision propriétaire | — | L | IDEA |
| TASK-011 | P0 | Charte LF, prompt de finalisation et MVP de transmission des pièces | demande propriétaire, ADR-004 | test HTTP de bout en bout avec deux dossiers et le cabinet | L | DONE |
| TASK-012 | P0 | Recette hébergeur : HTTPS, scanner réel, sauvegarde/restauration, comptes cabinet et textes publics | accès et configuration validés | dérouler la recette avant d’activer les pièces réelles | L | BLOCKED |
| TASK-013 | P0 | Appliquer visiblement la charte et rendre les devis utilisables sans SQL | demande propriétaire, ADR-005 | recette HTTP/CLI sans PDO ni config locale | M | DONE |
| TASK-014 | P0 | Portail client/cabinet sans SQL : comptes, métadonnées, pièces et statuts en stockage privé | TASK-011, ADR-006, ADR-007 | `python tools/tests/portail-sans-sql.py` PASS | L | DONE |
| TASK-015 | P1 | Consultation des demandes de devis et invitation client depuis l’interface cabinet | TASK-013, TASK-014 | test HTTP : code unique, demandes visibles au cabinet, refus client | S | DONE |
| TASK-016 | P0 | Recette MySQL du portail (option) : migration, insertion, scanner réel, restauration | TASK-014, TASK-001 | `python tools/tests/portail-http.py` sur base de recette | M | BLOCKED |
| TASK-017 | P1 | Recette visuelle mobile/desktop et clavier (captures non autorisées à ce stade) | TASK-014 | inspection statique faite ; recette navigateur à autoriser | M | IDEA |

**Notes :** le test local n’établit pas la compatibilité de l’hébergeur. TASK-001 et TASK-012 sont des prérequis à l’ouverture, pas au développement local du MVP. **TASK-014/015** sont vérifiées sur Windows avec PHP sans `pdo_mysql` ni `pdo_sqlite` ; la recette MySQL (TASK-016), l’antivirus réel, les permissions Unix et la restauration restent à exécuter sur l’environnement cible. Aucun déploiement implicite.
