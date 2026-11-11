# PROMPT 04 — IMPLÉMENTATION MULTI-FICHIERS SANS CASSE

Mission : **[PAGE/FONCTIONNALITÉ]**. Tu as le stack validé dans `docs/DECISIONS.md`. Lis `prompts/vibe-coding/VIBE_MANIFEST.md` (ou le manifeste fourni avec ce prompt), inspecte l'existant, exécute ou précise les commandes de découverte (`ls`, manifest dépendances, routes, tests) avant de proposer des modifications.

**Contrat** : cartographie des fichiers créés/modifiés/supprimés, objectifs, imports, interfaces, secrets nécessaires, données d'entrée, états (vide, loading, succès, échec), cas responsive/clavier. Écris un code complet adapté au stack : HTML/CSS/JS si HTML choisi, PHP si PHP choisi, composants si framework choisi. Pas de traduction automatique en React.

Préserve comportements existants, lien WhatsApp, téléphone, chat, pop-up, cookies, blog et funnel. Vérifie absence de chevauchement CTA/cookie/pop-up sur mobile et absence de script tiers sans consentement requis.

**Rendu** : diff clair ou fichiers finaux complets, commandes de test réellement exécutées et sorties essentielles ; si tu ne peux exécuter, liste le test requis sous `NON EXÉCUTÉ`. Propose rollback ciblé. Pas de refactorisation hors demande.

## Exigence pour une vitrine premium animée

Si le mandat porte sur une refonte de site d'agence, un portfolio ou une vitrine premium, **construire une page complète et vivante**, pas seulement les tokens ou le hero. Réutiliser les composants animés maintenus du projet ; sinon sélectionner des presets professionnels sous licence compatible, avec démonstrations vérifiées, puis **les installer/intégrer dans le lot autorisé**. Les effets complexes inventés de zéro et les audits répétés sans nouveau fait sont à éviter. Tester l'interactivité réelle, les pages intérieures, le mobile et le mode mouvement réduit. Un navigateur externe inaccessible ne bloque pas les tests locaux accessibles.

Git : **uniquement `main`**, sans branche secondaire ni PR. Respecter le travail parallèle, comparer `HEAD` / `origin/main`, tester et contrôler le diff avant commit et push fast-forward si expressément autorisés. Aucun déploiement implicite.
