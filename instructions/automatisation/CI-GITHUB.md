# Générer la CI réelle dans le projet — modèles GitHub Actions

**Ce fichier est Markdown, pas un workflow exécuté.** Après identification du stack et autorisation d'écriture, IA A génère **dans le dépôt cible** `.github/workflows/ci.yml` adapté au `package.json`/`composer.json`, puis teste une exécution réelle. Les exemples sont des **bases à ajuster** et ne prouvent pas une exécution.

## A. Node/pnpm — site Astro, React, Next

```yaml
name: quality-gate
on:
  push:
    branches: [main]
  workflow_dispatch:
permissions:
  contents: read
concurrency:
  group: quality-${{ github.ref }}
  cancel-in-progress: true
jobs:
  quality:
    runs-on: ubuntu-latest
    timeout-minutes: 25
    steps:
      - uses: actions/checkout@v4 # EN PRODUCTION : remplacer le tag par SHA d'action approuvé
      - uses: actions/setup-node@v4 # EN PRODUCTION : pinner SHA et version runtime du projet
        with:
          node-version: '22' # EXEMPLE ; choisir version compatible avec la stack
      - run: corepack enable
      - run: corepack pnpm install --frozen-lockfile
      - run: corepack pnpm lint
      - run: corepack pnpm typecheck
      - run: corepack pnpm build
      # Ajouter tests E2E seulement si scripts réels + navigateurs installés :
      # - run: corepack pnpm test:ci
      # Ajouter check des clés i18n/media/manifests :
      # - run: corepack pnpm check:locales && corepack pnpm check:media
```

**Attention :** GitHub Expressions `${{ ... }}` sont des expressions **dans le YAML copié**, sans effet dans ce kit. Si `lint`/`typecheck` absent, créer et justifier la commande ou omettre ; ne pas laisser de step qui échoue à cause d'une commande inventée. Next static export : valider présence sortie statique et 404.

## B. PHP / Composer — site Symfony/Laravel ou backend léger

```yaml
name: quality-php
on:
  push:
    branches: [main]
  workflow_dispatch:
permissions:
  contents: read
jobs:
  php:
    runs-on: ubuntu-latest
    timeout-minutes: 20
    steps:
      - uses: actions/checkout@v4 # pinner SHA après revue
      - uses: shivammathur/setup-php@v2 # pinner SHA après revue
        with:
          php-version: '8.3' # EXEMPLE : vérification avec compte et composer.json
          tools: composer:v2
      - run: composer install --no-interaction --prefer-dist --no-progress
      - run: composer audit --no-interaction
      - run: ./vendor/bin/phpunit # SEULEMENT si réellement installé/configuré
```

Pour un PHP minimal sans Composer, préférer `find public -name '*.php' ... php -l` avec gestion robuste des chemins, plus tests HTTP dédiés. Ne pas inventer de suite PHPUnit.

## Gates supplémentaires recommandés

- `security` : secrets scanner local/CI, audits dépendances et restrictions de services externe.
- `media` : manifest checksum, original conservé, droits `verified`, aucun `hero` absent, versions WebM/MP4/poster et taille budget.
- `i18n` : parité des clés, pas de doublons/placeholder cassé, hreflang tests au build.
- `e2e`: Playwright desktop/mobile + reduced motion et formulaire ; installer les navigateurs **sur runner CI**, pas sur hébergement.
- `performance` : Lighthouse reproductible, seuils comme avertissements au début, blocants après baseline mesurée.

## Important sur « main uniquement »

Une CI déclenchée **après** `push main` signale les défauts mais ne protège pas ce commit. Si aucune branche n'est autorisée, la revue de diff local avant commit est le meilleur contrôle précoce ; options de ruleset GitHub à définir avec propriétaire, sans présumer un mécanisme empêchant tous les push. Le workflow `concurrency` évite deux jobs simultanés, **pas deux éditeurs écrivant en même temps**.

## Politique de production

N'ajouter **aucun `deploy` dans CI** par défaut. Aucune connexion à hPanel/Infomaniak ni secret de production dans les jobs sans accord détaillé. Les Actions peuvent transférer les artefacts *après autorisation* si une procédure a été vérifiée ; conserver le droit de rollback et les logs.

## Brancher les contrôles avancés

- Modèles de tests : `automatisation/QA-UNIFIEE.md` pour E2E, hero, langues, responsive, a11y et budgets.
- Traitement des actifs : `automatisation/PIPELINE-MEDIAS.md` pour inventorier et `automatisation/TRANSFORMER-MEDIAS.md` pour préparer des dérivés, **hors de la CI de production**, sauf machines prévues pour le volume.
- Gestion des événements IA : `automatisation/ORCHESTRATEUR-GITHUB.md`, déclencheur séparé sans droit de déploiement.
- Les actions CI et leurs permissions doivent être testées dans le vrai dépôt ; ne pas utiliser d'identifiants du propriétaire dans les logs ou des artefacts.
