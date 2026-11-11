# PROMPT 18 — AUDIT COMPLET AVANT PRODUCTION

Audite le dépôt **réel** selon `prompts/vibe-coding/VIBE_MANIFEST.md` (ou le manifeste fourni avec ce prompt). Commence par inventorier version, stack et fichiers, puis exécute les tests disponibles. Utilise une **matrice 18 modules** avec statut `absent / prévu / implémenté / vérifié / non applicable justifié / bloqué`. Ne valide pas une maquette pour une intégration fonctionnelle.

**Périmètre** : architecture et dépendances, mobile et design, WhatsApp, téléphone, chat IA backend réel, tunnel/formulaires/CRM, pop-up commercial, blog/publication, cookies & scripts bloqués avant consentement, SEO, média hero/images/videos, animation, Core Web Vitals, WCAG AA, OWASP, anti-hallucination, observabilité, déploiement.

**Rendu** : synthèse exécutable, score par domaine **seulement avec critères de mesure explicites**, incidents P0/P1/P2, `fichier:ligne` ou URL, reproduction, preuve et sévérité, correction minimale, risque d'effet de bord, responsable, action recommandée. Distinguer mesures et hypothèses ; tests NON EXÉCUTÉS comptent comme inconnus, pas comme réussis.

**Critères bloquants** : secrets divulgués, fausse conversion, formulaire non distribué, chat mock présenté comme réel, traceurs illégitimes chargés trop tôt, modales inaccessibles, fausses offres, déploiement irréversible. Fournis un prompt concret de correction pour chaque P0.
