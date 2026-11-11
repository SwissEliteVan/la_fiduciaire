# PROMPT 15 — PERFORMANCE WEB QUELLE QUE SOIT LA TECHNOLOGIE

Détecte le stack **réel**. Audite la performance en vérifiant les mesures disponibles. Si le site n'est pas accessible ou outils indisponibles, **ne fabrique ni scores ni gains**. Cibles terrain : LCP ≤ 2,5 s, INP ≤ 200 ms, CLS ≤ 0,1 au 75e percentile. Indiquer mobile/desktop et métriques labo séparément.

**Ordre** : TTFB/hébergement → ressource LCP prioritaire → CSS/JS bloquants → images/video/fonts → tiers (analytics/chat/popups/cookies) → interactions → cache/DB → animations. Ne pas lazy-loader l'image LCP ; réserver espaces media pour CLS ; charger chat et pop-up au moment utile ; optimiser `transform`/`opacity` si animation.

**Contexte** : évaluer HTML pur comme baseline de simplicité ; React/Next seulement si retenu : consulter Vercel agent-skills conditionnellement. Mesurer le tunnel, pas seulement la page d'accueil, et tester avec bannière cookies et chat ouverts.

**Livrer** : tableau mesures avant/après avec outil, profil et date ou `non mesuré`, top causes, plan de correction par effort/impact, patchs ciblés, commandes de reproduction, limites. Ne pas sacrifier accessibilité ou conversion à un score Lighthouse.
