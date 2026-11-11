# Protocole exécutable en session — deux IA, un dépôt

**Autorité unique pour le déroulement.** Les autres prompts renvoient ici et n'en copient pas les règles. Définition des rôles : **IA A implémente**, **IA B audite, teste, cherche les risques et améliore le design** ; rotation des rôles après clôture autorisée et mention explicite de la tâche.

## Canaux et vérité

| Objet | Source de vérité |
|---|---|
| Règles, interdits et politique Git | `AGENTS.md` local + décisions acceptées |
| Tâches futures | `projet/BACKLOG.md` ou backlog existant, **un seul** |
| Tâche active | `projet/NOW.md` ou fichier NOW existant, **un seul** |
| Décisions | `projet/DECISIONS.md` ou ADR existants |
| Coordination, historique des reviews | **issue GitHub dédiée à chaque tâche ou issue de coordination existante**, référencée par ID |
| État technique constaté | `projet/CONTEXT.md`, limité à l'état courant |
| Tests automatiques | GitHub Actions **si réellement installées** ; sinon commandes locales explicites |

## Machine d'états contrôlée

`OPEN → CLAIMED → PLANNED → IMPLEMENTED → TESTED → REVIEW_REQUESTED → (FIX_REQUIRED ↔ TESTED) → APPROVED → RELEASED → CLOSED`.

- **OPEN** : tâche documentée, critères et dépendances. **CLAIMED** : A confirme base SHA + fichiers ; s'il manque issue/accès, mode lecture seule jusqu'à coordination manuelle.
- **PLANNED** : objectif, risques, diff prévisible, tests, limites de permissions.
- **IMPLEMENTED** : modifications locales dans fichiers déclarés ; pas de suppression non autorisée.
- **TESTED** : commandes + codes de sortie + environnements, preuves de rendu lorsque pertinent.
- **REVIEW_REQUESTED** : A poste diff/SHA + preuves ; B doit être sollicitée dans sa vraie session.
- **FIX_REQUIRED** : B poste défaut avec preuve et test attendu ; A corrige et reteste (max trois itérations non utiles sans escalade).
- **APPROVED** : B émet verdict avec limites. Cela **n'autorise pas** la production.
- **RELEASED** : code committé/poussé si autorisé, état documenté, SHA final vérifié et claim libéré. **CLOSED** : tâche terminée sur critères. En cas de revue post-commit imposée par main-only, consigner ce décalage et ne pas nommer « approuvé avant commit ».

## Ordre opérationnel

1. **SYNC** : relire AGENTS, NOW, état Git local + distant, dernière issue, claims non libérés, changements non suivis ; ne jamais `git reset --hard` pour « synchroniser ».
2. **CLAIM** : poster `[CLAIM v1] ...` avec ID unique de tâche, chemins réservés, SHA de base et cible de livraison. Voir `CLAIM-RELEASE.md`.
3. **RECHECK** : recharger l'issue et le SHA ; en cas de double claim ou modifications entrantes chevauchantes, **STOP**. Commentaires Issues ne sont pas un verrou atomique ; ils réduisent le risque, ils ne le suppriment pas.
4. **PLAN** : scope minimal et acceptation ; IA B peut auditer parallèlement des fichiers **sans les modifier**, sans CLAIM d'écriture.
5. **IMPLEMENT/TEST** : A travaille sur périmètre réservé, test ciblé puis lint/types/build ; conserver journal de commandes et preuves.
6. **REVIEW** : si espace partagé, B inspecte diff local **avant commit** ; avec accès GitHub sans espace partagé, A publie seulement le commit autorisé puis B fait **revue post-commit**. Ne jamais créer de branche ni de pull request : la politique du dépôt est `main` exclusif (voir `AGENTS.md`), et la revue se fait par diff, SHA et issue.
7. **PUBLISH** : re-vérifier `origin/main` et la propriété des fichiers juste avant `push`; si distant a bougé, **STOP**, réexaminer le diff et coordonner ; aucun force-push.
8. **RELEASE/HANDOFF** : poster état, SHA final, verdict B réel, tests et seule prochaine action. Mettre à jour NOW/CONTEXT uniquement par détenteur CLAIM puis libérer les fichiers. Pas de changement de serveur implicite.
9. **LOOP** : tant que la session et autorisations l'autorisent, prendre la première tâche READY indépendante du backlog et répéter. Stopper si tâches terminées, blocage réel ou nouveau mandat requis.

## CI et automatisation (à matérialiser)

IA A doit proposer, puis générer dans le **projet** `.github/workflows/ci.yml` à partir d'`automatisation/CI-GITHUB.md`, et le relais des issues facultatif depuis `automatisation/ORCHESTRATEUR-GITHUB.md`. Utiliser `concurrency` par workflow, moindre privilège, permissions `contents: read`, événements `push`/`pull_request` **selon politique** ; contrôle lint/tests/build et médias/i18n. Un job CI après push sur main **détecte** une erreur, ne bloque pas rétroactivement le commit : pour protéger main sans branches, configurer une politique de push adaptée si disponible et validée par propriétaire ; sinon reconnaître cette limite.

## Propriété, parallélisme et rotation

- A seule écrit les fichiers de son CLAIM ; B ne modifie jamais ces fichiers pendant la revue.
- B peut prendre un CLAIM **distinct** pour un travail indépendant si la politique autorise deux tâches parallèles ; garder **une seule tâche produit active** : pour les autres demandes, préférer audit lecture seule. Pour éviter la confusion, modèle par défaut = séquentiel.
- `NOW.md` et `CONTEXT.md` édités exclusivement par détenteur du CLAIM actif, après nouveau contrôle SHA.
- Répartition possible à tâche suivante : A↔B après RELEASE ; noter explicitement les rôles et les nouveaux chemins réservés.
- Aucun « accord à deux » sans message REVIEW réellement reçu et consulté.

## Permissions / stop

Aucune modification du serveur, publication, changement DNS, permission, migration destructive, transfert de fichiers client ou achat sans consentement clair. Si GitHub inaccessible, les actions d'écriture restent locales et la réservation distante est **non confirmée**. Une analyse documentaire peut continuer ; pas de commit qui écrase le travail inconnu.
