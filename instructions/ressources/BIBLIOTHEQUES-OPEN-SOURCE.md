# Catalogue d'outils — références vérifiées au 18.10.2026

**Catalogue de candidats, pas liste de dépendances à installer.** Vérifier pour la version retenue les dernières releases, activité des mainteneurs, Security Advisories, obligations de licence et compatibilité de la stack/hébergeur. La présence d'un dépôt GitHub ne garantit pas « open-source » au sens OSI.

| Catégorie | Outil / dépôt officiel | Licence contrôlée / nuance | Usage conseillé et coût client |
|---|---|---|---|
| Vitrine à HTML statique | [Astro](https://github.com/withastro/astro) | MIT | SEO/HTML, faible JS ; gratuit, build local/CI |
| PHP framework | [Laravel](https://github.com/laravel/framework) | MIT | Applications CRUD/auth si PHP et outils compatibles ; gratuit |
| PHP composants/framework | [Symfony](https://github.com/symfony/symfony) | MIT | APIs/logiciel métier, composants ciblés ; gratuit |
| CMS local léger | [Grav](https://github.com/getgrav/grav) | MIT pour cœur | Contenu flat-file sur PHP ; extensions à vérifier |
| Administration PHP | [Filament](https://github.com/filamentphp/filament) | MIT pour dépôt inspecté | UI admin avec Laravel seulement si nécessaire ; plugins séparés |
| Style | [Tailwind CSS](https://github.com/tailwindlabs/tailwindcss) | MIT | Classes utilitaires si équipe apprécie, purge CSS ; gratuit |
| UI React | [shadcn/ui](https://github.com/shadcn-ui/ui) | MIT pour dépôt | Composants copiés et adaptés, revue dépendances ; gratuit |
| Icônes | [Lucide](https://github.com/lucide-icons/lucide) | ISC | SVG accessibles, importer seulement nécessaires ; gratuit |
| Animation web | [Motion](https://github.com/motiondivision/motion) | MIT pour package `motion` | Transitions ciblées ; gratuit, poids contrôlé |
| Défilement fluide | [Lenis](https://github.com/darkroomengineering/lenis) | MIT | Seulement si indispensable, attention scroll natif ; gratuit |
| 3D React | [react-three-fiber](https://github.com/pmndrs/react-three-fiber) | MIT | Scènes ciblées avec fallback ; poids GPU/JS à mesurer |
| Images build | [Sharp](https://github.com/lovell/sharp) | Apache-2.0 | Resize, WebP, AVIF, formats, sans cloud ; gratuit |
| Vidéos build | [FFmpeg](https://github.com/FFmpeg/FFmpeg) | LGPL-2.1+ cœur ; GPL selon compil./libs | Transcodage local ; codecs et distribution à contrôler |
| i18n | [i18next](https://github.com/i18next/i18next) | MIT | Projet JS à contenus localisés ; gratuit |
| E2E | [Playwright](https://github.com/microsoft/playwright) | Apache-2.0 | Tests navigateur locaux/CI ; gratuit |
| Accessibilité | [axe-core](https://github.com/dequelabs/axe-core) | MPL-2.0 | Tests a11y automatisés ; ne remplace pas tests humains |
| Performance | [Lighthouse](https://github.com/GoogleChrome/lighthouse) | Apache-2.0 | Audit lab, utiliser budgets/conditions reproductibles |

## Outils **gratuits mais pas à présenter comme open-source MIT**

| Outil | Licence/condition vérifiée | Recommandation |
|---|---|---|
| [React Bits](https://github.com/DavidHDev/react-bits) | **MIT + Commons Clause** en 2026 : usage dans site/application, **restriction de revente/redistribution des composants comme bibliothèque** | Usage commercial de site possible sous conditions ; vérifier avant templates vendus à autrui |
| [GSAP](https://github.com/greensock/GSAP) | Licence propriétaire standard « no charge », pas MIT/OSI | Usage permis selon termes officiels **à relire**, ne pas confondre gratuit et open source |

## Décider une dépendance — fiche obligatoire

```text
Composant :             URL repo/paquet :
Version/commit :        Dernière release vérifiée le :
Licence source :        Obligations produit/redistribution :
Maintien (commits/avis) :    Vulnérabilités connues :
Fonction irremplaçable :     Alternatives sans dépendance :
Compatibilité stack/hosting :
Coût réel : npm/gratuit/licence/temps/JS/CPU :
Impact bundle mesuré après installation :
Décision ADR : ACCEPTÉ / REFUSÉ + justification :
```

## Processus de veille

Avant chaque installation, ouvrir **licence du paquet réellement installé** et non seulement la vitrine, dernières releases, risques de dépendance et lockfile ; épingler versions adaptées. Tous les trois mois, relire sécurité/compatibilité et créer tâches ciblées. Pas de modification massive automatique des versions majeures.
