# Continuer le projet — mode autonome (par défaut)

## Pourquoi cette évolution

Le lanceur exigeait auparavant un objectif précis à chaque session, injectait la consigne « UNIQUEMENT la tâche ci-dessous » et déclarait le tour « terminé » dès que le CLI retournait le code 0. Pour « exécute tous les md », une IA a lu une grande partie des documents, n'a modifié aucun fichier et a pourtant obtenu le statut de session terminée. Ce n'est **pas** une preuve de développement réalisé.

## Comportement maintenant attendu

1. Ouvrir le projet existant, puis **Continuer le projet — démarrer l'IA**. Aucune reformulation d'objectif n'est nécessaire. La confirmation de lancement garde son rôle d'autorisation de modification.
2. Le mode autonome charge le contexte minimal : README, AGENTS et extraits des registres NOW/BACKLOG/ROADMAP. Il prend jusqu'à **quatre tâches vérifiables** dans une tranche, et poursuit après une micro-correction lorsque la tranche contient d'autres actions réalisables.
3. Il ne parcourt pas tous les fichiers Markdown. Les fichiers sont des références, **pas** des scripts à exécuter. Les checklists réglementaires, modèles, historiques et validations humaines ne deviennent pas automatiquement des modifications de code.
4. Lorsqu'aucun backlog exploitable n'existe, l'IA peut examiner quelques risques techniques du produit et réaliser une tranche de fiabilisation **seulement si elle reproduit des défauts réels**. Elle ne doit pas inventer des fonctionnalités ou des retouches inutiles pour justifier le temps passé.
5. Le kit compare les empreintes du travail Git avant et après le tour. **Code 0 sans changement vérifiable du produit = SANS PROGRÈS**, pas « terminé ». Le même état ne peut pas provoquer une nouvelle session autonome payante identique.
6. En cas de livraison vérifiable, un nouveau clic **Continuer le projet** autorise une nouvelle tranche, même si le texte du mandat n'a pas changé. L'historique des missions reste conservé.
7. Pour une intervention ponctuelle, utiliser **Tâche précise (facultatif)**. La seconde IA peut reprendre automatiquement le mandat de vérification de la première.

## Limites explicites

L'empreinte Git prouve qu'il y a eu des modifications dans le projet, **pas** que l'implémentation fonctionne : l'IA doit toujours fournir les tests et les preuves de validation. Une modification uniquement documentaire ne constitue pas, à elle seule, un avancement applicatif. Les créations d'artefacts de build et les fichiers temporaires ne doivent pas compter comme développement.

Le CLI demeure borné par la durée maximale existante. Le kit ne contourne pas les autorisations de l'environnement ni les protections du dépôt. Les push/commits automatiques restent gérés indépendamment, par projet, et désactivés par défaut. Un déploiement, une suppression destructive, une migration sensible ou un engagement financier nécessitent toujours un accord explicite.

## Critères de recette

- Le bouton **Continuer le projet** ne demande pas une phrase de travail obligatoire.
- « execute tous les md » est interprété comme un mandat de développement fondé sur les tâches pertinentes, sans lecture indiscriminée ni exécution de fichiers Markdown.
- Le prompt demande une tranche multi-actions plutôt qu'une unique retouche.
- Une session sans changement du produit n'est ni comptabilisée ni présentée comme une livraison ; ses nouveaux appels sont bloqués sur le même état du projet.
- Les projets et assistants restent séparés, et aucune sauvegarde GitHub n'est déclarée sans confirmation distante.
