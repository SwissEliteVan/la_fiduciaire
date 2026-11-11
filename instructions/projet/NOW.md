# NOW — une seule tâche active, contexte court

**TEMPLATE NON ACTIF :** ne jamais remplacer un NOW existant. Copier la structure et remplir à partir de la vraie issue avant de commencer.

```text
ID : [TASK-XXX]
Statut : OPEN | CLAIMED | TESTED | REVIEW_REQUESTED | BLOCKED | RELEASED
Objectif utilisateur (1 phrase) :
Résultat observable et test d'acceptation :
Pourquoi maintenant / priorité :
Département et fichiers réservés :
Agent auteur IA A/B :
GitHub issue CLAIM :
HEAD/base SHA :
Dépendance/accord propriétaire :
Tests prévus / réellement exécutés :
Blocage P0 :
Prochain petit pas unique :
```

**Règles :** ≤ 40 lignes de faits courants, pas de journal historique, pas de deuxième NOW. Les anciennes décisions passent à ADR ; les preuves restent dans commits/issues et une synthèse dans CONTEXT. Détenteur du CLAIM seul habilité à éditer NOW pendant la tâche.
