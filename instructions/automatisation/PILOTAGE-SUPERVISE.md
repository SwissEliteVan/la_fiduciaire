# Pilotage autonome supervisé — mise en œuvre

Cette fonction appartient uniquement à **IA-DUAL-AUTONOMOUS-KIT**. Le kit ne modifie aucun projet par installation ; seul un cycle explicitement confirmé peut lancer un assistant dans le projet sélectionné.

## Les huit piliers

1. **Priorité produit** : les candidats issus des listes de suivi sont hiérarchisés et les tâches reconnues comme traitées dans la mémoire du kit sont écartées. Le raisonnement sur la valeur métier reste en partie délégué à l'IA ; le classement lexical est une heuristique, pas un diagnostic produit prouvé.
2. **Orchestrateur durable** : bouton « Cycle supervisé (2 tours max) », état atomique hors dépôt, reprise contrôlée après interruption, **au plus deux développements et deux revues** ; arrêté en cas d'échec, de non-progrès, de preuve manquante ou de revue non validée. Durée totale plafonnée à 45 minutes ; les CLI sont chacun limités à 20 minutes. Aucune boucle infinie ni mode 24/7.
3. **Contrôle qualité bloquant** : empreintes de fichiers produit avant/après (en soustrayant l'état préexistant), `git diff --check`, scripts lint/typecheck/test reconnus et bornés, ou tests Python reconnus. Un code CLI 0 ne valide plus une mission autonome sans preuve d'un test significatif.
4. **Visuel réel** : l'utilisateur peut configurer une URL **localhost** de son propre serveur de développement. Le kit exploite Playwright **déjà installé dans ce projet** : Chromium, captures à 1440/768/390px, réduction des animations, erreurs JavaScript, débordement horizontal et réponse HTTP. Les captures restent dans le profil local. Pas d'installation, de publication ni de serveur démarré à son insu. Sans URL locale, Playwright ou serveur actif, la recette est NON EXÉCUTÉE : validation globale impossible pour une application web à interface.
5. **Deuxième IA** : relecture read-only, réponse capturée et enregistrée dans `derniere-revue.json`. Un verdict exact « VERDICT = VALIDÉ », « CORRECTIONS REQUISES » ou « NON VÉRIFIABLE » est attendu ; tout format incertain devient NON VÉRIFIABLE. Une revue négative stoppe le cycle au lieu de lancer indéfiniment des correctifs.
6. **Mémoire** : `memoire-produit.json` privé par chemin de projet (tâches suggérées, preuves des fichiers, tests et verdict). Les anciennes modifications ou validations ne sont pas transformées en nouvelles tâches sans raison. Attention : lorsqu'une IA traite un autre objectif que le premier candidat, le suivi doit être confirmé manuellement ; la mémoire n'est pas une preuve de correspondance fonctionnelle.
7. **Budget** : un lancement implique au maximum 4 appels CLI (2 développements + 2 relectures), durée totale bornée à 45 minutes, aucun retry automatique après échec ou absence de progrès. Temps réellement passé par tour et résultat sont sauvegardés. Les coûts et tokens restent « non mesurés » car les fournisseurs ne garantissent pas un décompte uniforme ; aucun prix inventé.
8. **Récupération ciblée** : archives non destructives des modifications Git avant chaque session dans `KitIADual/qualite/<projet>/archives`. Le patch des fichiers suivis et certains fichiers non suivis sont sauvegardés sous plafond de 20 Mo, sans suppression. Un fichier sensible (nom ou contenu ressemblant à un secret), même déjà suivi par Git, n'est jamais recopié : il est listé dans `exclus_sensibles` et l'archive est signalée partielle (`limite`), comme lorsque le plafond de fichiers non suivis est atteint. Le manifeste permet de préparer une restauration ciblée **manuelle** ; aucun `git reset --hard`, `git clean` ou restauration silencieuse.

## Règles de statut

- **SANS_PROGRES** : pas de changement nouveau sur les fichiers produit.
- **ECHEC** : un contrôle applicable a échoué, ou l'agent s'est arrêté anormalement.
- **NON_VERIFIABLE** : preuves insuffisantes ou recette visuelle nécessaire mais non disponible ; aucun faux succès.
- **ACCEPTE_TECHNIQUE** : fichiers produit modifiés et tests applicables réellement exécutés, sans échec ; ne remplace pas une validation métier.
- **VALIDE** : relecture IA B positive et contrôle technique passé ; le verdict de l'IA n'est pas une certification indépendante de production.

Le lancement manuel de la relecture IA B reste disponible et ne doit pas être confondu avec le cycle supervisé.

## Utilisation

Depuis le kit à jour : sélectionnez le projet et les assistants, ouvrez le serveur local si une interface doit être testée, choisissez « Configurer recette visuelle », puis « Cycle supervisé (2 tours max) ». Une confirmation annonce le budget. Si seul un assistant CLI est sélectionné, le système peut terminer avec une acceptation **technique seulement** ; il ne prétend pas disposer d'un relecteur indépendant.

Les opérations de déploiement, changements de droits, migrations destructives, publication et dépenses ne sont jamais autorisées implicitement. Le kit ne déclare pas un programme « totalement autonome » tant que ses tests d'intégration sur les CLI et environnements réels ne l'ont pas établi.

## Vérification

Le workflow GitHub Actions Windows compile l'ensemble des modules et exécute chaque fichier de tests indépendamment, notamment `test_pilotage.py`, `test_preuves.py`, `test_fenetre.py`, `test_autonomie.py`, `test_executer_ia.py`. Ces tests n'appellent aucun modèle payant ni serveur de projet utilisateur.
