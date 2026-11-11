# Mode d'emploi — installer sans écraser

## Projet neuf

1. Créer un dépôt privé vide ou fournir la définition du produit ; conserver l'archive dans un dossier de référence séparé.
2. Fournir `00-START.md` à IA A et `prompts/IA-B.md` à IA B ; vérifier que les deux sont connectées au *même* dépôt et connaissent le SHA.
3. IA A crée les documents de travail dans le dépôt réel, initialise la stack, met en place les outils de qualité et matérialise les CI **dans le projet** si les permissions le permettent.
4. Après audit B, valider la feuille de route ; les IA avancent une tâche après l'autre durant les sessions actives. La mise en production requiert un accord distinct.

## Projet existant

1. Ne pas déposer les modèles racine `AGENTS.md` ou `README.md` par-dessus ceux du projet. Demander d'abord un diff et une cartographie des collisions.
2. `00-START.md` active le mode EXISTANT ; appliquer les règles actuelles du repo, notamment `main` uniquement lorsqu'il est imposé.
3. Intégrer seulement les procédures utiles (ou conserver le kit comme bibliothèque externe) ; ne pas migrer les tâches ou les ADR historiques sans contrôle.

## Commandes indicatives (adapter à l'environnement réel)

```bash
git status --short --branch
git remote -v
git fetch origin
git rev-parse HEAD
git rev-parse origin/main
find . -type f -name '*.md' -not -path './node_modules/*' -not -path './.git/*'
```

Aucune commande ci-dessus n'autorise un push ni une modification du serveur. En absence de CLI, utiliser les interfaces du connecteur GitHub ; ne pas inventer la sortie des commandes.

## Deux IA en pratique

- IA A : code, architecture, intégration.
- IA B : audit contradictoire, UI/UX, régressions, vérification indépendante.
- GitHub issue : file de messages et répartition ; pas un moteur de réveil des agents.
- CLAIM : intention vérifiable, **pas** verrou atomique ; check immédiat des conflits et SHA, puis abandon en cas de doute.
- Un seul `NOW.md` ; backlog et ADR durables ; contexte compact ; un passage de relais par tâche.

## Automatisation réelle — ce que Markdown permet ou non

Le kit décrit les transformations à produire : tests, workflow CI, commandes média, alertes et passations. Les agents doivent **écrire dans le dépôt du produit des fichiers exécutables** (`.github/workflows/*.yml`, scripts JS/Python/PHP, configurations) selon son stack pour que l'automatisation fonctionne. L'archive elle-même ne contient que des `.md`. Aucune exécution autonome 24/7 sans agent/scheduler connecté et explicitement autorisé.

## Mettre en route les automatismes GitHub

1. L'IA A détermine le runner réel (GitHub Actions, agent connecté ou seulement sessions manuelles) et vérifie les permissions **dans le dépôt**.
2. Après autorisation d'écriture, elle crée `.github/workflows/ci.yml` d'après `automatisation/CI-GITHUB.md`, puis le relais d'issues d'après `automatisation/ORCHESTRATEUR-GITHUB.md` si pertinent. Les fichiers `.yml` n'existent **pas** dans ce ZIP (Markdown uniquement).
3. Elle exécute un essai sur une issue et un commit non sensibles. Une IA B ne peut être réveillée automatiquement que si un agent disposant d'un déclencheur réel a été configuré ; une étiquette GitHub ne suffit pas.
4. Pour les médias, extraire les scripts dans le dépôt cible : `automatisation/PIPELINE-MEDIAS.md` (inventaire), `automatisation/TRANSFORMER-MEDIAS.md` (conversion), `automatisation/QA-UNIFIEE.md` (recette). Conserver les sources hors webroot.
