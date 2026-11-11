# Programme d'optimisation du kit — 9 prompts

**Correctifs du dernier audit :** [6 prompts ciblés, mode économe en tokens](correctifs/README.md). À exécuter un par un ; ne pas confondre la présence des prompts avec un correctif appliqué.

Les prompts se lancent **un par un**, avec un objectif limité, des contrôles et une relecture indépendante. Ils définissent des travaux à réaliser : leur présence ne prouve pas leur implémentation.

| Étape | Prompt | Intention |
|---|---|---|
| 00 | [Cadrage et mémoire produit](00-CADRAGE-MEMOIRE-PRODUIT.md) | Décrire chaque produit et conserver ses besoins |
| 01 | [Créativité et animations](01-DIRECTION-ARTISTIQUE-CREATIVE.md) | Trois directions vraiment différentes |
| 02 | [Audit de sécurité](02-AUDIT-SECURITE.md) | Protéger les sources, Git et les sauvegardes |
| 03 | [Architecture unifiée](03-ARCHITECTURE-UNIFIEE.md) | Simplifier deux moteurs sans casser le kit |
| 04 | [Autonomie IA A/B](04-AUTONOMIE-IA-A-B.md) | Coordiner développement, revue et reprise |
| 05 | [Tokens et mémoire](05-ECONOMIE-TOKENS-MEMOIRE.md) | Réduire coût et répétitions mesurées |
| 06 | [Tests et corrections](06-TESTS-AUTO-CORRECTION.md) | Prouver les corrections et éviter les régressions |
| 07 | [Ergonomie du lanceur](07-ERGONOMIE-LANCEUR.md) | Parcours clair et interruption tolérée |
| 08 | [Validation finale](08-VALIDATION-FINALE.md) | Vérifier plusieurs projets et les scénarios critiques |

## Principes

- Respecter les règles du projet et ses décisions existantes ; les anciennes contraintes de vidéo, hébergement et langues restent applicables tant qu'elles ne sont pas explicitement révisées.
- Pour une nouvelle UI ou une refonte importante, passer par la fiche produit puis proposer trois concepts différents **avant de coder**, sans supposer un choix utilisateur.
- Aucun appel IA lors de l'ouverture ou de la saisie du produit. Aucun commit, push ou déploiement implicite.
- Pour chaque chantier : état Git, SHA, fichiers concernés, résultat observable, tests exécutés et blocages.
- Marquer comme NON EXÉCUTÉ tout test ou rendu non réalisé, notamment Windows réel, Docker, navigateurs et API externes.
