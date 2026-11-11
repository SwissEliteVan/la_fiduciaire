# Prompt 04 — IA au choix, API facultative, coordination économe

## Mission

Faire évoluer le kit pour que le propriétaire puisse choisir **zéro, une ou deux IA**, indépendamment de l'éditeur ou du prix de l'abonnement. **Claude, Codex et Gemini ne doivent pas être imposés**. Permettre aussi des fournisseurs via API et des modèles locaux sans obliger à souscrire deux forfaits.

## État technique à vérifier avant travail

Le lanceur possède actuellement des adaptateurs CLI explicites dans `lanceur/assistants.py` (`claude`, `codex`, `gemini`) et un mode `vscode` permettant de copier des prompts dans **une extension IA au choix**, sans exécution automatique. Une API arbitraire n'est **pas** automatiquement branchée à `executer_ia.py`. Ne pas prétendre l'inverse. Revalider sur les fichiers courants avant implémentation.

## Solution à développer lors d'un mandat autorisant du code

1. Conserver tous les parcours et tests existants ; ne jamais supprimer un adaptateur fonctionnel.
2. **Parcours immédiat sans nouveau code :** documenter le mode VS Code + extension compatible avec le fournisseur choisi (par exemple Continue si installée/compatible), dont la connexion API est faite dans ses réglages ; le kit transmet les prompts, sans prétendre piloter directement cette extension.
3. **Automatisation du lanceur :** concevoir une interface d'adaptateurs configurable avec `id`, `mode=cli|api|vscode|local`, `model`, `base_url`, `api_key_env`, `timeout`, `budget`, `permissions`, `role`. Choisir indépendamment agent A et, **optionnellement**, B. Prévoir un seul agent par défaut et aucun appel externe non demandé.
4. Pour les fournisseurs compatibles OpenAI, utiliser un endpoint `/v1` configuré et validé. Pour les API propriétaires hétérogènes, évaluer une passerelle **facultative LiteLLM**, uniquement si elle simplifie réellement le besoin. Une API de génération de texte **n'est pas un agent développeur** : les outils de lecture/écriture, exécution de tests, approbation, sandbox et passation doivent être fournis par un harnais agent vérifié, pas improvisés.
5. Fournisseur par API, modèle local, extension VS Code et CLI : validation de disponibilité, erreurs explicites, aucun faux succès ni seconde IA fictive.
6. **Sécurité, coûts et Git : voir `AGENTS.md`** (secrets hors dépôt et hors logs, autorisation avant tout envoi de code privé ou appel facturable, budget et arrêt sur 401/429/timeout, `main` exclusif, CLAIM ciblé, push fast-forward uniquement si autorisé, aucun déploiement implicite). Ne pas recopier ces règles dans le code : les appliquer.

## Tests et preuves

- Sans IA, 1 IA, 2 IA choisies librement ; panne de B sans blocage de A ; modèle local sans réseau.
- API factice : succès, 401, 429, 5xx, timeout, réponse vide, coût atteint, annulation et reconnexion ; aucun vrai crédit facturé pendant les tests.
- Aucun secret en log/diff ; permissions analyse lecture seule versus développement limité ; usage de `main` seulement et refus des conflits.
- Modes VS Code et CLI existants toujours opérationnels ; tests unitaires ciblés et recette Windows réelle lorsque possible.

**Livrable :** solution réellement connectée **si et seulement si un mandat de modification du code du lanceur est donné**. Pour une mission documentaire/prompt uniquement : produire la spécification technique et les étapes de configuration, sans affirmer que l'API marche déjà.

Voir aussi `prompts/CONNECTER-IA-LIBRES-API.md`.
