# PROMPT 28 — BACKEND MINIMAL POUR FORMULAIRES, CHAT ET CRM

Pour le frontend choisi **[HTML PUR / CMS / AUTRE]**, détermine les besoins qui exigent réellement du traitement serveur : secret fournisseur chat, envoi sécurisé de formulaire, capture CRM, authentification ou paiement. **N'ajoute pas une application complète** si une fonction serverless/edge ou un service éprouvé suffit.

**Comparer** : (A) formulaire tiers approuvé et hébergement statique ; (B) petite fonction serverless/edge ; (C) serveur existant CMS/PHP ; (D) backend complet seulement si besoin avéré. Évaluer confidentialité, quota, latence, coût, portabilité, maintenance et limite fournisseur.

**API** : endpoints précis, schémas de requête/réponse, limites et validation des entrées, anti-spam/rate-limit, CORS et CSRF selon le cas, secrets serveur, journalisation minimisée, identifiants de corrélation, retries contrôlés, protections contre double insertion, politique de conservation, refus d'actions IA non autorisées.

**États frontend** : disponibilité, loading, succès confirmé, échec, possibilité de retenter, transfert humain ; ne jamais présenter les réponses mock comme des actions réelles.

**Livrables** : diagramme de flux, choix argumenté, code complet adapté à la plateforme choisie, `.env.example` sans secrets, budget de quota, tests d'échec et d'abus réellement joués ou marqués NON EXÉCUTÉS, procédure de restauration et de rotation des secrets.
