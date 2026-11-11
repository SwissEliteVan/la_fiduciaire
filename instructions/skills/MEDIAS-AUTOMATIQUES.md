# Skill — découverte et transformation automatique des médias locaux

**Très haute priorité.** IA A pilote génération locale ; IA B vérifie les choix, licences et qualités visuelles. Outillage privilégié : `ffprobe`/`ffmpeg`, `sharp`/`libvips` (build), `exiftool` en option et `sha256sum`/hash Python. Aucun Cloudinary, Imgix, S3, API d'upload tierce.

## Pipeline obligatoire (8 étapes)

1. **DISCOVER** : parcourir récursivement `assets`, `addets` (typo fréquente), `asset`, `medias`, `media`, `images`, `img`, `photos`, `pictures`, `videos`, `video`, `uploads`, `static`, `public` et variantes de casse. Exclure `.git`, `node_modules`, `vendor`, builds, caches et secrets. Ne pas déplacer avant inventaire.
2. **INVENTORY** : pour chaque fichier → chemin original, MIME magic si possible, extension, taille octets, SHA-256, dimensions, codec/framerate/durée/fps/bitrate/rotation/alpha, EXIF sensibles, orientation, usage actuel, droit d'utilisation et source. Dédupliquer par contenu, jamais uniquement nom.
3. **CLASSIFY** : photo de produit, fond, portrait, logo, icône, visuel d'offre, motion, tutoriel ou hero ; examiner sujet, cadrage, contraste avec texte, tonalité, réutilisation abusive. Les suggestions d'emplacements sont des **propositions**, pas une vérité d'une analyse automatisée.
4. **RIGHTS GATE** : statut `verified | pending | forbidden`. Auteur, URL source/facture, licence exacte, limites, attribution, consentement de personne représentée et usages sensibles. Aucun fichier `pending/forbidden` en production ; ne jamais ôter un filigrane ou inventer une autorisation.
5. **GENERATE** (en dossier staging local, sans écraser originaux) : images responsive 480/768/1200/1600/2000 selon besoin, WebP ou AVIF + JPEG/PNG fallback lorsque utile, `srcset`, format sRGB, compression équilibrée, transparence et ratios préservés ; vidéos MP4 H.264 et WebM VP9/AV1 uniquement si appareils/ressources le justifient, poster JPEG/WebP à image représentative et non vide.
6. **MANIFEST** : source, dérivés, checksum, taille, dimensions, espace/position, alt dans les langues du PRD (profil strict : FR/EN/DE/IT), crédit, licence, `hero=true`, priority/lazy, pages utilisatrices, décision. Générer manifest dans **le projet** en JSON ou autre format approprié (le kit ne contient que Markdown).
7. **INTEGRATE** : chemins de médias publiables sous hébergeur local ; `<picture>`/`srcset`, `<video>` selon skill dédié, dimensions ou aspect-ratio pour éviter CLS, lazy hors première vue, priorité ciblée pour poster/LCP ; détection des références cassées.
8. **AUDIT** : comparer poids avant/après, screenshot visuel/crop, SSIM/VMAF si disponible pour vidéos (avec jugement humain), réseau navigateur, cache et 404 ; relever fichiers non référencés et proposer un plan de nettoyage **sans supprimer les originaux**.

## Dossiers recommandés du projet réel

```text
assets-source/              # fichiers originaux, privés, hors racine publique
assets-source/LICENSES.md   # provenance et droits réels
assets-staging/             # transformations et essais, ignoré Git si volumineux
public/media/              # dérivés réellement publiables et autorisés
scripts/media/             # scripts génération reproductibles
src/media-manifest.*       # machine-readable manifest généré dans projet
```

## Règles particulières

- Ne pas supposer que convertir tout en AVIF réduit toujours la taille ou accélère le décodage ; vérifier la photo type et les navigateurs cibles.
- SVG non fiable : inspecter scripts/attributs dangereux et assainir. Exif GPS/auteur : retirer des versions publiques sauf obligation d'attribution lisible séparément.
- GIF animé → vidéo courte ou WebP animé seulement si meilleure expérience et motion réduit.
- Auto-alt : décrire **la fonction** du visuel, sans inventer personne, marque, lieu ou droits. Un visuel purement décoratif utilise `alt=""`.
- Un ré-encodage successif dégrade la qualité : partir des originaux ; en leur absence, signaler la limite. Aucun re-encodage aveugle de centaines de fichiers.
- Les fichiers source volumineux ne doivent pas automatiquement être committés : définir stockage de travail **sur disque du projet/chez l'hébergeur** et processus de sauvegarde, pas de cloud externe permanent.

Voir aussi [`automatisation/PIPELINE-MEDIAS.md`](../automatisation/PIPELINE-MEDIAS.md), [`automatisation/TRANSFORMATIONS-MEDIAS.md`](../automatisation/TRANSFORMATIONS-MEDIAS.md) et [`skills/VIDEO-HERO.md`](VIDEO-HERO.md).
