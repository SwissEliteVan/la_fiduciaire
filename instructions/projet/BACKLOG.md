# Backlog unique — priorisation mécanique

**Statut :** TEMPLATE. Les tickets productifs sont décrits ici ou dans le backlog officiel **déjà existant** du dépôt : ne pas garder deux backlogs.

## Ordonnancement

- Statuts : `IDEA`, `READY`, `CLAIMED`, `BLOCKED`, `DONE` ; `CLAIMED` ne remplace pas le commentaire CLAIM dans l'issue.
- Triage P0→P3 ; parmi READY, classement `priorité = impact utilisateur × confiance / (effort × risque)` sur échelle 1–5 (heuristique, non mesure économique réelle).
- Une tâche = un résultat visible, fichiers estimés, prérequis, test, propriétaire de l'écriture et taille S/M/L. Pas de tâche « refaire tout le site ».

| ID | P | Résultat attendu | Dépendances | Test/critère | Effort | Statut |
|---|---|---|---|---|---|---|
| TASK-001 | P0 | Contrôler droits source vidéo hero | preuve propriétaire | licence consignée | S | IDEA |
| TASK-002 | P1 | Vérifier la stack hébergement | TASK-001 non requise | ADR avec preuves fournisseur | S | IDEA |
| TASK-003 | P1 | Pipeline médias et poster | vidéo autorisée | QA + checksums | M | IDEA |

**Avant exécution, remplacer tous les exemples par les faits du projet.** Une tâche sensible autorisée explicitement est identifiée par lien à l'accord propriétaire.
