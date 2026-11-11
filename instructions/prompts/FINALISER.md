# Prompt — finaliser et préparer la publication

Ne pas commencer par annoncer « terminé ». Lire DoD, derniers rapports IA B, tests CI, manifeste médias, i18n et hébergement.

1. Identifier tout P0/P1 non résolu et corrections autorisées ; si possible les exécuter puis tester.
2. Exécuter/revoir lint, types, build, tests unit/integration/E2E, mesures vidéo hero, accessibilité, SEO et traductions de toutes les locales requises.
3. Comparer les médias publiables aux preuves source/licence et aux vrais chemins sur hébergeur ; source originaux préservés hors `public`.
4. Vérifier possibilité de sauvegarde et rollback + procédure de déploiement propre à l'hébergement retenu (Hostinger Business/Infomaniak si profil `strict-local-web`) ; ne pas démarrer de transfert.
5. Obtenir/revoir la REVIEW B sur SHA exact, corriger ses P0/P1, consigner réserves.
6. Mettre à jour NOW/CONTEXT/DECISIONS seulement si nécessaire ; RELEASE et rapport `PRÊT À PUBLIER` avec critère, preuves et demande d'autorisation **explicite**. Si la mise en ligne est déjà autorisée pour cette version et réellement exécutée, contrôler l'URL et dire `DÉPLOYÉ` seulement avec preuve.

Rapport final concis : état de chaque critère DoD, bloqueurs, revue, tests observés, site cible, méthode restauration, statut publication, prochaine action unique.
