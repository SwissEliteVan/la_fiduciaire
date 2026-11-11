# Skill avancé — direction artistique, bibliothèque UI et recette design

**Objectif :** garantir une identité professionnelle cohérente, mesurée en accessibilité et performance, plutôt qu'une accumulation d'effets visuels. IA B définit et audite la cohérence ; IA A implémente les tokens et composants. Ne copier ni le contenu ni les graphismes d'un concurrent.

## 1. Brief design systématique (obligatoire avant la home)

- Positionnement : secteur, zone géographique, offre, confiance attendue, ton et contraintes de marque.
- Public : trois parcours prioritaires (découverte, comparaison, prise de contact/achat) ; navigation sur mobile et environnement réseau.
- Objectif : une action primaire et une secondaire maximum par section ; instrumentation respectueuse des données.
- Palette : rôle émotionnel des couleurs, contrastes mesurés, fonds sur vidéo, mode clair/sombre si réellement utile.
- Typographie : sources légales des polices, performance des fichiers WOFF2 auto-hébergés, échelles responsive, line-height et prise en charge latin étendu (DE/IT).
- Médias : palette réelle des images autorisées, direction photographique, cadrages mobiles et source vidéo hero conforme.

En sortie : `design/BRIEF.md` dans le projet réel, 3 adjectifs de marque, structure des pages, promesse et CTA mesurable. Si le propriétaire a déjà une charte validée, la préserver.

## 2. Tokens — une seule source CSS

Utiliser une grille d'espacement courte, tailles fluides et valeurs sémantiques. Exemple **adaptable**, à traduire en CSS réel selon la stack :

```css
:root {
  --color-surface: #ffffff;
  --color-ink: #1a2430;
  --color-accent: #235fce;
  --color-danger: #ad253d;
  --color-focus: #ffbe3a;
  --space-1: .25rem; --space-2: .5rem; --space-3: .75rem;
  --space-4: 1rem; --space-6: 1.5rem; --space-8: 2rem;
  --space-12: 3rem; --space-16: 4rem;
  --radius-card: .75rem;
  --font-body: system-ui, sans-serif;
  --step-0: clamp(1rem, .98rem + .1vw, 1.125rem);
  --step-3: clamp(2rem, 1.1rem + 3vw, 3.75rem);
}
:focus-visible { outline: 3px solid var(--color-focus); outline-offset: 3px; }
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { scroll-behavior: auto !important; }
}
```

Les couleurs ci-dessus sont **illustratives**, non une palette imposée : vérifier les contrastes réellement obtenus avant adoption. Ne pas forcer des transitions globales qui rendent la page inaccessible.

## 3. Composants de référence

Inventorier : `Button`, `Link`, `Input`, `Textarea`, `Select`, `FormMessage`, `Navbar`, `Footer`, `LanguageSwitcher`, `HeroVideo`, `Card`, `Accordion`, `Modal` uniquement si besoins avérés, `Toast/Status`, `Loading`, `Empty`, `Error`, `Success`. Les états hover/focus/disabled/invalid et clavier sont testés au même titre que le rendu esthétique.

Documentation minimale par composant : rôle ; variants/tokens ; comportements souris/tactile/clavier ; aria/texte traduits ; états de chargement ; coût JS ; exemple ; captures petit/grand écran ; DoD. Éviter de mélanger trois bibliothèques pour les mêmes primitives.

## 4. Bibliothèques : choisir par besoin, pas effet de mode

- **Simple vitrine** : HTML/CSS natif et SVG Lucide ; Astro/Alpine lorsque besoin d'interactivité ; pas de React ou 3D obligatoires.
- **Application React existante** : primitives contrôlables de shadcn/ui si licence et dépendances vérifiées ; Motion pour transitions ciblées.
- **Scroll et scène** : GSAP/Lenis/React Three Fiber seulement si interaction justifiée et coût bundle/perf mesuré ; vérifier les licences GSAP et React Bits avant redistribution de templates.
- **Couverture hero** (si le PRD ou le profil strict l'exige) : vidéo locale + poster et overlay lisible ; `prefers-reduced-motion`, contrôle pause et fallback obligatoires dès qu'une vidéo est utilisée, même pour site très cinématique.

## 5. Audit QA IA B — six angles

1. **Composition** : densité visuelle, hiérarchie, alignements, marges cohérentes ; promesse et CTA au-dessus du pli sur mobile/desktop.
2. **Lisibilité** : contrastes sur vidéo *en mouvement* (pas une seule frame), longueur de ligne, corps et espacement, zoom 200 % ; pas de texte incrusté si HTML traduisible.
3. **Responsive** : 320, 390, 768, 1024, 1440, 1920 px ; aucune zone interactive occultée par une animation ; menu utilisable tactile/clavier.
4. **Langues** : celles du PRD (profil strict : FR/EN/DE/IT), localisation des dates, montants, labels et formulaires, pas de troncation silencieuse des langues longues (allemand…).
5. **Mouvement** : pas de paralysie du contenu si animation échoue, éléments interactifs stables, reduced motion utile, consommation GPU raisonnable.
6. **Conversion** : une proposition claire, preuve non inventée, prix/conditions honnêtes, CTA effectivement fonctionnel, parcours formulaire sans blocage.

Comparer avant/après au même viewport, locale, contenu, polices et état de chargement. Tracer régression avec capture et chemin du composant, puis créer FIX vérifiable, pas une liste subjective de préférences.

## 6. Critères de sortie

- Tous les composants essentiels partagent tokens et conventions d'accessibilité ; captures et test clavier disponibles.
- Hero réellement alimenté par vidéo locale autorisée ; repli poster et pause vérifiés.
- Tests UI mobile/desktop dans chaque langue du PRD (profil strict : FR/EN/DE/IT) + axe-core et examen humain ; pas de débordement ; médias externes seulement si le projet les autorise.
- Charge JS, poids médias et indicateurs Web Vitals mesurés ; pas de régression majeure sans justification liée à la valeur métier.
- IA B émet verdict **PASS / FIX / BLOCKED** avec preuves ; seules les régressions prouvées sont qualifiées de bloquantes.
