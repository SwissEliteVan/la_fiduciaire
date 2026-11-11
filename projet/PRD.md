# PRD — La Fiduciaire (vitrine)

**Statut :** rempli le 2026-11-10 par IA A à partir des déclarations du propriétaire. Toute valeur non fournie reste `[À CONFIRMER]` ; aucun prix, droit ni promesse réglementaire n'est inventé.

## Mission

Nom : **La Fiduciaire** · Cible : indépendants, TPE et PME de **Suisse romande** · Problème prioritaire : trouver un fiduciaire de proximité lisible sur les prestations, les honoraires et la zone desservie, sans démarche téléphonique préalable · Promesse mesurable : **générer des demandes de devis qualifiées** · Hébergeur : **Hostinger Business mutualisé (PHP 8.3 + MySQL)** — profil `strict-local-web` **non activé** (l'hébergement PHP/MySQL est requis pour le formulaire, donc `projet/PROFIL.md` n'est pas créé).

Prestations annoncées : comptabilité, TVA, salaires, pilotage, création d'entreprise, bouclement.

## Parcours clé

1. Arrivée sur l'accueil (hero vidéo locale + promesse + CTA « Demander un devis »).
2. Message de confiance (métier, zone romande, prestations listées).
3. Prestations détaillées → tarifs/honoraires → zones desservies.
4. Formulaire : type de demande, activité, canton, coordonnées, consentement.
5. Enregistrement de la demande en **MySQL** + confirmation affichée.
6. Exceptions à couvrir : champ invalide, erreur serveur/DB, double envoi, spam.

## MVP et acceptation

| ID | Utilisateur | Besoin | Acceptation étant donné/quand/alors | Mesure | Statut |
|---|---|---|---|---|---|
| F001 | visiteur | Comprendre la promesse | **Étant donné** l'accueil chargé, **quand** la page est visible, **alors** hero avec **vidéo locale** (`hero-video.webm`), poster local, CTA visible, bouton pause accessible, respect de `prefers-reduced-motion`, rendu mobile et desktop | contrôle technique + a11y | TODO |
| F002 | visiteur | Voir les prestations | Les 6 prestations annoncées sont présentées avec un texte propre à chacune | relecture propriétaire | TODO |
| F003 | visiteur | Connaître les honoraires | Une page tarifs affiche les modalités ou fourchettes validées par le propriétaire, sans promesse non validée | relecture propriétaire | TODO (`[À CONFIRMER]`) |
| F004 | visiteur | Savoir si sa région est couverte | La zone Suisse romande est listée explicitement (cantons/villes validés) | relecture propriétaire | TODO (`[À CONFIRMER]`) |
| F005 | prospect | Demander un devis | **Étant donné** le formulaire rempli valide, **quand** j'envoie, **alors** la demande est enregistrée en MySQL, une confirmation s'affiche, et une saisie invalide produit un message d'erreur sans perte de données | test d'intégration PHP/MySQL | TODO |
| F006 | visiteur | Informations légales | Mentions légales accessibles depuis toutes les pages (raison sociale, IDE/RC, adresse, contact) | relecture propriétaire | TODO (`[À CONFIRMER]`) |
| F007 | visiteur | Naviguer en français | Contenu FR complet, aucune chaîne manquante ; textes critiques relus | contrôle i18n | TODO |

## Hors périmètre (phase vitrine)

- **Application de gestion Laravel** : prévue ultérieurement, explicitement **hors périmètre** de cette installation.
- Pas de comptes clients, pas d'authentification, pas d'espace documentaire, pas de paiement en ligne dans cette phase.
- Pas de SaaS externe indispensable (analytics tiers, anti-spam SaaS, stockage externe) : anti-spam natif côté serveur.
- EN/DE/IT : préparation technique i18n souhaitable, **traduction effective seulement si le temps le permet** ; FR seul reste acceptable pour la phase 1.
- Aucun back-office non validé.

## Contraintes techniques

- Hébergement mutualisé Hostinger Business : **PHP 8.3** et **MySQL** doivent être confirmés sur le compte réel (documentation fournisseur ≠ preuve).
- Le formulaire doit écrire en **MySQL** ; validation et protections côté serveur.
- Vidéo hero : source locale `hero-video.webm` (fournie par le propriétaire, conservée à la racine du projet), dérivés autorisés : MP4 H.264 + poster JPG (périmètre média borné, voir `DECISIONS.md` ADR-002).

## Questions ouvertes (propriétaire)

1. `[À CONFIRMER]` **Tarifs** : fourchettes horaires, forfaits, mention TVA suisse.
2. `[À CONFIRMER]` **Zones** : cantons/villes exactes, prestations à distance ou non.
3. `[À CONFIRMER]` **Mentions légales** : raison sociale, IDE/RC, adresse, affiliation professionnelle, e-mail/téléphone publics.
4. `[À CONFIRMER]` **Indicateur** : volume mensuel de demandes visé (aucune cible chiffrée fournie à ce jour).

## DoD

Voir `instructions/projet/DEFINITION-OF-DONE.md` : acceptation par fonctionnalité, preuves réelles, publication soumise à accord distinct.
