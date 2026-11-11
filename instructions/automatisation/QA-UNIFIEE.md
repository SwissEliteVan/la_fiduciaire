# Qualité automatisée — tests de parcours, langues, vidéo et accessibilité

**À matérialiser dans le dépôt cible** après avoir déterminé la stack et ses commandes réelles. Exécuter la QA à trois niveaux : (1) pré-commit local rapide, (2) CI post-push, (3) recette visuelle/manuelle indépendante par IA B. **Une CI verte ne remplace pas une recette visuelle ni l'autorisation de publier.**

**Interdiction par défaut :** aucun agent ne crée automatiquement de répertoire `medias/qa/*` ou `refonte-apres`, de lot de captures JPG/PNG, ni de fichier de mesures QA ; aucun navigateur/Playwright de capture et aucune analyse d'images IA sans autorisation précise, explicite et préalable du propriétaire. Une configuration antérieure ne vaut pas accord. Les contrôles non graphiques restent possibles ; noter **NON EXÉCUTÉ** pour les preuves visuelles absentes. Conserver tous les médias et preuves déjà présents.

## Ordre de vérification recommandé

| Niveau | Déclencheur | Contrôles et seuil |
|---|---|---|
| Preflight | Avant commit/push autorisé | Diff, secrets, formatage, lint, types, tests ciblés, état Git propre hors fichiers modifiés |
| CI | Push/dispatch sur branche autorisée | Install verrouillée, lint, types, tests unitaires/API, build, i18n keys, budget médias, dépendances, E2E |
| QA B | Demande REVIEW réelle | Contrôles statiques et revue de preuves existantes ; captures multi-écrans uniquement avec accord spécifique préalable du propriétaire, sinon NON EXÉCUTÉ |
| Release | Validation par propriétaire | HTTPS, sitemap, robots, licence média, erreurs serveur, sauvegarde/restauration, preuve de déploiement |

## Modèle Playwright complet — site HTML/Vite/Astro/Next export

Installer Playwright localement et créer `tests/e2e/site.spec.ts` lorsque le test runner et `baseURL` sont configurés. Le script vérifie des invariants **observables** ; IA A adapte sélecteurs de navigation et routes du PRD, sans les supprimer pour faire passer la CI.

```typescript
import { test, expect } from '@playwright/test';

const locales = ['fr','en','de','it'] as const;
const origin = process.env.TEST_BASE_URL ?? 'http://127.0.0.1:4173';

test.describe('Livraison site multilingue', () => {
  for (const lang of locales) {
    test(`${lang} : document, CTA, hero local`, async ({ page }) => {
      await page.goto(`${origin}/${lang}/`, { waitUntil: 'domcontentloaded' });
      await expect(page.locator('html')).toHaveAttribute('lang', lang);
      await expect(page.locator('main h1')).toHaveCount(1);
      await expect(page.locator('main a[href], main button').first()).toBeVisible();
      const video = page.locator('main video').first();
      await expect(video).toHaveCount(1);
      const sources = await video.locator('source').evaluateAll(nodes => nodes.map(n => n.getAttribute('src')));
      expect(sources.some(src => src?.startsWith('/media/'))).toBeTruthy();
      expect(await video.evaluate((v: HTMLVideoElement) => v.muted)).toBe(true);
      const hreflang = await page.locator('link[rel="alternate"][hreflang]').count();
      expect(hreflang).toBeGreaterThan(0);
      await expect(page.locator('body')).not.toContainText(/\[missing translation\]/i);
    });
  }

  test('Préférence mouvement réduit : pas de lecture auto', async ({ browser }) => {
    const context = await browser.newContext({ reducedMotion: 'reduce' });
    const page = await context.newPage();
    await page.goto(`${origin}/fr/`);
    const video = page.locator('main video').first();
    await expect(video).toHaveCount(1);
    // La logique applicative DOIT éviter autoplay effectif sous reduced motion.
    const playing = await video.evaluate((v: HTMLVideoElement) => !v.paused && v.currentTime > 0);
    expect(playing).toBeFalsy();
    await context.close();
  });

  test('Aucun débordement horizontal en mobile', async ({ page }) => {
    await page.setViewportSize({width:390,height:844});
    await page.goto(`${origin}/de/`);
    const hasOverflow = await page.evaluate(() =>
      document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(hasOverflow).toBeFalsy();
  });

  test('Les médias essentiels répondent localement', async ({ page }) => {
    const failed: string[] = [];
    page.on('response', res => {
      if (res.url().includes('/media/') && res.status() >= 400) failed.push(`${res.status()} ${res.url()}`);
    });
    await page.goto(`${origin}/fr/`);
    await page.waitForLoadState('networkidle');
    expect(failed).toEqual([]);
  });
});
```

**Important :** le sélecteur `main video`, la racine `/fr/`, les balises `source`, les attributs boolean HTML, la pause et les `hreflang` sont des conventions du gabarit, pas des invariants universels. Le projet doit les respecter ou les adapter de façon **explicite**. Les navigateurs peuvent suspendre une vidéo pour économiser des ressources : n'en déduire ni performance ni lecture effective sans mesure.

## Accessibilité automatisée — complément, pas certification

Adapter et installer le paquet Playwright/axe correspondant à la stack (par exemple `@axe-core/playwright`) après contrôle licence/version :

```typescript
import AxeBuilder from '@axe-core/playwright';
const results = await new AxeBuilder({ page }).analyze();
expect(results.violations.filter(v => v.impact === 'critical' || v.impact === 'serious')).toEqual([]);
```

Compléter par test clavier, focus visible, contraste réel sur image en mouvement, zoom 200 %, navigation lecteurs d'écran, pause/reprise vidéo, messages d'erreur de formulaires annoncés et langue des contenus. Axe ne prouve pas à lui seul une conformité WCAG 2.2 AA.

## Budgets de qualité à fixer après baseline

- **Hero** : poids du MP4/WebM réellement téléchargé sur mobile, poster compressé et durée perçue ; définir budgets par page et par réseau, sans chiffres inventés.
- **Performance** : LCP/INP/CLS de données terrain si disponibles, audits Lighthouse en conditions fixes, pas un score unique comme preuve suffisante.
- **I18n** : même ensemble de clés, placeholders identiques, routes/hreflang valides et 404 traduites ; contrôle visuel en DE/IT.
- **Sécurité** : pas de secrets dans Git, autorisation côté serveur si données privées, anti-CSRF selon architecture, limitation de débit/abus, scanners de dépendances.
- **SEO** : canonical par langue, sitemap et robots corrects, titres/h1/JSON-LD cohérents, pas de pages locales vides auto-générées.
- **Données** : aucune PII dans événements analytics ou logs ; politique de conservation et suppression documentée.

## Rapport de preuves standard

```text
Issue / task / commit SHA :
Exécuté par (IA A / IA B / GitHub CI) :
Commande exacte et code de sortie :
Environnement/version :
Nombre de tests réussis/échoués/ignorés :
Captures/rapport de mesure réel :
Vérifications manuelles :
Non exécuté et motif :
Risques ouverts :
Verdict : PASS | FAIL | BLOCKED | NOT_RUN
```

**Règle CI `main` :** post-push ne signifie pas protection pré-push. En absence de PR, prendre des précautions pré-publication, conserver un commit récupérable, ne jamais réparer par force-push.
