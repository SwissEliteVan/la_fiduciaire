# Profil facultatif `strict-local-web`

**Ce profil ne s'applique jamais par défaut.** Il s'applique uniquement au projet
dont le fichier `projet/PROFIL.md` contient la ligne `profil: strict-local-web`,
créée sur demande explicite du propriétaire (bouton « Profil web strict » du
lanceur, ou fichier écrit par lui). Sans ce fichier, le kit reste universel :
aucun hébergeur, technologie, langue, média ni fournisseur n'est imposé.

Une fois activé, il complète les règles du projet sans remplacer une décision
déjà acceptée dans ses ADR ; un conflit est signalé au propriétaire.

## Règles du profil

1. Production chez **Hostinger Business** (défaut du profil), ou **Infomaniak** si
   le projet choisit cet hébergeur. Toutes les données, photos, documents et vidéos
   diffusés sont stockés localement chez l'hébergeur retenu. Les outils de build
   locaux ou GitHub ne sont pas des hébergeurs de données métier.
2. **Jamais** Supabase, Firebase, stockage cloud tiers, auth tiers indispensable,
   API SaaS obligatoire pour le fonctionnement central, Vercel/Netlify comme
   plateforme applicative, vidéo tierce comme seule source.
3. La stack est choisie selon le PRD et les capacités **vérifiées sur le compte**
   d'hébergement, avec ADR et alternatives (voir
   [`architecture/HEBERGEMENT.md`](../architecture/HEBERGEMENT.md) et
   [`architecture/CHOIX-STACK.md`](../architecture/CHOIX-STACK.md)).
4. Sites : **vidéo hero locale obligatoire**, poster/fallback, bouton pause,
   accessibilité et `prefers-reduced-motion`
   ([`skills/VIDEO-HERO.md`](../skills/VIDEO-HERO.md)).
5. Multilingue conçu dès le départ ; langues par défaut du profil : **FR/EN/DE/IT**
   sauf décision contraire du PRD ([`skills/MULTILINGUE.md`](../skills/MULTILINGUE.md)).

## Critères supplémentaires de « terminé »

- Architecture et versions conformes à Hostinger Business/Infomaniak *dans le
  compte*, sans stockage ni auth externe obligatoire, sans Supabase/Firebase.
- Hero vidéo locale autorisée sur l'accueil, poster performant, repli
  réseau/autoplay bloqué, bouton pause, `prefers-reduced-motion`, mobile et
  desktop testés.
- Procédure de publication testée sur l'hébergeur retenu ; aucun transfert sans
  accord spécifique.
