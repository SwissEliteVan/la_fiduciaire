# ADR — registre des décisions

Un ADR = choix structurant justifié, pas note de séance. Les décisions existantes d'un dépôt priment sur les exemples ci-dessous.

```text
ADR-XXX — [titre]
Date / statut : accepté | conditionnel | remplacé
Situation et critères :
Solutions envisagées :
Choix (stack, hébergeur, stockage, i18n, médias) :
Compatibilité réelle et source vérifiée :
Licences et données :
Conséquences et limites :
Test d'invalidation / condition de relecture :
Owner et lien issue :
```

Décisions permanentes par défaut du kit : aucun assistant IA, hébergeur, technologie ni fournisseur imposé ; accord avant production ; toute nouvelle décision de stack doit démontrer sa compatibilité réelle. Si le profil `strict-local-web` est activé (`projet/PROFIL.md`) : pas de Supabase/Firebase ni de dépendance applicative externe indispensable, médias et données chez Hostinger Business ou Infomaniak, hero vidéo local, i18n native.
