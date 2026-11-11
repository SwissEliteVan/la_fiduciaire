# Checkpoints fréquents et reprise Git — configuration du lanceur

## Activation volontaire par projet

Dans la fenêtre du kit : choisir un projet, puis **Sauvegardes automatiques**.
- Les snapshots locaux sont proposés en premier, **désactivés par défaut**. Lorsqu'ils sont activés, le lanceur prend au plus une copie toutes les 5 minutes d'un ensemble de modifications Git visibles, lorsqu'il tourne. Aucun snapshot n'est un commit.
- Les commits automatiques sont **une option supplémentaire**, avec confirmation explicite du dépôt distant et de la branche. Ils s'exécutent après un tour IA et au repos environ toutes les 10 minutes ; jamais sous le verrou d'édition IA, et **jamais tant qu'un fichier a été enregistré depuis moins de 10 minutes** (édition humaine en cours dans VS Code) : le commit est alors reporté. Seuls les fichiers du tour IA qui vient d'être contrôlé échappent à ce délai.
- Aucun fichier dont le nom ou le contenu ressemble à un secret n'est copié dans les snapshots ; il est listé dans les exclusions du manifeste.
- Les push automatiques sont une **troisième autorisation** : jamais implicites. En cas d'échec, les commits restent locaux, et le SHA distant doit correspondre exactement à HEAD avant d'annoncer « envoyé ».
- Les suppressions ne sont **jamais autorisées automatiquement** dans la configuration utilisateur standard. Révision et confirmation via le bouton manuel existant.

Tout changement de dossier, branche ou remote invalide l'autorisation automatique. Aucun reset/clean/rebase/force-push/fusion automatique.

## Où retrouver les copies
Les copies et paramètres sont hors dépôt, sous LOCALAPPDATA/KitIADual/checkpoints/ sur Windows (ou ~/.kit-ia-dual/checkpoints/ ailleurs). Chaque projet dispose d'une clé propre. Dossier versions/copy-*/files/ et manifest.json contenant empreintes, chemins sauvés et exclusions. Au plus 24 versions conservées par projet : les plus anciennes sont retirées pour contrôler l'espace disque. Copier les archives nécessaires vers un stockage indépendant si une rétention longue est voulue.

**Limites :** les copies sont des sauvegardes de récupération « best effort » et non des snapshots atomiques globaux. Exclusions : liens, chemins sensibles (dont .env), médias de plus de 50 Mio et snapshot > 250 Mio. Les médias originaux nécessitent un plan de sauvegarde distinct. Le contrôle Git reste la source d'information des modifications ; les fichiers ignorés par .gitignore ne sont pas inventoriés. Fermer le lanceur interrompt le contrôle périodique, mais l'état et les fichiers présents restent persistants. Une session IA finit en un tour avec limite de durée de 20 minutes ; toute session réellement connectée doit encore être vérifiée sur le PC.

## Incidents et preuves
- Ne pas pousser si le dépôt distant a avancé ou si le SHA ne peut pas être vérifié : conserver HEAD et afficher l'incertitude.
- Les missions sont historisées en JSONL hors projet avec assistant, rôle, date, état et objectif, sans prétendre reproduire l'intégralité d'une conversation.
- Un même objectif déjà terminé ne peut pas être relancé en développement. Après deux échecs identiques, intervention humaine requise.
- La CI valide la logique sur des dépôts temporaires, pas les CLI du PC.
- Pour récupérer une version : choisir le dossier dans versions/, inspecter manifest.json et les empreintes ; restaurer manuellement les fichiers choisis après avoir préservé l'état actuel. Le kit ne fait aucune restauration automatique.

## Design et médias
Deux modèles préservant le travail existant sont fournis sous projet/REFONTE-DESIGN.md et projet/PLAN-MEDIAS.md ; le bouton **Plans design et médias** les copie dans le projet uniquement après accord, sans remplacer les fichiers déjà présents.
