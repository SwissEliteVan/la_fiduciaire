# Skill — vidéo hero locale et accessible (OBLIGATOIRE sur tout accueil web)

**Invariant universel du propriétaire, indépendant du PRD et du profil :** la page d'accueil possède une zone hero prévue pour une vraie vidéo locale, avec **poster local** et stratégie de repli. **Aucune vidéo distante** (YouTube, Vimeo, stockage externe). Si la source est manquante/non licenciée : préparer la structure et un poster autorisé, marquer `BLOCKED_MEDIA` et REFUSER `DONE` ou publication ; le poster seul ne satisfait jamais la règle. Ne jamais fabriquer la preuve d'une vidéo réelle.

## Sélection de la séquence

- Sujet réel pertinent à la proposition de valeur, exposition/couleurs compatibles avec texte, cadrage mobile et desktop, sans démarrage choquant. Vérifier droit de synchroniser musique (préférer muet), droits de personnes, usage commercial et source.
- Réduire à la **séquence utile** (ex. 6–12 s si adaptée) ; pas de règle de durée imposée à la création. Toujours boucler proprement lorsque répétition pertinente ; pas de clip noir au premier affichage.
- Poster représentatif, optimisé et **présent même si vidéo ne se lance pas**. Dérivés localisés seulement si la vidéo contient des textes spécifiques à une langue ; sinon vidéo commune et sous-titres ou texte HTML traduits.

## Rendu HTML recommandé (adapter au framework)

```html
<section class="hero" aria-labelledby="hero-title">
  <video class="hero__video" autoplay muted playsinline loop preload="metadata"
         poster="/media/hero-poster.webp" aria-hidden="true">
    <source src="/media/hero.webm" type="video/webm">
    <source src="/media/hero.mp4" type="video/mp4">
  </video>
  <div class="hero__overlay" aria-hidden="true"></div>
  <div class="hero__content">
    <h1 id="hero-title">[Promesse traduite]</h1>
    <p>[Bénéfice traduit]</p>
    <a href="/[locale]/contact">[CTA traduit]</a>
    <button type="button" class="hero__pause" aria-label="Mettre la vidéo en pause">Pause</button>
  </div>
</section>
```

Le code est **un gabarit**, pas un composant fonctionnel complet : IA A doit intégrer état pause/reprise, détection d'erreur, focus accessible, locale, gestes et CSS. `aria-hidden=true` uniquement si la vidéo est décorative ; sinon fournir transcription/description et sous-titres quand pertinents.

## Motion réduit et réseau

- `prefers-reduced-motion: reduce` → ne **pas lancer** autoplay, afficher poster et fournir lecture volontaire avec contrôle ; respecter `saveData`/connexion limitée sans rompre le contenu. Une pause accessible doit fonctionner aussi sur tactile/clavier.
- Conserver texte/CTA dans HTML au-dessus d'overlay avec contraste mesuré ; définir aspect ratio, largeur/hauteur de zone, `object-fit` et focal point pour chaque breakpoint.
- Ne pas bloquer le rendu initial sur téléchargement vidéo ; poster et texte apparaissent instantanément, format optimisé, politique `preload` testée. Ne pas prétendre que `preload="metadata"` suffit à garantir un LCP rapide : mesurer.
- Vérifier serveur pour byte-ranges/HTTP 206, MIME `video/mp4` et `video/webm`, cache/versionnement, TLS, fallback webm→mp4, iOS Safari, Android Chrome.

## Encodage reproductible — exemples **à adapter et mesurer**

```bash
ffprobe -v error -show_entries format=duration,size:stream=codec_name,width,height,avg_frame_rate -of json assets-source/hero-original.mp4
# Générer MP4 H.264 compatibilité ; ces paramètres ne conviennent pas à tous les contenus
ffmpeg -i assets-source/hero-original.mp4 -an -vf 'scale=w=1280:h=-2:flags=lanczos' -c:v libx264 -crf 25 -preset slow -pix_fmt yuv420p -movflags +faststart assets-staging/hero.mp4
# VP9 optionnel pour comparaison, si compilé dans FFmpeg
ffmpeg -i assets-source/hero-original.mp4 -an -vf 'scale=w=1280:h=-2' -c:v libvpx-vp9 -crf 33 -b:v 0 assets-staging/hero.webm
# Poster à instant vérifié (par défaut ici 2s, modifier selon clip)
ffmpeg -ss 2 -i assets-source/hero-original.mp4 -frames:v 1 assets-staging/hero-poster.jpg
```

## Tests d'acceptation impératifs

| Scénario | Preuve |
|---|---|
| 390px mobile / 1440px desktop | vidéo/poster plein cadre, CTA lisible, aucun débordement |
| Sans mouvement | video non auto-lancée, poster et CTA disponibles |
| Autoplay bloqué | poster visible, page utilisable et lecture manuelle |
| WebM absent/incompatible | MP4 joue ou fallback poster fiable |
| Vidéo 404 ou réseau coupé | aucun écran vide/noir, pas de boucle d'erreur |
| Performance mobile | poids réel, LCP/INP/CLS, nombre de requêtes, coût CPU |
| Licence et hébergement | preuve source, `https://domaine/media/...`, pas de tiers |

**Pas de mise en ligne** si hero source/picture rights, comportement reduced-motion, lisibilité ou fallback sont invalides.
