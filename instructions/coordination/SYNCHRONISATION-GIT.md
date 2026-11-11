# Synchronisation Git — anti-écrasement et travail sur main

## Avant toute tâche

```bash
git status --short --branch
git remote -v
git fetch origin
git rev-parse HEAD
git rev-parse origin/main
git diff --name-status
git diff --cached --name-status
```

Lire l'issue, les CLAIM non libérés et le dernier SHA. **Si `HEAD != origin/main`**, ne pas pousser : inspecter l'écart, sélectionner une stratégie non destructive **avec accord**, garder les changements non commitées. `git pull` aveugle, `reset --hard`, `clean -fd`, `push --force` interdits.

## Juste avant écriture et publication

- Confirmer que HEAD et les fichiers diffèrent ou non de la base CLAIM ; vérifier nouveaux commits et absence de réservation concurrente.
- Un GitHub Issue n'offre pas de verrou transactionnel. Contre double départ simultané : publier CLAIM, recharger, **recontrôler à chaque phase** ; en cas de course, STOP et propriétaire arbitre.
- Avec branche principale unique, Git rejette normalement un push non-fast-forward ; cette protection ne doit pas être désactivée. Ne jamais forcer une mise à jour.
- Ne pas auto-committer `NOW` et `CONTEXT` en même temps que deux auteurs indépendants : propriétaire unique du CLAIM actif.

## Autorisations Git

- `git fetch`/diff/diagnostic : lecture généralement admise si connecteur disponible.
- `git add`/commit : uniquement dans périmètre autorisé ; aucun secret ; pas de fichiers binaires massifs sans intention.
- `git push` : permission selon politique/propriétaire ; documenter SHA exact.
- Branch/PR : possible dans autres projets ; **jamais** si projet impose `main` seulement (par exemple workflow Clicom V5).

## Reprise de copie périmée

1. Arrêter l'écriture et copier les modifications utiles dans une sauvegarde **hors repo** uniquement avec autorisation et sans secrets.
2. Relire base, diff distant, diff local et conflits ; demander arbitrage si chevauchement.
3. Réappliquer changement minimal sans effacer le travail de l'autre agent ; relancer tests et revoir claim.
4. En cas d'incertitude, produire patch de lecture seule avec instructions ; ne pas improviser de rebasing destructeur.
