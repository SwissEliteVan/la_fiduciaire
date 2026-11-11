# PROMPT 12 — INTÉGRER DES ANIMATIONS PROFESSIONNELLES PRÊTES À L'EMPLOI

**But : un site vivant, spectaculaire et maîtrisé, pas un site figé.** Pour **[PAGES]**, améliorer les interactions avec des animations visuellement abouties, sélectionnées dans des **bibliothèques, composants ou presets professionnels déjà existants**. La demande d'un site animé implique de vraies animations en mode normal. Il est interdit de conclure « aucune animation » uniquement parce que créer un effet de zéro serait coûteux.

## Choisir rapidement, sans gaspiller les tokens

1. Lire le frontend réel, le lockfile, la direction artistique et la recette antérieure. **Ne pas recommencer les audits ou conversions déjà terminés.** Inventorier brièvement les animations et bibliothèques déjà présentes ; privilégier leur réutilisation.
2. Étudier un maximum de **trois solutions pertinentes** offrant des démonstrations visuelles officielles et des presets/composants directement réutilisables. Distinguer un **moteur** (GSAP/Motion, nécessitant parfois du code) d'une collection de **composants prêts à l'emploi**. Ne jamais imposer React à un projet Vite/JS natif ni forcer un nouveau framework.
3. Vérifier licence commerciale actuelle, version, dépendances, poids JS/CSS, maintenance, performances mobile, clavier/tactile, `prefers-reduced-motion`, compatibilité du build statique et coût. Une licence payante ou un service externe à facturer nécessite l'accord spécifique du propriétaire.
4. **Si l'implémentation est autorisée**, installer et intégrer le plus petit ensemble justifié de composants/presets existants, régler leur configuration, les tester dans un vrai navigateur et conserver les sources. **Le code de branchement minimal est autorisé ; ne pas inventer de longs effets maison en CSS/JS/SVG/Canvas.** Si un preset est incompatible, choisir un autre existant plutôt que produire une imitation fragile.
5. Couvrir au minimum, si le parcours le justifie : entrée du hero, apparition des sections, hover/liens/CTA, galerie interactive, navigation et interactions tactiles. Ne pas faire bouger un élément sans intention ni compromettre la lecture.
6. Tester au même viewport avant/après, mobile peu puissant, contrôles clavier, visibilité du contenu sans JS, `prefers-reduced-motion`, CLS/INP/LCP et erreurs réseau. Un test non exécuté est `NON EXÉCUTÉ`.

## Livraison

En **mission d'audit seulement** : démonstrations réelles, recommandation et plan sans fichiers modifiés. En **mission de développement autorisée** : composants intégrés et effectivement visibles, fichiers exacts, captures, tests PASS/FAIL/NON EXÉCUTÉ, URL de prévisualisation locale et limites. Ne pas arrêter la livraison après la simple recommandation lorsque l'implémentation fait partie du mandat.

**Git : main uniquement**, sans branche/PR/worktree de branche. Sauvegarder par commit ciblé et push fast-forward `origin/main` après tests/diff si le mandat Git l'autorise, jamais en écrasant un travail concurrent ; aucun déploiement sans autorisation distincte.
