# Décision de stack — matrice et arbitrage IA

**Le choix revient à IA A**, que IA B doit challenger. Interdiction d'imposer systématiquement Next, Laravel ou toute autre technologie. Préserver une stack existante qui satisfait réellement le PRD.

## Étape 1 — qualifier les besoins

- Vitrine statique / contenu rédactionnel ? Formulaire ? back-office ? comptes clients / droits multi-tenant ? workflows ? paiement ? réservation ? charge ? hors ligne ? langues et SEO ? volumes média ? contraintes légales ?
- Consigner les facteurs dans `projet/PRD.md`, distinguer exigences explicites et hypothèses.
- Préciser l'offre d'hébergement confirmée (profil `strict-local-web` : **Hostinger Business** ou **Infomaniak**) ; compléter une matrice de capacités comme `architecture/HEBERGEMENT.md` avec des preuves d'accès au compte. Les docs générales seules ne prouvent pas un runtime activé.

## Étape 2 — comparer 2 à 3 solutions *compatibles*

| Cas réel | Candidat A | Candidat B | Condition décisive |
|---|---|---|---|
| Site vitrine SEO et langues | Astro/HTML statique + petite route PHP | Next/React en export statique + PHP local | Moins de JS, contenu et expérience requise |
| App de gestion avec comptes | Laravel + PHP + MySQL/MariaDB | Symfony + PHP + MySQL/MariaDB | PHP, Composer, tâches planifiées, limites vérifiés |
| App JS interactive / SSR | Node.js managé chez Hostinger Business | solution PHP adaptée | Node réellement pris en charge par *ce compte*, persistance, quotas |
| Petit CMS éditorial | Grav/PHP local | CMS PHP si besoin | Maintenance, droits, sécurité, extension multilingue |

Infomaniak Web : présumer **PHP + MySQL/MariaDB** seulement après contrôle des options du plan ; ne pas présumer un processus Node permanent. Ne pas choisir une solution par seul prestige de framework.

## Score pondéré (0–5 avec preuves)

| Dimension | Poids |
|---|---:|
| Compatibilité réelle de l'hébergement et restauration | 25 % |
| Sécurité, confidentialité et maintenance | 20 % |
| SEO, Web Vitals, accessibilité, i18n | 20 % |
| Complexité/coût complet et autonomie de l'équipe | 15 % |
| Fonctionnalités PRD et tests réalisables | 15 % |
| Licences et maîtrise des données | 5 % |

Calcul : Σ(score/5 × poids). **Exclusion dure** si échec d'hébergement, stockage externe obligatoire, droit média, licence inappropriée ou sécurité. Rendre le score et les limites reproductibles.

## Décision enregistrée

```text
ADR: [numéro] / [date] / accepté ou conditionnel
Produit/cible :
Hébergeur, plan et sources :
Exigences :
Options/notes pondérées :
Choix et versions verrouillées :
Structure fichiers, DB/auth et politique stockage :
Route media / hero / i18n :
Exigences CI, sécurité, sauvegarde et rollback :
Alternatives rejetées :
Risques/contrôles sur compte réel :
```

**Garde-fous :** un export statique ne peut fournir des routes dynamiques serveur intégrées ; formulaire → PHP local ou solution native à l'hébergeur. Ne pas implémenter une API Next dans un build statique en prétendant qu'elle sera disponible. Ne pas compter sur la bande passante gratuite illimitée sans vérification contractuelle.
