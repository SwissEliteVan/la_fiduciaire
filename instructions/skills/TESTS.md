# Skill — tests reproductibles et preuve de qualité

## Test pyramid

1. **Static** : format, lint, types, dépendances, licences, secrets, invariants (aucun Supabase, pas de URL vidéo tierce ni appel DB hors hébergeur), traductions/parité clés.
2. **Unit** : règles métier, validation, composants critiques, gestion des erreurs, générateur de médias (manifest), localisation.
3. **Intégration** : POST réel avec faux SMTP local/DB locale si nécessaire, auth+permissions, fichiers, migration en DB test, rendu statique et routes.
4. **E2E** : parcours mobile/desktop, formulaires success/échec, keyboard, reduced-motion, video source/erreur/poster, langue FR/DE/EN/IT, liens, 404 et consentement si présent.
5. **Visuel** : captures baseline viewport 390×844 et 1440×900 au minimum, puis 320/768/1920, zoom 200 %, hero et longs mots en allemand, comparaison explicite ; aucune capture manquante présentée comme test passé.
6. **Performance** : Lighthouse local CI si machine compatible, budget, comparaison réseau de vidéo et poster ; résultats terrain séparés des laboratoires.
7. **Sécurité** : entrées, permissions, multi-tenant si applicable, dépendances, sauvegarde, droits/PII et uploads.

## Tableau d'exécution obligatoire

| Commande réelle | Environnement | Code retour | Statut PASS/FAIL/NOT_RUN | Preuve |
|---|---|---:|---|---|
| [npm/pnpm/composer] ... | [versions] | [0/non 0] | [statut] | [log lien] |

- Un build réussi **ne prouve pas** que l'écran fonctionne ni qu'un e-mail est livré.
- Un test E2E local **ne prouve pas** que le serveur Hostinger prend en charge PHP, `.htaccess`, Node ou `mail()`.
- Toute étape non exécutée = `NOT_RUN(reason)`, pas PASS.
- Si un outil impossible dans CI : documenter contrôle manuel et refus d'affirmer la couverture.

## Matrice de recette minimum

Pages × langues × 2 viewports × motion normal/réduit × réseau normal/lent pour hero ; formulaires valides/invalides/limites, menus, erreurs, liens, console sans exception, SEO/hreflang, droits, publication statique ou session applicative selon stack.

**Release bloquée** par P0 (sécurité/droit/absence de vidéo hero autorisée), bug critique métier, tests critiques échoués, i18n cassée, données non isolées ou restauration non planifiée.
