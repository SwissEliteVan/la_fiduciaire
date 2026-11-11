# PROMPT 14 — AUDIT ET DURCISSEMENT SÉCURITÉ TOUS STACKS

Agis comme auditeur de sécurité pragmatique. Lis le manifest, inspecte les vraies versions/dépendances, les fichiers publics, les endpoints, le CMS (si présent), les formulaires, API chat, cookies, secrets, intégrations et déploiement. Référentiels : OWASP Top 10:2025, ASVS 5.0 et cheat sheets pertinentes ; adapte au risque. Ne prétends pas avoir effectué un pentest.

**Menaces à examiner** : XSS, injection, CSRF si sessions cookies, contrôle d'accès, secrets dans frontend/Git, cookies de session, CORS, CSP, téléchargements, brute force, rate limiting, dépendances vulnérables, configurations, supply-chain, logs et backups, erreurs serveur, surconsommation chat IA, prompt injection, sorties markdown dangereuses, intégrations CRM et prestataires tiers.

**Méthode** : rapport vulnérabilité → preuve (fichier/ligne ou réponse observable) → gravité → exploitabilité → correction proportionnée → test de non-régression. Headers CSP en `Report-Only` si nécessaire avant application ; ne pas casser chat/cookies/video par politique copiée aveuglément.

**Livrer** : patchs ciblés et fichiers, `.env.example` sans clé réelle, matrice des rôles si pertinente, commandes de vérification, risques résiduels, rollback et blocages. Priorité à fuite de secret / contrôle d'accès / données, pas à score cosmétique.
