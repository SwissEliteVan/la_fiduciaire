# Skill — sécurité, vie privée, licences

**Modèle de menace :** profil utilisateur, navigateur, formulaire, authentification, serveur, DB, stockage médias, GitHub Actions, hébergeur. Une simple vitrine n'a pas besoin d'inventer une authentification.

## Protection systématique

- Secrets dans variables serveur (hébergeur), jamais Git, messages d'issue, console partagée, image, front public ou artifacts GitHub non privés. `NEXT_PUBLIC_*` est public.
- Entrées : validation serveur, échappement sortie, HTML non fiable sanitizé, SQL paramétré ; CSP raisonnable si testée ; dépendances verrouillées et mises à jour avec contrôle.
- POST sensibles : authentification/autorisation et CSRF, rate-limit fiable, anti-spam, quotas, contrôles d'origine en défense supplémentaire seulement ; refus d'uploads exécutables.
- Multi-tenant : jamais supposer qu'un filtre UI équivaut à permission serveur. Tester lecture, export, mutation d'ID appartenant à autre client.
- Images/vidéos : licence, droits des personnes, données EXIF, stockage local, compression et virus pour uploads, noms de fichiers non révélateurs.
- Données suisses : transparence et finalité nLPD, minimisation, durée de conservation, accès/suppression, sécurité et éventuel RGPD selon activité réelle. Toute analyse juridique sensible à faire valider par un spécialiste.
- Supply chain : lockfiles, audit vulnérabilités, version pinning d'Actions, révision licence avant usage ; ne pas auto-appliquer de correctifs majeurs sans tests.

## Validation

| Risque | Test observable |
|---|---|
| Secret accidentel | scanner Git et fichiers **sans afficher valeurs** ; blocage CI |
| PII inappropriées | review logs/analytics/export avec données fictives |
| XSS/HTML | saisies hostiles et CSP/devtools si applicable |
| CSRF/abus formulaire | requête inter-origine, sessions, limites concurrentes |
| Multi-tenant | deux comptes tests et accès croisé négatif |
| Upload | MIME tronqué, taille, double extension, SVG actif, accès non autorisé |
| Sauvegarde | restauration non destructive démontrée |

**Interdit :** publication d'un média à droits inconnus. Ne pas supprimer en production ni expédier des données clients à un service tiers pour « les analyser ».
