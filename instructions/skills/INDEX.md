# Routeur de compétences — chargement minimal

> **Source et consolidation :** [SwissEliteVan/sprompt_pkills](https://github.com/SwissEliteVan/sprompt_pkills), révision `75f0ac63c9f98bdba36ee0fda2598c84d2259490`. Le kit avait déjà la majorité des fichiers communs ; seuls le routeur local et `PROMPTING-CIBLE.md` ont été ajoutés. **Ne pas installer les modules `travail-universel/runtime` par simple présence dans ce dépôt.** Le catalogue du lanceur charge un fichier sélectionné seulement et ne lance aucune IA.

> **Note de compatibilité :** les anciennes prescriptions d'hébergement, langues ou fournisseurs figurant dans certains guides ne sont applicables que si les règles et le profil du projet concerné les exigent. Exception générale imposée par le propriétaire : vraie vidéo hero sur tout accueil web. Les instructions du projet ont priorité.

**Toujours charger en premier** `AGENTS.md`, `NOW` et le protocole ; ensuite **un seul** skill principal et ses références, pas tous les documents. IA A choisit architecture/code ; B consulte les mêmes contraintes + design/tests pour auditer.

| Signal de tâche | Skill principal | Compétences en soutien |
|---|---|---|
| Nouveau site ou migration | `ARCHITECTURE.md` | `DEPLOIEMENT.md`, `SECURITE.md` |
| Formulaire, authentification, DB | `BACKEND.md` | `SECURITE.md`, `TESTS.md` |
| Composants, responsive, accessibilité | `FRONTEND.md` | `DESIGN-UX.md` |
| Identité, conversion, ergonomie | `DESIGN-SYSTEM-PRO.md` | `DESIGN-UX.md`, `SEO-PERFORMANCE.md` |
| Import photos/logo/illustrations | `MEDIAS-AUTOMATIQUES.md` | `VIDEO-HERO.md`, `../automatisation/TRANSFORMER-MEDIAS.md` |
| Vidéo de couverture OBLIGATOIRE (tous projets web) | `VIDEO-HERO.md` | `MEDIAS-AUTOMATIQUES.md` |
| Routes et ressources localisées | `MULTILINGUE.md` | `SEO-PERFORMANCE.md` |
| Performance / SEO | `SEO-PERFORMANCE.md` | `TESTS.md` |
| Permissions, vulnérabilité | `SECURITE.md` | `BACKEND.md` |
| Tests / release | `TESTS.md` | `DEPLOIEMENT.md` |

## Contrat commun pour une compétence

1. Lire les fichiers du code réellement concernés ; ne pas installer une bibliothèque automatiquement.
2. Consulter `ressources/BIBLIOTHEQUES-OPEN-SOURCE.md` pour candidats puis **vérifier versions, licences et activité** avant chaque adoption.
3. Décrire critères observables, fichiers, tests, risques, impact sur bundle et dépendances.
4. Respecter l'hébergement et la politique de données du projet (profil `strict-local-web` : données locales et policy Hostinger/Infomaniak). Ne pas fabriquer de résultats de benchmark ni d'analyse visuelle.
5. Après changement : produire petit diff, rapport tests et demande REVIEW B ; aucune publication non autorisée.
