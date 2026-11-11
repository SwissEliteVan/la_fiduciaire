# Prompting ciblé — IA A et IA B (chargement à la demande)

Source : [VibeAcademy — huit techniques](https://vibeacademy.eu/blog/vibe-coding-prompt-techniques).
S'en servir comme **méthode**, sans imposer une stack ou recopier les exemples.
Chaque appel vise une seule décision ou réalisation observable.

| Technique | Quand et comment l'utiliser |
|---|---|
| **Context Priming** | Début : stack réelle, conventions, cible, fichiers, SHA ; pas tout le dépôt |
| **Stepwise** | Travail complexe : cadrer → modifier → tester → revoir, étapes courtes |
| **Constraint Stacking** | Seulement contraintes applicables : métier, techno, UX, sécurité, coût |
| **Debug** | Erreur verbatim, déclencheur, avant/après, attendu/observé, reproduction |
| **Refactor** | Invariants et tests de non-régression ; pas de migration gratuite |
| **Multi-File** | Lister fichiers liés et responsabilités ; pas d'édition hors périmètre |
| **Context Layering** | Charger règles → architecture → ADR utile → tâche → code → preuves |
| **Review** | B examine diff/SHA : sécurité, bugs, performance, accessibilité, design |

## Squelette opérationnel (adapter, ne pas recopier mécaniquement)

```text
OBJECTIF : [une tâche et critère observable]
CONTEXTE : [stack existante, 1–3 fichiers, SHA]
CONTRAINTES : [règles applicables, vidéo hero + FR/EN/DE/IT si profil web]
EXÉCUTION : [modification minimale, commande(s) test autorisées]
LIVRAISON : [diff/SHA, PASS/FAIL/NOT_RUN/BLOCKED, avis IA B]
```

En cas d'échec, réinjecter seulement l'erreur, l'état attendu/réel et le
contexte supplémentaire nécessaire. Pour un audit, trois défauts principaux
maximum avec gravité, preuve et correction. Éviter les justifications répétées
et les documents créés uniquement pour consommer du contexte.
