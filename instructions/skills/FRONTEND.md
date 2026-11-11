# Skill — frontend sobre, performant et accessible

## Fondations

- Mobile-first, HTML sémantique, composants réutilisables, CSS tokens, SSR/HTML statique si pages SEO ; hydrater uniquement interactions nécessaires.
- Grille responsive et typographie fluide, navigation clavier, focus visible, menus accessibles, contrôles tactiles adaptés, contrastes WCAG 2.2 AA.
- État chargement/erreur/vide/succès pour chaque parcours ; formulaires avec labels et erreurs liées au champ ; aucune interaction qui exige animation/souris.
- **Vidéo hero OBLIGATOIRE sur tout accueil web** (même sans profil strict, même si le PRD l'omet) : composant central de `skills/VIDEO-HERO.md`, réduction mouvement respectée, poster et bouton pause accessibles.
- Routes i18n de `skills/MULTILINGUE.md` ; jamais une interface moitié traduite.

## Sélection de composants

- Réutiliser le code existant prioritairement. Candidats MIT/ISC : Tailwind CSS, Lucide, shadcn/ui (si React), Motion (si animation justifiée).
- React Bits peut être gratuit pour l'usage commercial mais sa licence **MIT + Commons Clause** restreint la redistribution des composants ; vérifier obligations et modèle économique avant usage. Ne pas le présenter comme MIT pur.
- 3D/Lenis/GSAP uniquement si effet testé utile : fallback statique, reduced-motion, pas d'interruption du scroll natif ni de surcharge mobile.

## Contrôle IA B

Parcours clavier complet à 320/390/768/1280/1920 px, zoom 200 %, longs textes allemands, préférences de mouvement, vérification tactile, overlay vidéo hero, menus ouverts et erreurs formulaires. Screenshots comparés avec baseline ; aucune approbation fondée seulement sur un screenshot desktop.

**Réussite :** pas de débordement horizontal, pas de contrôles inaccessibles, principaux CTA visibles, LCP/INP/CLS mesurés et toute exception documentée.
