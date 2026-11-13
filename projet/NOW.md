# NOW — tâche active unique

**TASK-012 · Recette sur l’hébergement cible (en attente d’accès).** Tester d’abord un devis fictif sous HTTPS et sa lecture dans le dossier privé. Pour le portail, vérifier ensuite PHP 8.3/PDO MySQL, les limites de requête, les sessions, la CLI, le scanner et la sauvegarde/restauration. Confirmer les textes publics et la gestion des accès avant ouverture.

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
