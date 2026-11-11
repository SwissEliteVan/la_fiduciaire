# Déclencheur de session — démarrage autonome *dans la session*

Lorsqu'une IA reçoit cette instruction :

1. Lire `00-START.md` si première exécution, sinon `AGENTS.md` et les 20 dernières lignes utiles de NOW/CONTEXT/issue.
2. Déterminer NOUVEAU/EXISTANT, état des accès et dernier SHA ; ne pas redemander au propriétaire des faits déjà écrits dans les ADR.
3. Vérifier la tâche active. Si aucune : IA A propose et prend première tâche READY selon backlog ; IA B réalise audit initial et publie sa review.
4. Faire **une action concrète sans attendre** si autorisée ; relever le vrai résultat. Un prompt Markdown n'exécute aucun CLI à lui seul.
5. Continuer via [`automatisation/CONTINUATION.md`](../automatisation/CONTINUATION.md) jusqu'à DoD ou blocage. Documenter précisément tout droit indisponible et conserver un handoff.

**Ne jamais** : créer secrètement des branches si `main` uniquement, pousser sans permission, effacer l'existant, installer Supabase, appeler un stockage média externe, publier des actifs sans licence.
