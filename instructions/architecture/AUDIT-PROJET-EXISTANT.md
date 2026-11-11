# Audit complet d'un projet existant — preuves et optimisations

**Entrée :** repo + SHA + permissions. **Sortie :** état observé, risques classés, backlog et premier correctif minimal. IA A effectue l'inventaire ; IA B challenge et produit l'analyse UX indépendante.

## Inventaire exhaustif contrôlé

1. `git status --porcelain=v1`, branche, SHA `HEAD/origin/main`, diff et fichiers non suivis ; protéger le travail non commité.
2. Lister **tous les chemins `.md`** sous le repo (hors `node_modules`, `.git`, builds et vendeurs tiers) ; créer un index `chemin / taille / catégorie / lu intégralement oui-non / autorité`. Lire en entier les documents faisant autorité, puis ceux utiles à la décision. **Ne jamais affirmer « tous lus » après simple liste.**
3. Inspecter `package.json`, lockfile, `composer.json`, `next.config`, scripts build, CI, runtime, configurations d'hébergement et secrets *sans les afficher*.
4. Parcourir entrée → route → formulaire/API → stockage et authentification, ainsi que sitemap, canonical, pages légales, CSS/fonts, événements, analytics ; identifier zones non observables.
5. Inventorier tous les médias et les licences, doublons SHA-256, formats/dimensions, poids public, références et vidéo hero ; examiner `assets`, `addets`, `images`, `videos` et variantes.
6. Analyser i18n : ressources locales, `hreflang`, sélection, fallback, contenu traduit, liens localisés, longueur de chaînes, 404.
7. Exécuter commandes réellement présentes et autorisées ; résultats exacts. Vérifier responsive, reduced motion, tests mobile, permaliens, performances à froid. Sans exécution : « non exécuté ».

## Grille de notation 0–3

0 inexistant ; 1 présent mais non éprouvé ; 2 fonctionnel démontré ; 3 stable, documenté et testé. Dimensions : hébergement, sécurité, fonction, conversion, perf, UX, SEO, accessibilité, i18n, média, tests, documentation, reprise, sauvegarde.

## Rapport de défaut minimal

| P | Problème | Preuve chemin/ligne/test | Impact | Correction minimale | Effort | Responsable | Critère |
|---|---|---|---|---|---|---|---|
| P0 | [risque sécurité/droit] | [preuve] | [impact] | [correctif] | S/M/L | A | [test] |

- **P0** : risque sécurité, données, droits/licences ou déploiement cassé.
- **P1** : fonctionnalité de vente/usage, SEO critique, temps de chargement.
- **P2** : dette, cohérence design, ergonomie, traductions manquantes.
- **P3** : embellissements sans démonstration de valeur.

## Décision de suite

Proposer le top 5 avec dépendances et bénéfice attendu ; demander à B un `REVIEW-AUDIT` ; retenir une seule tâche dans `NOW.md`, sans refonte générale. Archiver les observations dans CONTEXT sans recopier 50 pages de journal. Ne jamais qualifier un risque d'erreur avérée sans test ou preuve directe.
