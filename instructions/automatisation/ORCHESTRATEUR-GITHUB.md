# Orchestrateur GitHub — déclenchements réels, contrôles et relais entre IA

Le dépôt GitHub est le canal partagé. Les deux agents ont accès au dépôt, **mais cet accès ne leur permet pas à lui seul d'être réveillés automatiquement**. Trois couches distinctes :

1. **Sans installation supplémentaire** : IA A et IA B lisent le dernier SHA, les issues, CLAIM/REVIEW/FIX/RELEASE/HANDOFF puis travaillent chacun dans leur session réelle.
2. **Automatisation GitHub native** : Actions détecte les commandes d'issues, vérifie leurs auteurs, ajoute labels/accusés de réception et lance des validations. Cela ne modifie pas le produit ni ne lance un LLM.
3. **Agent runner autonome optionnel** : lorsqu'un agent GitHub compatible est réellement installé, sa tâche peut être déclenchée depuis l'issue ou `workflow_dispatch` selon son API réelle ; il dispose de permissions minimales et d'un budget. **Pas de runner ni de clé validés = jamais prétendre que les IA s'appellent automatiquement.**

## Prérequis à vérifier une fois par dépôt

- Issue et commentaires accessibles aux deux agents, actions GitHub autorisées ; l'équipe choisit l'issue par tâche ou issue de coordination existante.
- Connaître `main-only` ou politique branches/PR. Si `main-only`, **pas** de branche/PR, aucune modification automatique de code par le robot de relais ; tests post-push et diff local avant publication.
- Permissions minimales par workflow ; `GITHUB_TOKEN` limité au job ; secrets exclus des prompts, logs, commentaires et artefacts.
- Créer les labels `ia:claimed`, `ia:review-requested`, `ia:fix-required`, `ia:released`, `ia:conflict`, `ia:blocked` si leur usage est adopté.
- Compte-rendu SHA/test signé par références vérifiables ; ne pas exposer les médias originaux dans des artefacts d'issue.

## Conventions d'événements

Utiliser les messages stables de `coordination/CLAIM-RELEASE.md`. Une commande machine n'est valide que si elle est postée par un collaborateur possédant `write`, `maintain` ou `admin` et correspond exactement au format attendu :

```text
[CLAIM v1] task=CLIC-42 agent=A base=0123456789abcdef0123456789abcdef01234567 files=components/Hero.tsx,app/page.tsx
[REVIEW v1] task=CLIC-42 agent=A base=0123456789abcdef0123456789abcdef01234567 result=requested
[FIX v1] task=CLIC-42 agent=B base=0123456789abcdef0123456789abcdef01234567 result=required
[RELEASE v1] task=CLIC-42 agent=A base=0123456789abcdef0123456789abcdef01234567 result=completed
[HANDOFF v1] task=CLIC-42 agent=A base=0123456789abcdef0123456789abcdef01234567 result=next
```

Ces lignes sont des **enveloppes de notification**. Les preuves, le SHA final, les validations et le lien diff figurent dans le texte adjacent en langage naturel. Pour des demandes sans code, `base` reste le SHA courant. Jamais de demande de déploiement dans un commentaire.

## Modèle GitHub Actions pour les événements d'issue

Après permission de créer des fichiers, IA A convertit ce modèle en `.github/workflows/ia-relay.yml` et **vérifie réellement** syntaxe, permissions, labels existants et événement de test. Il fait du routage d'état, pas de génération de code :

