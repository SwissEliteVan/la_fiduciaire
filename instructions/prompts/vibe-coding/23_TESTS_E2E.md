# PROMPT 23 — SCÉNARIOS DE TEST DU SITE COMPLET

À partir de la matrice fonctionnalités, crée et exécute si outils disponibles une suite de vérification de **parcours réels** adaptée au stack (navigateur manuel, Playwright, Cypress ou autre, sans installer par défaut). Ne simule pas une API manquante en déclarant l'intégration testée.

**Scénarios minimaux** : arrivée accueil mobile, CTA WhatsApp URL réelle, clic appel, ouverture chat et question connue/inconnue, transfert humain, formulaire avec erreurs puis succès et livraison vérifiée, pop-up fermeture/fréquence, navigation blog et article, recherche si disponible, bandeau accepter/refuser/personnaliser et réseau bloqué avant opt-in, médias responsive et poster, navigation clavier à 320px, redirection 404, incidents de réseau, sur-usage de l'API et sécurité basique.

**Livrables** : `docs/QA_REPORT.md` avec chaque scénario, priorité, préconditions, steps, attendu, observé, statut PASS/FAIL/NOT_RUN, URL/trace/capture si disponible, corrigeabilité et responsable. Aucun pourcentage de réussite ne doit inclure `NOT_RUN` comme PASS.
