# Définition de terminé — preuve avant opinion

Une livraison est `DONE` seulement si les points applicables sont vérifiés, avec preuve, et toutes les exceptions expliquées.

- [ ] PRD et parcours d'acceptation réellement respectés (scénarios normal et erreur).
- [ ] Architecture et versions conformes à l'environnement d'exécution retenu *et vérifié* pour ce projet ; avec le profil `strict-local-web`, appliquer aussi ses critères (`profils/STRICT-LOCAL-WEB.md` : Hostinger/Infomaniak, aucun stockage ni auth externe obligatoire, hero vidéo locale).
- [ ] **BLOQUANT POUR TOUT ACCUEIL WEB, QUEL QUE SOIT PROFIL/PRD : vraie vidéo hero locale et autorisée, avec source fonctionnelle, poster, repli, bouton pause, `prefers-reduced-motion` et intégration mobile/desktop.** Si source/droits/validation manquent : `BLOCKED_MEDIA`, jamais DONE/APPROUVÉ/PRÊT À PUBLIER ; ne pas remplacer la vidéo par une image seule. Ne pas lancer de navigateur ou capture sans permission. Pour projet sans accueil web, mentionner le statut non applicable à l'interface actuelle sans supprimer la règle pour une future UI.
- [ ] Manifest média exhaustif, droits prouvés, originaux hors public, dérivés optimisés/QA, aucun fichier suspect publié.
- [ ] Langues définies réellement fonctionnelles et relues pour contenus critiques ; parité i18n, URLs traduites, `hreflang`, sitemap/canonical vérifiés.
- [ ] Accessibilité WCAG 2.2 AA visée et contrôles automatisés + manuels (clavier/focus/contraste/zoom/lecteur d'écran selon moyens) effectués ; limites documentées.
- [ ] SEO technique, responsive, images, Web Vitals mobile/desktop réellement mesurés et budgets assumés.
- [ ] Tests unités/intégration/E2E, lint, types/build adaptés à stack réussis et logs vérifiables ; tests non applicables justifiés.
- [ ] Permissions serveur, CSRF/validation, protections uploads/multitenant si besoin et scans de secrets/dépendances contrôlés.
- [ ] Sauvegarde/restauration et rollback concrets préparés, procédure de publication testable sur hébergeur, contraintes d'environnement revues.
- [ ] Si une IA B est choisie : relecture indépendante réellement reçue, défauts bloquants corrigés, exact SHA vérifié. Si B absente ou non choisie, ne pas inventer de validation.
- [ ] Documentation README/NOW/CONTEXT/ADR à jour, claim libéré, diff revu ; aucun travail hors périmètre.
- [ ] **Publication effective** séparément autorisée par propriétaire ; si non autorisée, statut final `PRÊT À PUBLIER`, jamais `DÉPLOYÉ`.

**Pas de « résultat parfait » garanti :** le niveau professionnel résulte de critères vérifiés, d'une QA indépendante et d'une recette en conditions réelles. Une check-box cochée sans preuve n'a aucune valeur.
