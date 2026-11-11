# PROMPT 11 — IMAGES, VIDÉOS, FONTS : AUDIT ET PIPELINE OPTIMISÉ

Lis les pages et médias réels. Construis `docs/MEDIA_INVENTORY.md` : fichier, licence, rôle, largeur/hauteur, ratio responsive, poids, format, alternatives, texte alt ou décoratif, affichage et priorité. Si aucun média n'est fourni, propose un sourcing/livrable nécessaire **sans inventer des assets existants**.

**Images** : générer dérivés WebP/AVIF quand outils disponibles, variantes `srcset/sizes`, fallback navigateur, dimensions stables, recadrages mobiles, éviter lazy sur LCP, lazy sous la ligne de flottaison, priorisation LCP parcimonieuse. Sélectionner ImageMagick/Sharp/Squoosh/service CDN en fonction de stack et volumétrie ; pas d'installation imposée.

**Vidéo** : décider si hero vidéo ajoute de la valeur ; si demandée, durée, story, MP4 H.264/WebM appropriés, poster prioritaire, `muted playsinline`, pause, motion reduced, `preload` mesuré, version mobile et débit adapté ; vidéos secondaires différées ; sous-titres/transcription si utile. Décrire pipeline FFmpeg seulement si l'outil existe ou peut être installé légitimement.

**Font/icon** : WOFF2 et sous-ensembles, chargement maîtrisé, SVG sûrs, attribution/licence. **Livrer** scripts de conversion exacts adaptés aux outils présents, rapport des poids avant/après **mesurés** ou « non mesuré », composants HTML valides, résultats Lighthouse/CrUX si réellement disponibles.
