# 06 — Livrer et sauvegarder sans risque

1. Vérifie l'acceptation de la tranche, ses tests réels et les réserves de l'étape 04.
2. Explique à une personne non technicienne **comment ouvrir et voir le résultat**, ce qui fonctionne et ce qui manque.
3. Contrôle la liste des fichiers modifiés, les secrets, les médias et le statut des tests (PASS / FAIL / NON EXÉCUTÉ).
4. Si Git est présent, distingue **commit local** de **push GitHub**, affiche la destination et les fichiers concernés, et demande une confirmation séparée pour chaque action. Aucun force push, aucune nouvelle branche. Si Git absent, conseiller une sauvegarde locale sans rendre Git obligatoire.
5. Aucun déploiement, suppression, écrasement ni migration sans accord explicite.
6. Prépare une **note de passation portable** entre IA : objectif, décisions approuvées, fichiers importants, travaux, tests, points ouverts, prochaine action. Ne l'enregistre dans le projet qu'après accord.

Termine par : résultat utilisable, sauvegarde locale/distance prouvée ou non, limites et prochaine action. Ne dis jamais « publié » pour un simple commit local.