```yaml
name: ia-relay
on:
  issue_comment:
    types: [created]
permissions:
  contents: read
  issues: write
concurrency:
  group: ia-relay-issues
  cancel-in-progress: false
jobs:
  route:
    if: ${{ !github.event.issue.pull_request }}
    runs-on: ubuntu-latest
    timeout-minutes: 5
    steps:
      - uses: actions/github-script@v7 # En production épingler au SHA vérifié
        with:
          github-token: ${{ secrets.GITHUB_TOKEN }}
          script: |
            const { owner, repo } = context.repo;
            const issue_number = context.issue.number;
            const actor = context.actor;
            const content = (context.payload.comment.body || '').trim();
            const rx = /^\[(CLAIM|REVIEW|FIX|RELEASE|HANDOFF) v1\] task=([A-Za-z0-9_-]{2,60}) agent=([AB]) base=([0-9a-f]{40})(?: files=([^\n]{1,600})| result=(requested|required|completed|next))$/;
            const found = rx.exec(content.split('\n')[0]);
            if (!found) return;
            let userPermission = 'none';
            try {
              const {data} = await github.rest.repos.getCollaboratorPermissionLevel({owner, repo, username: actor});
              userPermission = data.permission;
            } catch (error) { core.warning('Collaborateur non autorisé ou API inaccessible'); return; }
            if (!['admin','maintain','write'].includes(userPermission)) {
              core.warning('Commande ignorée : auteur sans accès écriture'); return;
            }
            const [line, event, task, agent, base, files, result] = found;
            const {data: repository} = await github.rest.repos.get({owner,repo});
            const {data: branch} = await github.rest.repos.getBranch({owner,repo,branch:repository.default_branch});
            const head = branch.commit.sha;
            if (event === 'CLAIM' && base !== head) {
              await github.rest.issues.createComment({owner,repo,issue_number,
                body:`[BOT-STALENESS] CLAIM ${task} refusé : base ${base.slice(0,8)}, HEAD ${head.slice(0,8)}. Synchroniser puis réessayer.`});
              return;
            }
            // Le contrôle des CLAIM concurrents ET de chevauchement de fichiers reste obligatoire.
            // Une issue n'est pas un verrou atomique, même avec une concurrence d'Actions.
            const labels = {CLAIM:'ia:claimed', REVIEW:'ia:review-requested',
              FIX:'ia:fix-required', RELEASE:'ia:released', HANDOFF:'ia:released'};
            await github.rest.issues.addLabels({owner,repo,issue_number,labels:[labels[event]]});
            await github.rest.issues.createComment({owner,repo,issue_number,
              body:`[BOT-ACK] ${event} ${task} (agent ${agent}) enregistré. Validation humaine/agent requise; aucune exécution IA présumée.`});
```

**Sécurité :** le workflow n'exécute pas le contenu d'un commentaire comme shell, JS, prompt ou commande Git. Les auteurs sans permissions sont ignorés. La chaîne `files` n'est jamais interprétée comme chemin exécutable. Le robot ne modifie pas `main`. La permission du bot de lire les issues et les labels est vérifiée sur le dépôt réel. **Ce workflow ne garantit ni exclusion mutuelle atomique ni revue automatique par un modèle** ; il signale les états. Ne publier qu'après test dans le dépôt cible.

## Revue réellement automatisée — deux options

**Option A : agent GitHub installé**. IA A publie `[REVIEW v1]`, le relais pose `ia:review-requested`. L'IA B configurée suit effectivement les changements d'issue et produit `[REVIEW-VERDICT]` avec un lien vers preuves. La manière de déclencher B dépend de son application/outil ; documenter sa procédure authentifiée, son statut et ses limites. Une étiquette seule **ne lance pas un agent**.

**Option B : deux sessions conversationnelles**. IA A fournit explicitement à IA B le numéro d'issue, le SHA et le diff. IA B répond dans GitHub. Toute reprise nécessite que l'utilisateur ou un outil autorisé active la session B. Une session interrompue ne peut pas être considérée disponible par défaut.

## Prévention des conflits : règle stricte

Un seul détenteur d'écriture par tâche et par liste de fichiers. Avant toute édition ou push :

1. Relire les CLAIM non libérés sur toutes les tâches actives ; comparer les chemins (y compris répertoires parents, renommages et fichiers générés).
2. Relire HEAD distant et les modifications non commitées du workspace. Si désaccord SHA / double CLAIM / fichiers chevauchants : `STOP_CONFLICT` ; B peut auditer en lecture seule.
3. Si nécessaire, propriétaire publie `[ARBITRAGE]` et réattribue la tâche. Pas d'expiration unilatérale d'un CLAIM ; pas de reset destructeur ni push forcé.
4. Ne jamais laisser deux IA modifier simultanément `NOW.md` ou `CONTEXT.md`. Une seule tâche active, état consolidé à la libération.

## Tâches que GitHub peut automatiser sans modèle

- GitHub Actions `push` : lint, types, build, tests unitaires/E2E, scan de secrets, liens cassés, parité des traductions, vérification media manifest et budget, Lighthouse CI lorsque configurée.
- Issues : routage d'événements et rappels (avec fréquence raisonnable, sans traiter une absence de réponse comme consentement).
- `workflow_dispatch` : recette manuelle autorisée sans déploiement ; délivrer rapport et logs sans données privées.
- Publication prod **désactivée par défaut** : nécessite accord explicite, sauvegarde/rollback testés et contrôle de l'environnement hébergeur.

**Critère de réussite de l'orchestration :** reproduire une tâche issue → CLAIM reconnu → contrôle SHA → tests CI → demande de revue réellement lue → verdict B → FIX si besoin → RELEASE confirmé. Documenter `AUTOMATION_ACTIVE` pour chaque étape effectivement exécutée et `MANUAL_REQUIRED` pour les autres.
