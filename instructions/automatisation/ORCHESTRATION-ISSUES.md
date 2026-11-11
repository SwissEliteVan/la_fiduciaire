# Orchestration GitHub Issues — automatisations utiles et limites

**Ne pas confondre automatisation des messages avec activation d'un agent IA.** Les deux IA ont accès au dépôt, mais GitHub seul ne lance pas une conversation ChatGPT/Claude. Le propriétaire doit soit ouvrir les deux sessions, soit disposer d'un orchestrateur/bot réellement installé, documenté, autorisé et compatible avec les conditions des fournisseurs d'IA.

## Automatisables sans orchestrateur externe

- Générer une issue par tâche avec labels `ready`, `claimed`, `review`, `blocked`, `done` ; statut NOW reste unique.
- Quand un commentaire commence par `[CLAIM v1]`, vérifier format/ID/SHA, lire les autres claims non libérés, signaler collision au lieu d'autoriser une édition.
- Quand une CI échoue, commenter le run et placer `blocked` si les droits GitHub le permettent ; **ne pas fermer automatiquement**.
- Quand un commentaire `[REVIEW v1]` arrive, notifier le détenteur CLAIM (mention GitHub si disponible), mais **pas** déclencher une autre IA par supposition.
- Après `[RELEASE v1]`, archiver le ticket si DoD satisfait et aucun REVIEW_PENDING ; incrémenter le backlog d'une seule tâche.

## Gabarit minimal de workflow dans le **projet réel**

```yaml
name: issue-coordination
on:
  issue_comment:
    types: [created]
permissions:
  contents: read
  issues: write
concurrency:
  group: issue-${{ github.event.issue.number }}
  cancel-in-progress: false
jobs:
  classify:
    if: github.event.issue.pull_request == null
    runs-on: ubuntu-latest
    steps:
      - uses: actions/github-script@v7 # avant activation, pinner à SHA vérifié
        with:
          script: |
            const body = context.payload.comment.body.trim();
            const kind = (body.match(/^\[(CLAIM|REVIEW|FIX|RELEASE|HANDOFF) v1\]/) || [])[1];
            if (!kind) return;
            const issue = context.issue.issue_number;
            const {data: comments} = await github.rest.issues.listComments({
              ...context.repo, issue_number: issue, per_page: 100
            });
            // Signalement uniquement : la validation réelle du SHA, des chemins
            // et de l'absence de collision doit encore être effectuée par l'agent.
            const competing = kind === 'CLAIM' && comments.filter(c =>
              c.id !== context.payload.comment.id &&
              /^\[CLAIM v1\]/.test(c.body) &&
              !comments.some(r => /^\[RELEASE v1\]/.test(r.body))
            );
            if (competing && competing.length) {
              await github.rest.issues.createComment({
                ...context.repo, issue_number: issue,
                body: 'Attention : plusieurs CLAIM possibles. Suspendre toute écriture et arbitrer les fichiers/SHA.'
              });
            }
```

**Limites de cet exemple :** ne traite que les 100 premiers commentaires, et une ancienne RELEASE peut fausser une détection simple ; c'est une **alerte**, pas un verrou ni un interpréteur de CLAIM complet. Ne jamais déduire de cette Action qu'il n'y a aucun conflit. Un outil de verrouillage fiable demanderait un backend ou une garantie transactionnelle conçue/testée ; le protocole utilise rechecks et arrêt en cas d'ambiguïté. Les workflows qui écrivent dans GitHub Issues nécessitent permissions propriétaires et doivent être audités contre abus de texte non fiable ; ne jamais exécuter le corps des commentaires comme shell.

## Pour automatiser réellement l'activation de la seconde IA

Vérifier dans chaque plateforme l'existence d'une **intégration officielle** capable de répondre à un événement GitHub, avec consentement, permissions minimales et coûts explicités. Un tel service serait un **orchestrateur de développement**, pas un serveur d'application ni stockage métier, mais ne doit ni exfiltrer des secrets/données clients ni contourner les règles d'autorisation. S'il n'existe pas, garder la relance manuelle des sessions et afficher `REVIEW_PENDING`.

## Gouvernance anti-spam

- Pas de commentaire automatique à chaque commit si aucun événement significatif ; une notification par transition.
- Pas de changements de labels/assignés sans droits prouvés.
- `main` seulement si exigé, pas de PR artificielle.
- Actions épinglées sur SHA de commit avant installation ; éviter de copier du code non vérifié dans un workflow privilégié.
