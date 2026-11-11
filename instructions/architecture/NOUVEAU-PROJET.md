# Nouveau projet — déroulé sans framework imposé

**Entrées :** objectif utilisateur, domaine, public, langues, contenus et préférence d'hébergeur s'ils sont connus. **Responsable :** IA A ; B intervient à la fin des étapes cadrage, prototype et sécurité.

## S0. Découverte

- Établir 1 persona principal, 1 problème, 1 résultat, 1 indicateur, hypothèses et hors périmètre.
- Confirmer obligations communes : hébergeur local, vidéo hero, i18n, accessibilité, aucune base SaaS.
- S'il manque des informations, noter `[À CONFIRMER]` et choisir une option réversible ; ne pas inventer raison sociale, prix, droit d'image ni promesse réglementaire.

## S1. Cadrage

- Compléter `projet/PRD.md`, `projet/ROADMAP.md`, `projet/BACKLOG.md` et conditions d'acceptation au format « Étant donné / Quand / Alors ».
- Définir contenu navigation, sitemap, CTA principal, microcopies, pages légales et système de suivi de conversions **local ou auto-hébergé** si vraiment nécessaire.
- Prévoir quatre langues (fr/en/de/it) pour public suisse, sinon conserver framework i18n et ne traduire qu'avec qualité contrôlée.

## S2. Architecture

- Vérifier compatibilité hPanel/panel Infomaniak, PHP, DB, Node, SSH, cron, permissions, sauvegarde ; comparer plusieurs stacks et ADR.
- Définir authentification/session uniquement si PRD l'exige ; comptes séparés, politique des droits, CSRF, sauvegardes et journalisation minimale.
- Définir arborescence : application, `assets-source/` hors public, `public/media/`, `locales/`, tests, scripts, docs et sauvegardes hors racine web.

## S3. Création des fondations

- Créer les vrais fichiers exécutables du projet (selon stack), exemple `package.json`/`composer.json`, configs, `.gitignore`, `.env.example` fictif, README, scripts `check`, `build`, `media`, `i18n` ; installer uniquement les dépendances validées.
- Implémenter design tokens et composants accessibles ; hero vidéo local et poster ; routes localisées, 404 et messages traduits ; tests de fumée.
- Matérialiser les templates Markdown dans les véritables workflows CI/tests/scripts après adaptation, dans des fichiers **hors du kit ZIP**.

## S4. Développement séquentiel

Pour chaque fonctionnalité : PLAN → CLAIM → implémentation → tests → REVIEW B → corrections → handoff. Une tranche est autonome et observable ; aucune fonctionnalité déclarée faite sans preuve réelle.

## S5. Recette et proposition de publication

- Lint, types, unités, E2E, accessibilité, langues, SEO, performance, vérification de médias et licences, sauvegarde/rollback, test formulaires et permissions.
- Préparer bundle de publication et instructions d'hébergement ; **arrêter avant déploiement** jusqu'à autorisation spécifique du propriétaire.

**Reprise d'erreur :** consigner la commande, code de sortie, fichiers incriminés, cause probable, correction minimale, puis relancer le même test. N'effectuer ni reset destructeur ni changement de stack réflexe.
