# Boucle de travail maximale — sans autonomie fictive

**Le kit n'exécute pas de tâche en arrière-plan.** L'IA active peut enchaîner les actions autorisées pendant sa session et utiliser GitHub CI configurée ; une IA absente ne se réveille pas spontanément. Ne pas écrire « je continue tout seul cette nuit » sans outil programmé et consentement.

## Algorithme d'orchestration pour l'IA active

1. Lire `NOW` et dernière issue/RELEASE ; découvrir si autre IA disponible.
2. S'il y a blocage P0 sans solution locale autorisée : documenter et proposer au propriétaire l'action unique ; choisir une tâche indépendante de **lecture seule** si utile.
3. Sinon, trouver le premier item BACKLOG `READY` non réservé dont dépendances sont terminées, effort court, critère d'acceptation testable.
4. Réserver, recontrôler SHA, implémenter ou auditer ; tester, demander REVIEW ; corriger défauts ; publier via autorisations et libérer.
5. Réévaluer les priorités sur résultats, pas sur esthétique seule ; répéter jusqu'à (a) backlog accepté vide, (b) DoD rempli, (c) outil bloqué, (d) autorisation manquante, (e) fin de session.
6. Avant arrêt, écrire HANDOFF compact et **une seule** prochaine action réellement faisable.

## Règles anti-boucle

- Maximum trois cycles sans progrès sur même défaut avant diagnostic contradictoire IA B ou arbitrage.
- Aucune introduction automatique d'outils coûteux, auth externe, stockage externe, refonte de stack ou changement de production.
- Si qualité cible non démontrée par métrique, laisser P1/P2 ouvert et noter écart ; ne pas « optimiser » indéfiniment une animation sans effet utilisateur.
- Interdiction de fermer un ticket par seule génération d'un document : exiger preuve de réalisation du critère.

## Mécanismes automatisables par GitHub

- Tests sur push, analyse statique, checks médias/i18n, audit de dépendances, commentaires automatisés **avec permissions explicites**, étiquettes issues (si accès). Ces automatisations détectent et remontent, elles ne remplacent ni l'accord humain pour publication ni un agent réellement actif.
- Un robot GitHub peut publier des rapports ; pour lancer effectivement IA B après un événement, il faut une application/agent compatible *installé et configuré* avec un déclencheur. Ne pas supposer cette capacité de la simple connexion GitHub.
