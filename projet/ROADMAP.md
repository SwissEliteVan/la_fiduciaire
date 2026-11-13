# Roadmap — La Fiduciaire (vitrine et portail)

**Statut :** rempli le 2026-11-10. Cette feuille **n'autorise aucun déploiement** ; la publication exige un accord distinct du propriétaire.

| Phase | Résultat livré | Critère de sortie | Dépendance |
|---|---|---|---|
| 0 — Cadrage | PRD, ROADMAP, BACKLOG, NOW, ADR stack | documents écrits et questions propriétaire listées | — (**fait**, sauf ADR à confirmer) |
| 1 — Stack et capacités | ADR-001 confirmé (A ou B) après contrôle du compte Hostinger | PHP 8.3, MySQL, SSH/Composer/cron, `.htaccess` prouvés sur le compte réel | accès compte propriétaire |
| 2 — Médias hero | `hero-video.webm` analysé, MP4 H.264 + poster JPG générés à côté | fichiers lisibles, poids raisonnable, checksums consignés | autorisation média bornée (**accordée**) |
| 3 — Squelette technique | structure publique/privée, config versionnée hors secrets, page d'accueil FR servie en local | page d'accueil s'affiche en local, aucun secret commité | phase 1 |
| 4 — Hero intégré | vidéo + poster + `prefers-reduced-motion` + bouton pause + repli | critère F001 validé en local, mobile et desktop | phases 2 et 3 |
| 5 — Contenu métier | prestations, tarifs, zones, mentions légales (FR) | F002/F003/F004/F006 relus par le propriétaire | réponses aux `[À CONFIRMER]` |
| 6 — Formulaire devis | enregistrement MySQL, validation serveur, anti-spam natif, messages d'erreur | F005 vérifié par test d'intégration | phase 3 + base MySQL de recette |
| 7 — Qualité | a11y, SEO technique, Web Vitals, tests automatisés ciblés | budgets et vérifications consignés | phases 4 à 6 |
| 8 — Préparation release | sauvegarde/rollback testables, journal de release | statut `PRÊT À PUBLIER` | phase 7 |
| 9 — Publication | mise en production | accord explicite du propriétaire, fichier par fichier | phase 8 |
| 10 — Portail MVP | Charte LF, accès sur invitation, dépôt et traitement, prompt de finalisation | recette HTTP locale sur données fictives | réalisé sur le socle PHP natif |
| 11 — Recette portail | Capacité réelle, restauration, recette ergonomique, scanner et textes validés | TASK-012 puis accord distinct d’ouverture | accès à l’hébergement de recette |
| Hors phase | Application de gestion Laravel, OCR et intégrations | — | décision ultérieure du propriétaire |

**Règles :** une seule tâche active dans `NOW.md` ; pas d'i18n EN/DE/IT avant que la vitrine FR soit stable ; aucun SaaS externe indispensable ; aucune capture QA sans autorisation.
