# Modèle de données et isolation — seulement si l'application l'exige

Site statique sans espace utilisateur : indiquer **« pas de base de données nécessaire »** au lieu d'inventer une DB. Pour une application avec données :

1. Entités et propriétaire ; champs minimum ; données personnelles et délais de conservation ; index ; contraintes uniques ; suppression/archives.
2. Auth locale : comptes, réinitialisation, MFA adapté aux risques, session HTTP-only/Secure/SameSite, gestion du CSRF et limitation de tentative.
3. Isolation entreprise/organisation : contrôles de permissions **sur chaque requête serveur** ; refuser un identifiant appartenant à un autre tenant, tester le contournement par changement d'URL, pagination et export.
4. Fichiers : métadonnées en DB, contenu sur disque privé du même hébergeur, accès via contrôleur autorisé ; jamais `public/uploads` pour données confidentielles.
5. Migration : versionnement, sauvegarde DB, plan de retour arrière, durée d'indisponibilité, accord explicite si destructive.

| Entité | Finalité | Champs | Qui peut lire/écrire | Rétention | Tests |
|---|---|---|---|---|---|
| [nom] | [but] | [liste] | [permissions] | [durée] | [cas croisé] |

Interdit : ajouter Supabase ou Auth0/Firebase pour gagner du temps. Une DB MySQL/MariaDB du même hébergeur est envisageable si besoin démontré et capacité confirmée.
