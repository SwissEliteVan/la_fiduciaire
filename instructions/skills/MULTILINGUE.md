# Skill — internationalisation native et SEO localisé

## Architecture dès le début

- Locales suisses suggérées : `fr`, `en`, `de`, `it` ; dans chaque projet, choisir locales utiles et prévoir ajout sans refonte. Langue initiale par défaut de l'entreprise explicitement définie ; par défaut `fr` pour cible romande.
- Routes claires `/{locale}/...` avec `fr`, `en`, `de`, `it` et slugs localisés via table de routage ; gestion canonique de `/` (redirect **si support** ou landing avec choix) ; pas de duplicate content.
- Fichiers messages centralisés (`locales/fr.json`, `en.json`, etc. dans projet réel), structurés par namespaces `common`, `home`, `offers`, `forms`, `errors`, `legal` ; clés stables, interpolations typées, jamais concaténer 3 chaînes traduites dans un ordre figé.
- Localisation des nombres, dates, devises CHF et formats légaux via `Intl` ou équivalent serveur ; pas de machine translation automatique de clauses légales publiées sans relecture qualifiée.

## Négociation de langue

1. URL explicite prime ; sélecteur langue préserve autant que possible la page correspondante.
2. Préférence utilisateur persistée seulement si nécessaire et avec transparence appropriée ; navigateur `Accept-Language` suggestion, jamais forcer le changement malgré choix manuel.
3. Clé absente → fallback contrôlé (et signalé **en test**) ; pas de clé affichée en production. Éviter d'envoyer des chaînes à une API traduction externe.
4. Traductions des erreurs serveur, 404, e-mails, placeholders, aria-label, title/description, alt rédigés correctement par locale.

## SEO

- `html lang`, title/meta description propres à locale, `canonical`, `hreflang` bidirectionnels et `x-default` seulement quand pertinent, sitemap localisé ; pas de `hreflang` vers une page non publiée.
- Contenu utile pour chaque marché, éviter duplication automatique de villes/langues sans pertinence ; scripts/JSON-LD localisés et contacts cohérents.

## Contrôles automatisés

- Même ensemble de clés dans toutes les locales (ou exceptions temporaires listées) ; placeholders/interpolations identiques ; ressources JSON/YAML valides ; détection HTML dangereux ; **aucune chaîne non traduite dans les UI publiques**.
- Naviguer parcours critique dans toutes les locales ; liens localisés, sélecteur, formulaire et erreurs, `hreflang`, 404, compression et débordement typographique (DE long), layouts RTL futur si extension prévue.
- Test de traductions sensibles par relecteur compétent ; verdict linguistique distinct du passage technique.

## DoD

Un nouveau langage s'ajoute par ressources et routes déclaratives, sans duplication de la logique métier. Les pages et textes importants sont relus, tests locale passés ; la nouvelle langue n'est publiée qu'une fois le contenu prêt.
