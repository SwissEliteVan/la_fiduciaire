# Prompt 02 — Audit sécurité et sauvegardes

Examiner l'état Git réel, droits d'accès, scripts d'exécution, verrous IA, secrets, prompts externes, politiques de commit/push, sauvegardes et restauration.

Priorité P0 : vérifier **les blobs présents dans l'index Git** avant commit, pas seulement leur version sur disque. Reproduire le cas d'un fichier staged sensible dont la version disque est saine, puis corriger et tester. Contrôler symlinks, suppressions, fichiers ignorés et SHA distant.

Auditer ensuite autorisations, isolation des agents, limites de temps/coût, logs et reprise après interruption. Aucune action destructive ni publication.

Sortie : risques P0/P1/P2 avec fichier/ligne et reproduction, correctif minimal par tranche, tests [PASS/FAIL/NOT_RUN/BLOCKED], preuve et prochain pas.
