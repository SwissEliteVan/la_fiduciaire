# ADR — registre des décisions (La Fiduciaire)

Format : un ADR = un choix structurant justifié. Les scores ci-dessous sont des **hypothèses de jugement**, pas des mesures ; ils seront révisés si une preuve les contredit.

```text
ADR-001 — Stack de la vitrine et hébergement
Date / statut : 2026-11-10 / ACCEPTÉ par décision du propriétaire : **option A retenue** (vérification
  des capacités Hostinger reportée au moment de publier) — remplace le statut conditionnel initial.
Situation et critères : vitrine FR (puis EN/DE/IT) de fiduciaire, formulaire de devis à
  enregistrer en MySQL, hébergement imposé Hostinger Business mutualisé (PHP 8.3 + MySQL),
  application Laravel prévue dans un second temps. Pondération : `instructions/architecture/CHOIX-STACK.md`.
Solutions envisagées :
  A. PHP 8.3 natif + MySQL (front controller, PDO, templates PHP, .htaccess)
     Score : hébergement 5, sécurité 3, SEO/a11y/i18n 4, complexité 4, fonctionnalités 4, licences 5
     → Σ(score/5 × poids) = 0,82
  B. Laravel (version à verrouiller, PHP ≥ 8.2) + Blade + MySQL
     Score : hébergement 3, sécurité 5, SEO/a11y/i18n 4, complexité 3, fonctionnalités 5, licences 5
     → Σ(score/5 × poids) = 0,80
  C. WordPress — REJETÉ : hébergement 5, sécurité 2, SEO 4, complexité 3, fonctionnalités 3, licences 5
     → 0,72 ; dette de plugins, surface d'attaque, écart avec la phase Laravel.
Choix (stack, hébergeur, stockage, i18n, médias) : HÉBERGEUR = Hostinger Business mutualisé
  (décision propriétaire, profil `strict-local-web` non activé). STACK = **B si et seulement si**
  PHP 8.3 + Composer + SSH (ou déploiement équivalent) + cron + MySQL sont prouvés sur le compte ;
  **sinon A**. i18n : FR d'abord, structure prête pour EN/DE/IT. Aucun stockage ni service externe.
Compatibilité réelle et source vérifiée : NON VÉRIFIÉE à ce jour. Documentation fournisseur =
  indication, jamais preuve (`instructions/architecture/HEBERGEMENT.md`). Vérification = TASK-001.
Licences et données : composants open source à licence vérifiée lors de l'installation ; données
  du formulaire stockées dans la base de l'hébergeur, aucun envoi vers un tiers.
Conséquences et limites : choisir A évite toute dépendance runtime mais impose une seconde base
  de code lors du passage à Laravel ; choisir B unifie le socle mais alourdit un mutualisé et
  dépend de Composer/cron réels. Aucune migration de stack implicite ultérieure sans nouvel ADR.
Test d'invalidation / condition de relecture : compte sans SSH/Composer → A ; compte avec
  Composer + cron + PHP 8.3 vérifiés → B ; refus de PHP 8.3 → réouverture complète de l'ADR.
Owner et lien issue : propriétaire du projet ; aucune issue GitHub (pas de dépôt Git initialisé).
```

```text
ADR-002 — Opérations média autorisées sur la vidéo hero
Date / statut : 2026-11-10 / ACCEPTÉ (autorisation explicite, périmètre borné)
Situation et critères : la règle 15 d'`AGENTS.md` exige une vraie vidéo locale dans le hero et
  interdit toute analyse/transcodage sans accord spécifique.
Solutions envisagées : ne rien toucher (hero inexploitable techniquement) ; dériver un MP4 et un
  poster pour compatibilité et performance ; ajouter d'autres traitements (recadrage, sous-titres).
Choix : source `hero-video.webm` conservée intacte à la racine du projet ; seules opérations
  autorisées = (1) `ffprobe` (codec, durée, dimensions, piste audio), (2) génération d'un MP4
  H.264 à côté du WebM, (3) extraction d'un poster JPG local. Rien d'autre.
Compatibilité réelle et source vérifiée : fichier présent, 3 149 260 octets, conteneur EBML/WebM
  confirmé (signature `1a 45 df a3`) ; SHA-256 `9A90BDE6…741A627` avant et après installation.
Licences et données : média fourni par le propriétaire, qui en assume l'exploitation ; question
  close par décision du propriétaire — ce point n'est ni un risque ouvert ni un blocage.
Conséquences et limites : MP4 H.264 + poster améliorent la compatibilité Safari/iOS et le
  premier rendu ; poids final à mesurer ; aucun recadrage ni modification de la source.
Test d'invalidation / condition de relecture : toute opération média hors ces trois cas exige
  une nouvelle autorisation écrite.
```

