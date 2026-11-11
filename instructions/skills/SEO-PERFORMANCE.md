# Skill — SEO technique, mesure et Web Vitals

## Audit étape par étape

1. Explorer sitemap/robots, indexabilité des vraies pages, codes HTTP, canonical, hreflang, redirections, pages 404 et liens internes par langue.
2. Vérifier page title/description uniques, un H1 significatif, ordre H2, contenu utile, noms d'organisation, données structurées **fondées sur faits réels** et fiches locales si PRD.
3. Mesurer mobile + desktop en conditions reproductibles, distingue lab Lighthouse et field CrUX si disponible ; budgets initiaux **cibles** (p75 réels) : LCP ≤2,5 s, INP ≤200 ms, CLS ≤0,1. Sans données utilisateur, n'invente pas p75 terrain.
4. Profil coût JS/framework : ne pas hydrater une page statique sans raison, découper code, retarder scripts non critiques, supprimer dépendances inutiles.
5. Mesurer hero vidéo et poster séparément : LCP du titre/poster, taille totale, CPU, requêtes, autoplay, impact mobile, `Range` et cache. Ne pas cacher une mauvaise expérience sous un seul score Lighthouse.
6. Optimiser images et fonts : tailles définies, `srcset`, priorités réellement critiques, preload limité, font locale si légale, pas de CLS, fallback system.
7. Si analytics, favoriser logiciel local/self-host et confidentialité ; définir events `cta_click`, `lead_submit_success` sans PII et documenter les conditions de consentement applicables. Aucune obligation d'installer un tracker.

## Critères par niveau

| Métrique | Cible de départ | Mode de validation |
|---|---:|---|
| LCP | ≤ 2,5s | CrUX p75 si disponible ; Lighthouse indicatif sinon |
| INP | ≤ 200ms | mesure terrain ; TBT en labo comme indicateur secondaire |
| CLS | ≤ 0,1 | field ou lab selon disponibilité |
| 404 et mauvaises canonical | 0 sur pages attendues | crawl/test automatisé |
| Hreflang contradictoires | 0 | crawl de toutes les locales |
| Téléchargement d'assets inutilisés | minimisé | réseau navigateur + manifest |

Ces seuils sont des **objectifs**, pas des observations. IA B vérifie gains mesurés avant/après, même environnement et variation ; corriger tout d'abord P0/P1.
