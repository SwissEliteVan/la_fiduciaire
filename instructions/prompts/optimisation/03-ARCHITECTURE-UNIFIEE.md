# Prompt 03 — Architecture unifiée

Cartographier les responsabilités de lanceur/ et travail-universel/runtime/ : interfaces, CLIs, adaptateurs API, contrôles Git, état des tâches, prompts, preuves, sauvegardes.

Distinguer les doublons des différences intentionnelles (CLI locale vs dispatcher/API). Proposer un contrat versionné : project_id, request_id, rôle, tâche, permissions, budget, statut, sortie, SHA et preuves. Unifier progressivement les points communs par adaptateurs, sans réécriture globale ni migration de stack forcée.

Livrables : ADR et matrice de responsabilités, prototype d'un seul flux, tests de compatibilité, possibilité de rollback. Ne supprimer aucun mécanisme existant avant preuve.