```text
ADR-003 — Gouvernance Git du projet
Date / statut : 2026-11-10 / ACCEPTÉ (héritage d'`AGENTS.md`), EXÉCUTION EN ATTENTE
Situation et critères : politique du propriétaire = branche `main` exclusivement, aucune branche
  secondaire, aucun `git worktree`, aucune pull request, aucun force-push.
Solutions envisagées : initialiser immédiatement le dépôt du projet ; attendre un accord explicite.
Choix : le dépôt du projet n'est PAS encore initialisé. `git init -b main` dans
  `C:\Users\Administrateur\Local Sites\la_fiduciaire` requiert un accord distinct du propriétaire,
  ainsi que tout commit ou push ultérieur.
Conséquences et limites : sans dépôt, aucun SHA de référence et aucun historique de preuves ; les
  preuves restent des rapports de session. Aucun déploiement n'est concerné par cet ADR.
Test d'invalidation / condition de relecture : accord écrit d'initialisation, puis vérification
  `git branch --show-current` = `main` avant toute écriture suivie.
```

## ADR-004 — Portail documentaire sur le socle existant

- **Décision (2026-11-13).** Garder PHP natif + MySQL, étendre le contrôleur frontal et ajouter une migration additive. Aucun compte n’est créé par la migration. Le portail est désactivé par défaut.
- **Alternative.** Laravel apporte des briques de sécurité et d’administration, mais impose un changement de stack alors que Composer, la CLI et les possibilités du compte Hostinger ne sont pas prouvés. Un stockage SaaS est écarté pour ce MVP et nécessiterait une décision distincte.
- **Stockage et rôles.** Le fichier analysé est stocké hors racine web sous un nom généré. Le serveur contrôle le dossier à chaque lecture. Le cabinet est un rôle global à provisionner avec parcimonie. Les codes à usage unique sont remis manuellement par un canal privé, jamais dans une URL.
- **Condition d’ouverture.** Recette réelle, revue du rôle cabinet et de son authentification, limites/disponibilité du scanner, confidentialité et sauvegarde/restauration prouvées. Si l’hébergeur ne permet pas l’analyse requise et le stockage hors web, ne pas activer le portail et rouvrir le choix d’architecture.

Mise à jour ADR-003 : le dépôt GitHub et sa branche `main` existent. Le propriétaire a explicitement autorisé l’intégration directe sur `main` pour cette mission. Les règles d’absence de branche secondaire et de déploiement distinct restent applicables.

## ADR-005 — Devis sans SQL par défaut

- **Décision (2026-11-13).** Pour ouvrir rapidement le parcours vitrine, un devis validé devient un fichier JSON individuel et privé, écrit sous verrou avant confirmation. Pas de configuration SQL, de notification e-mail ni de compte requis pour un prospect.
- **Alternative.** SQLite introduit une extension à vérifier. MySQL reste un choix explicite et inchangé pour le portail. Aucun transfert ou effacement automatique des devis entre modes.
- **Exploitation.** Le processus PHP doit pouvoir écrire hors racine publique. Les fichiers sont lisibles par un opérateur en CLI ou dans le gestionnaire privé de l’hébergeur. Il faut sauvegarder et contrôler ce dossier avant ouverture réelle.
