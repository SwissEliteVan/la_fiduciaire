# PROMPT 16 — ACCESSIBILITÉ WCAG 2.2 AA DES PAGES ET WIDGETS

Effectue un audit ciblant WCAG 2.2 AA sur l'architecture réellement présente. N'affirme pas la conformité intégrale sur la seule base de tests automatiques. Tester au minimum HTML sémantique, navigation sans souris, Skip Link, menu, formulaires, popup, chat IA, bandeau cookies, CTA sticky, vidéos, blog et tunnel.

**Vérifications** : focus visible/non masqué, ordre logique, fermeture Escape et retour focus après modal, erreurs textuelles et relation labels, alt et titres descriptifs, contrastes, 200 % zoom, reflow 320 CSS px, cible tactile interne 44px si possible (WCAG AA 24px avec exceptions), `prefers-reduced-motion`, sous-titres/transcription si parole, `aria-live` non bavard, absence de blocage par widgets superposés.

**Livrer** : écarts `page:fichier:ligne` si code disponible, WCAG critère/impact, correction précise dans stack choisi, tests clavier/lecteur d'écran réellement effectués vs à effectuer, captures ou steps de reproduction, état des blocages avant mise en production.
