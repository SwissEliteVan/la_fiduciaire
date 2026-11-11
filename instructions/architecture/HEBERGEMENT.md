# Hébergement strict — Hostinger Business ou Infomaniak (profil facultatif)

> S'applique uniquement si le profil `strict-local-web` est activé (`projet/PROFIL.md`). Sinon, documenter l'hébergement choisi par le projet avec la même exigence de preuves sur l'environnement réel.

## Contrat

- Une seule plateforme choisie par projet : **Hostinger Business par défaut**, **Infomaniak si propriétaire l'impose**. Code et CI sur GitHub autorisés, **stockage applicatif, session, base, uploads, photos/vidéos et sauvegardes opérationnelles sur l'hébergement sélectionné**, avec copie hors ligne chiffrée si explicitement prévue. Pas de Supabase, Firebase, S3, Cloudinary, YouTube, Vimeo ni API serveur externe indispensable.
- Services tiers ponctuels non essentiels (ex. calendrier, anti-spam SaaS, analytics) ne sont **pas** intégrés par défaut et nécessitent arbitrage privacy/fonctionnel ; préférer composants auto-hébergés ou solutions natives simples.
- Vérifier **sur le compte réel** : version PHP/Node, politique de processus, DB et quota, CRON, SSH, stockage, sauvegardes, logs, ports, TLS, SPF/DKIM. Documentation fournisseur = indication, jamais preuve d'activation du compte.

## Matrice de vérification à remplir

| Fonction | Hostinger Business | Infomaniak Web | Preuve technique |
|---|---|---|---|
| Statique HTML/CSS/JS | documentation favorable | documentation favorable | URL de test + réponse HTTP |
| PHP / `mail()` | à tester dans compte | à tester dans compte | page de contrôle + boîte témoin |
| MySQL/MariaDB | à confirmer | à confirmer | compte/connexion + privilèges |
| Node managé/persistant | annoncé pour Hostinger Business ; à tester | produit Node/Cloud séparé selon offre, **ne pas supposer** | procédure produit, processus/restart |
| Cron/CLI/SSH | à confirmer | à confirmer | exécution de commande de test non sensible |
| Fichiers/rewrite `.htaccess` | à confirmer | à confirmer | 200/404/permalinks réels |
| Sauvegarde/restauration | à confirmer | à confirmer | exercice de restauration non destructif |

## Sources officielles consultées au 18.10.2026

- Hostinger Node (Business et Cloud) : https://www.hostinger.com/support/node-js-hosting-options-at-hostinger/
- Hostinger déploiement GitHub/Node : https://www.hostinger.com/support/how-to-deploy-a-nodejs-website-in-hostinger/
- Infomaniak offres Web, PHP/MySQL/SSH/Cron : https://www.infomaniak.com/fr/hebergement/hebergement-web
- Infomaniak caractéristiques selon formule : https://www.infomaniak.com/fr/hebergement/tarifs-et-caracteristiques
- Infomaniak Node.js : produit distinct à vérifier avant sélection, voir https://www.infomaniak.com/en/hosting/our-web-offers

**Mise en garde :** certains anciens articles Hostinger affirment l'inverse sur Node. Se fier à la page fournisseur la plus récente et à une validation sur le compte ; éviter les affirmations universelles.

## Choix d'architecture

- Site statique : build **local ou CI**, publier uniquement sortie statique et script PHP local si formulaire. Ne pas supposer qu'une API framework survit à l'export.
- App de gestion : PHP/MySQL recommandé si capacités connues ; sessions et fichiers hors dossier public ; migrations sauvegardées.
- Node managé : uniquement si compte confirme support, contraintes de stockage et modèle de build/runtime. Ne pas utiliser un runtime CI comme serveur de production.
- Upload : répertoire privé inaccessible par URL, whitelist de type MIME et taille, noms générés, antivirus si requis, contrôle d'accès serveur, pas d'exécution PHP à partir des uploads.
- Vidéos locales : chemin sous domaine propriétaire chez l'hébergeur retenu, réponses `Content-Type`, requêtes Range/206, TLS, cache et fallback testés.

## Procédure release : STOP obligatoire

1. Lint/types/tests/build + autorisations/licences + i18n + a11y + médias vérifiés.
2. Sauvegarde de l'existant et méthode de rollback **testées** ; diff de fichiers/DB préparé.
3. Préparer journal de release et plan de smoke-test, sans toucher à la production.
4. Obtenir **accord explicite du propriétaire pour ce déploiement précis**.
5. Déployer seulement via procédure validée ; vérifier HTTP/SEO/formulaires/hero/DB et restaurer sur nouvel accord si incident.
