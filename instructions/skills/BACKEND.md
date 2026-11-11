# Skill — backend local à l'hébergeur

## Sélection

- Sans données métier : **pas de serveur d'application** ; si formulaire dans site statique, traitement serveur minimal sur l'hébergement retenu (profil strict : PHP minimal Hostinger/Infomaniak) et tests.
- Avec CRUD/auth/permissions : base et runtime réellement disponibles sur l'hébergement retenu, framework seulement si compatible versions/extensions/tâches planifiées et maintenance justifiées. Profil strict : PHP/MySQL/MariaDB de l'hébergement, Laravel/Symfony si compatibles, Node managé Hostinger si besoin réel et support du compte confirmé.

## Contrôles obligatoires

1. Modéliser entité + propriétaire + politique d'accès. Pour chaque route : authentifier, autoriser, valider l'entrée, gérer erreur sans révéler secrets, journaliser minimum ; tests d'accès inter-comptes.
2. Utiliser requêtes paramétrées/ORM, transactions sur mutations, contraintes SQL, index et migrations **réversibles** quand possible.
3. Cookies `Secure`, `HttpOnly`, `SameSite`, fixation session, CSRF, rate-limit, protections anti-bot proportionnées ; ne pas prendre l'en-tête `Origin` comme seule protection anti-CSRF/robot.
4. Uploads : MIME vérifié côté serveur, extension whitelist, taille maximale, virus si pertinent, traitement de l'image sans exécuter le contenu, stockage **privé** sur hébergement, noms aléatoires.
5. E-mails : vérifier configuration d'envoi de l'hébergement, délivrabilité réelle avec boîte témoin, adresse expéditeur alignée et SPF/DKIM/DMARC ; ne pas considérer `mail()` réussi comme e-mail reçu.
6. Maintenance : jobs cron autorisés, logs épurés des PII, sauvegarde/restauration, rétention et suppression conforme aux objectifs.

## Tests minimum

Cas normal, cas invalide, injection, CSRF, quotas/race de formulaire, échecs serveur, permissions négatives, export cross-tenant, failover e-mail et restauration de backup non destructrice.

**Interdits avec le profil `strict-local-web`** : Supabase, Firebase, Auth0 obligatoire, CDN de fichiers ou API d'e-mail tierce indispensable, serveur de base de données hébergé ailleurs. Hors profil strict : toute dépendance externe indispensable est justifiée dans un ADR et acceptée par le propriétaire. Obtenir avis du propriétaire pour toute modification de permissions ou déplacement de données.
