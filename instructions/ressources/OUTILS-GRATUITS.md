# Choix d'outils gratuits — sans infrastructure applicative tierce

## Outils de production locale / CI

- **Git/GitHub** : code, issues, diff, Actions pour **build/tests éphémères**, jamais base ou stockage média de production. Accès/quotas GitHub à vérifier ; gratuité du service dépend du plan.
- **FFmpeg + ffprobe** : identifier vidéo/source, transcoder localement, poster ; vérifier licences du binaire et codecs. https://ffmpeg.org/
- **Sharp / libvips** : batch d'images WebP/AVIF selon bundle JS ou CLI adapté. https://sharp.pixelplumbing.com/
- **Python Pillow (option)** : transformation simple ; vérifier licence et qualité ; inutile si Sharp présent.
- **Playwright** : captures visuelles locales/CI + navigateur réduit motion ; https://playwright.dev/
- **Lighthouse + axe-core** : mesures lab, pas preuve d'audit humain ni de conformité légale. https://github.com/GoogleChrome/lighthouse ; https://github.com/dequelabs/axe-core
- **Composer audit / npm audit / OSV-Scanner** : vulnérabilités des dépendances ; vérifier intégration officielle et faux positifs ; https://github.com/google/osv-scanner
- **Gitleaks** (open-source sous conditions de version) : scanner les secrets ; consulter licence du dépôt https://github.com/gitleaks/gitleaks et épingler Action/SHA si choisi.

## Règle de dépendance

Les outils ci-dessus tournent **sur poste développeur ou CI**, sans devenir composants SaaS requis en production. N'utiliser ni traduction distante de données sensibles ni API d'images stockant les médias chez un tiers. L'IA peut suggérer une ressource publique, mais elle ne peut lui attribuer de droits de reproduction par défaut.

## Coût total

Gratuit en licence ≠ gratuit en CPU, temps, maintenance ou limites GitHub. Pour chaque outil, indiquer : installation, poids, licences transitives, compatibilité Windows/Linux, licences codec, coût serveur et plan de remplacement.
