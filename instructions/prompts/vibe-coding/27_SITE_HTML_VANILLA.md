# PROMPT 27 — PROJET HTML/CSS/JS VANILLA (APRÈS CHOIX JUSTIFIÉ)

Le stack HTML/CSS/JS pur a été **retenu pour des raisons documentées**. Tu ne dois pas le transformer implicitement en projet React, Next.js, Vite, TypeScript ou Tailwind. Inspecte l'existant si présent. Livre un site statique complet, accessible et optimisé, en améliorant progressivement l'HTML natif avec JS léger.

**Arborescence conseillée, à adapter** : `index.html`, `pages/`, `blog/`, `assets/css/`, `assets/js/`, `assets/img/`, `assets/video/`, `robots.txt`, `sitemap.xml`, `README.md`, fichiers de règles/QA. Évite dix fichiers JS si deux suffisent ; pas de `node_modules` sans besoin réel.

**Fonctionnalités** : liens WhatsApp/téléphone réels, chat IA qui parle à une API backend/edge sécurisée, funnel accessible, formulaire avec action réelle, pop-up via `<dialog>`/JS natif si approprié, blog statique/SSG minimal seulement si besoin de volume, cookies/préférences avec blocage des scripts facultatifs, images responsive WebP/AVIF, CSS/WAAPI pour animations utiles.

**Sécurité** : ne jamais appeler directement un fournisseur LLM avec clé depuis le navigateur ; les opérations sensibles utilisent un service côté serveur ou fournisseur autorisé. Ni formulaire ni chat ne prétendent fonctionner si serveur absent.

**Livrer** : fichiers HTML/CSS/JS complets, éventuel endpoint dans dossier `functions/` SEULEMENT si nécessaire, guide de publication, tests au navigateur et matrice des modules, statuts de connexion honnêtes.
