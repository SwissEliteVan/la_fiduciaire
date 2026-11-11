# Skill — préparation de release, hébergement, restauration

**Déployer seulement après accord explicite du propriétaire.** Le fait de terminer le code ou d'avoir accès à l'hébergement ne donne pas l'autorisation.

## Préflight

- ADR stack et preuve capacité du compte ; versions runtime ; `.env` non présent dans Git ; choix de sous-domaine de test compatible ; certificats et domaine prévus.
- Tests CI + lint/types/build + E2E + licence et sécurité + route locale des médias et vidéo hero + 404 + hreflang/canonical ; contrôler manifest de fichiers statiques si applicable.
- Snapshot du répertoire et DB existants, datés et restaurables ; méthode rollback minutée et **testée** hors prod ; stockage sauvegarde sur hébergeur/outil autorisé, protéger accès.
- `RELEASE_PLAN` : SHA, liste exacte des fichiers publiés, diff, site cible, plan HTTP smoke, autorisations, risques et condition rollback. Confirmation du propriétaire sur **ce plan précis**.

## Procédure après accord

1. Revalider SHA/CI, aucun commit concurrent, mode maintenance uniquement si nécessaire.
2. Transférer artefacts via méthode du compte : contenu du `out/` pour site exporté, préserver fichiers cachés `.htaccess`, PHP local ; pour PHP app, document root sur dossier `public` et migrations contrôlées ; pour Node, dashboard officiel et persistances vérifiées.
3. Tests en ligne : 200, HTTPS, assets media/Range, autoplay/fallback, languages, formulaire e-mail, accès interdit, erreurs, analytics opt-in si présent, logs, 404, sitemap/robots.
4. Consigner versions, dates, résultat, décision du propriétaire ; déclencher le rollback **seulement dans les limites de l'autorisation** et vérifier à nouveau.

## Post-mortem

Tout incident → issue P0, impact, détection, rollback, correctif, test anti-régression. Sauvegarde et restauration font partie du produit, pas d'une note facultative.
