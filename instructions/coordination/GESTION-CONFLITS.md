# Conflits — états et résolution

## Double CLAIM sur fichiers communs

STOP pour **les deux agents** avant écriture ; comparer timestamps vérifiés, issues, SHA et périmètres. Ne pas supposer que « premier commentaire = verrou ». Le propriétaire choisit un détenteur ; l'autre devient auditeur lecture seule ou change de tâche après validation.

## Commit entrant pendant une modification

STOP avant commit/push. Calculer `git diff`, SHA distant et zones de chevauchement. Pas de `reset --hard`, pas de force push et pas de rebase silencieux. Si fichiers disjoints, demander au détenteur de fusionner proprement après sauvegarde et tests. Si conflit, arbitrage et nouveau CLAIM.

## IA indisponible

Noter `[REVIEW_PENDING]` ; B ne répond pas automatiquement. Si la tâche est à faible risque et autorisations le permettent, l'auteur peut continuer une autre tâche **lecture seule** ; pour une publication, bloquer tant que le protocole impose revue ou escalader au propriétaire. Claim abandonné : propriétaire émet explicitement `[CLAIM-REASSIGN]`, jamais expiration supposée.

## Erreur CI

Marquer `BLOCKED_CI`, conserver logs nettoyés de secrets, chemin et scénario, corriger sur même périmètre ou créer claim complémentaire si fichiers non prévus ; relancer les contrôles. Poussé sur main ≠ validé. Aucun déploiement.

## Désaccord de design

B fournit 2 options avec critère mesurable (accessibilité, conversion, poids, cohérence). A choisit une option réversible si elle est dans le PRD ; pour changement d'identité ou promesse commerciale, demander au propriétaire et ADR si structurant.

## Escalade

Envoyer message court dans issue : `conflit`, `SHA`, `fichiers`, `impacts`, `propositions`, `décision nécessaire`. Si aucune issue accessible, préparer message à remettre manuellement ; ne pas affirmer que la communication a été publiée.
