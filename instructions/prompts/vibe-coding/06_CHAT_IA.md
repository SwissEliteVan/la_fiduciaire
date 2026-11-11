# PROMPT 06 — CHAT IA SÉCURISÉ, UTILE ET MESURABLE

Intègre un **vrai** chat IA au site **[PROJET]**, sans imposer un framework. Le frontend peut être HTML/JS pur ; une API serveur/edge ou un fournisseur sécurisé traite les secrets. Inspecte les fonctionnalités et ressources existantes avant toute création.

**Conçois** : dialogue FAQ/qualification, transfert WhatsApp/humain, données de référence vérifiées, récupération et citations internes si pertinent, refus d'inventer prix/disponibilités, réessai/fallback, indicateur de chargement, historique limité, politique de rétention, coût par requête, quotas, consentement/notice, protection spam.

**Sécurité** : jamais de clé côté client ; contrôles d'accès et validations côté serveur ; pas d'actions CRM/paiement destructives exécutées sur la seule sortie du modèle ; documents tiers non fiables, limites outils, prévention injections, données de chat non divulguées à d'autres visiteurs ; rendu HTML/Markdown sécurisé.

**UI** : bouton visible mais discret, modal ou panneau accessible, focus/piège si modal, fermeture Escape, réduction de mouvement, usage tactile, repli quand offline, chargement différé. Empêcher collision avec cookies, CTA sticky et pop-ups.

**Livrer** : architecture front/back indépendante des vendors, contrat API, fichiers complets, variables `.env.example`, estimation prudente des coûts, 12 tests (faits connus/inconnus, injection, confidentialités, erreurs/quotas, mobile/clavier, consentement) et état `opérationnel / simulé en environnement local / bloqué`. Ne jamais appeler « connecté » un mock.
