# PRD — La Fiduciaire (vitrine et portail)

**Statut :** rempli le 2026-11-10 par IA A à partir des déclarations du propriétaire. Toute valeur non fournie reste `[À CONFIRMER]` ; aucun prix, droit ni promesse réglementaire n'est inventé.

## Mission

Nom : **La Fiduciaire** · Cible : indépendants, TPE et PME de **Suisse romande** · Problème prioritaire : trouver un fiduciaire de proximité lisible sur les prestations, les honoraires et la zone desservie, sans démarche téléphonique préalable · Promesse mesurable : **générer des demandes de devis qualifiées** · Hébergeur : **Hostinger Business mutualisé (PHP 8.3 + MySQL)** — profil `strict-local-web` **non activé** (aucun `projet/PROFIL.md`). La vitrine et les devis démarrent sans SQL.

Prestations annoncées : comptabilité, TVA, salaires, pilotage, création d'entreprise, bouclement.

## Parcours clé

1. Arrivée sur l'accueil (hero vidéo locale + promesse + CTA « Demander un devis »).
2. Message de confiance (métier, zone romande, prestations listées).
3. Prestations détaillées → tarifs/honoraires → zones desservies.
4. Formulaire : type de demande, activité, canton, coordonnées, consentement.
5. Enregistrement de la demande dans le **stockage privé sans SQL** par défaut, puis confirmation affichée.
6. Exceptions à couvrir : champ invalide, erreur serveur/DB, double envoi, spam.

## MVP et acceptation

| ID | Utilisateur | Besoin | Acceptation étant donné/quand/alors | Mesure | Statut |
|---|---|---|---|---|---|
| F001 | visiteur | Comprendre la promesse | **Étant donné** l'accueil chargé, **quand** la page est visible, **alors** hero avec **vidéo locale** (`hero-video.webm`), poster local, CTA visible, bouton pause accessible, respect de `prefers-reduced-motion`, rendu mobile et desktop | contrôle technique + a11y | TODO |
| F002 | visiteur | Voir les prestations | Les 6 prestations annoncées sont présentées avec un texte propre à chacune | relecture propriétaire | TODO |
| F003 | visiteur | Connaître les honoraires | Une page tarifs affiche les modalités ou fourchettes validées par le propriétaire, sans promesse non validée | relecture propriétaire | TODO (`[À CONFIRMER]`) |
| F004 | visiteur | Savoir si sa région est couverte | La zone Suisse romande est listée explicitement (cantons/villes validés) | relecture propriétaire | TODO (`[À CONFIRMER]`) |
| F005 | prospect | Demander un devis | **Étant donné** le formulaire rempli valide, **quand** j'envoie, **alors** la demande est enregistrée hors web, une confirmation s'affiche, et une saisie invalide produit un message d'erreur sans perte de données | test d’intégration PHP/stockage privé | TESTÉ EN LOCAL |
| F006 | visiteur | Informations légales | Mentions légales accessibles depuis toutes les pages (raison sociale, IDE/RC, adresse, contact) | relecture propriétaire | TODO (`[À CONFIRMER]`) |
| F007 | visiteur | Naviguer en français | Contenu FR complet, aucune chaîne manquante ; textes critiques relus | contrôle i18n | TODO |

## Phase portail — transmission des pièces

Le prospect reste sur le parcours vitrine. Le client existant reçoit un code du cabinet, configure son mot de passe et accède à son dossier. Il classe ses pièces par mois et catégorie, dépose jusqu’à cinq fichiers PDF/JPG/PNG de 10 Mo chacun et retrouve les documents réellement reçus. Le cabinet peut filtrer les dépôts, les télécharger et les marquer « Traité » ou « À remplacer ».

| ID | Acceptation | État |
|---|---|---|
| F008 | Un code à usage unique expire, peut être renouvelé, et une révocation ferme les sessions | Recette HTTP locale |
| F009 | Un lot valide est conservé hors racine web, avec trace en base et confirmation après enregistrement | Recette locale PHP/MariaDB, scanner factice |
| F010 | Deux dossiers ne voient ni ne téléchargent leurs pièces respectives. Seul le cabinet change les statuts | Recette HTTP locale |
| F011 | La charte, l’espace client et le formulaire de devis fonctionnent sans dépendance front externe | Inspection HTML et contrastes, recette visuelle à faire |
| F012 | Hébergement cible validé, scanner réel, sauvegarde/restauration et parcours de récupération éprouvés avant ouverture | À vérifier sur l’hébergement |

## Hors périmètre de ce MVP

- Pas de migration Laravel, d’inscription publique, de paiement, d’OCR, de connecteur CRM ni d’envoi de pièces par e-mail.
- Pas de SaaS externe indispensable. Le portail reste désactivé par défaut. Le devis est actif par défaut en mode fichiers.
- FR d’abord. Les autres langues ne sont pas nécessaires à cette phase.
- Gestion des comptes par CLI dans le MVP. Le rôle cabinet a accès à tous les dossiers. Authentification renforcée et périmètre par collaborateur à décider avant ouverture.

## Contraintes techniques

- Hébergement mutualisé Hostinger Business : **PHP 8.3** et le dossier privé doivent être confirmés sur le compte réel pour la vitrine, puis **MySQL** pour le portail (documentation fournisseur ≠ preuve).
- Le formulaire utilise un dossier privé hors racine web sans SQL par défaut. MySQL reste optionnel pour les devis et requis pour le portail. Validation et protections côté serveur.
- Vidéo hero : source locale `hero-video.webm` (fournie par le propriétaire, conservée à la racine du projet), dérivés autorisés : MP4 H.264 + poster JPG (périmètre média borné, voir `DECISIONS.md` ADR-002).

## Questions ouvertes (propriétaire)

1. `[À CONFIRMER]` **Tarifs** : fourchettes horaires, forfaits, mention TVA suisse.
2. `[À CONFIRMER]` **Zones** : cantons/villes exactes, prestations à distance ou non.
3. `[À CONFIRMER]` **Mentions légales** : raison sociale, IDE/RC, adresse, affiliation professionnelle, e-mail/téléphone publics.
4. `[À CONFIRMER]` **Indicateur** : volume mensuel de demandes visé (aucune cible chiffrée fournie à ce jour).

## DoD

Voir `instructions/projet/DEFINITION-OF-DONE.md` : acceptation par fonctionnalité, preuves réelles, publication soumise à accord distinct.
