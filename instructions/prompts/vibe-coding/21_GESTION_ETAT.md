# PROMPT 21 — ÉTAT, CACHE, SESSIONS ET FORMULAIRES

Inspecte le stack actuel et la fonctionnalité : **[FONCTION]**. Identifie chaque état et sa source de vérité : navigateur local, URL, serveur, session, cache, API, CRM, cookie consentement. **Si HTML/JS pur suffit : ne propose aucun gestionnaire d'état React.** Un choix technique ne doit pas être fait avant l'inventaire.

**Livrer** : `docs/STATE_MAP.md` avec état, propriétaire, cycle de vie, persistance, invalidation, données sensibles, transitions loading/empty/error/success/offline, accès clavier aux retours. Définir gestion des courses, double-submit et retries. Cache privé isolé. État de consentement cohérent sur toutes pages ; pas de partage de conversations chat entre visiteurs.

Implémente la plus petite solution correcte. Tests : reload/back, deux onglets si pertinent, connexion lente, réponse hors ordre, erreurs serveur, refus cookies, formulaires et route différente. Déclare non testés les cas que tu n'as pas exécutés.
