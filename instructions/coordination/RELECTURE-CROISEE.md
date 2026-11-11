# Audit contradictoire par IA B

**B ne doit pas modifier le périmètre réservé**. Elle inspecte le diff, l'architecture, la qualité du design, la sûreté des médias et les preuves de tests. Elle ne certifie pas un test qu'elle n'a pas exécuté.

## Revue en 3 passes

1. **Bloquants** : secret, isolation utilisateur, XSS/CSRF, droits médias, déploiement contraire à la politique, perte de données, appel SaaS prohibé, bugs formulaires, URLs i18n cassées, hero sans source ou poster.
2. **Fonctionnel/UX** : conversion, clarté de l'offre, navigation mobile, tabulation/focus, contrastes, texte long en allemand, motion réduite, vidéos sur mobile, cookies et consentement, SEO canonicals/hreflang.
3. **Optimisation** : bundle JS, images redimensionnées, vidéos re-encodées, dépendances inutiles, duplication de composants, poids de police et LCP/INP/CLS.

## Grille résultat

| ID | Gravité | Fichier / ligne | Preuve reproductible | Correction minimale | Test d'acceptation |
|---|---|---|---|---|---|
| B-01 | P0/P1/P2 | ... | ... | ... | ... |

Verdicts : `FIX_REQUIRED`, `APPROVED_WITH_RESERVES`, `APPROVED`. Mentionner SHA réellement relu et s'il s'agit d'une revue avant commit ou après commit. Renvoyer `REVIEW v1` dans issue ; ne pas « approuver » un patch changé après inspection sans nouveau SHA.

## Contrôle indépendant

Si terminal disponible, lancer **les vrais tests** sur copie synchronisée non destructive ; sinon lire les résultats CI et indiquer « résultat CI consulté, non reproduit ». Réaliser un screenshot mobile, keyboard, reduced motion si navigateur autorisé. Pour tout défaut : relier à un scénario utilisateur et un test d'échec. Écarter les préférences purement esthétiques sans bénéfice explicité.
