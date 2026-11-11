# PRD — modèle à remplir selon le produit réel

**Statut :** TEMPLATE, non rempli, ne pas traiter comme décision acceptée.

## Mission

Nom : `[NOM]` · Cible : `[CIBLE]` · Problème prioritaire : `[PROBLÈME]` · Promesse mesurable : `[PROMESSE]` · Hébergeur choisi : `[HÉBERGEUR]` (profil `strict-local-web` : Hostinger Business par défaut / Infomaniak si choisi).

## Parcours clé

Arrivée visiteur → message de confiance → offres/fonction → interaction → demande/commande → confirmation → suivi. Décrire les exceptions, états d'erreur et permission s'il y a comptes.

## MVP et acceptation

| ID | Utilisateur | Besoin | Acceptation étant donné/quand/alors | Mesure | Statut |
|---|---|---|---|---|---|
| F001 | visiteur | Comprendre la promesse | Hero **avec vidéo locale autorisée**, poster et CTA visibles, mobile/réduit mouvement | test E2E + a11y | TODO |
| F002 | visiteur multilingue | Naviguer en langue choisie | Routes FR/EN/DE/IT ou locales définies, aucune clé manquante | tests i18n & hreflang | TODO |
| F003 | utilisateur | Action principale du métier | Scénario normal + erreur à définir | preuve de bout en bout | TODO |

## Hors périmètre et règles

- Aucun stockage, auth, backend ou vidéo externes indispensables. Ne pas ajouter de comptes sans nécessité du produit.
- Pas de publication avant validation des droits et accord de déploiement. Pas de stack imposée sans ADR.
- Budget, langues, contenus, concurrents, législation et délais sont à compléter d'après *faits* du propriétaire ; toute valeur inconnue reste `[À CONFIRMER]`.

## DoD

Lier aux checks de `projet/DEFINITION-OF-DONE.md` et spécifier acceptation par fonctionnalité, pas seulement `build PASS`.
