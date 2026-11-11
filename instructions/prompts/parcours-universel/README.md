# Prompts de projet — toutes IA (00 à 06)

Point d'entrée permanent du kit : Claude, Codex, Gemini, DeepSeek, Cline, Roo Code et tout autre assistant. Aucun fournisseur obligatoire.

Dans le lanceur Windows : **« Prompts de projet (toutes IA) »** → choisir une étape → **« Copier pour mon IA »** → coller dans le chat de l'assistant. Le bouton ajoute automatiquement le **00 — Cadre commun** à chaque étape copiée. Copier ne lance aucune IA, ne transmet aucun fichier et ne consomme aucun token.

| Étape | Fichier | Usage |
|---|---|---|
| 00 | [Cadre commun](00-CADRE-COMMUN.md) | Règles réutilisables pour chaque assistant |
| 01 | [Découvrir les Markdown](01-DECOUVERTE-MARKDOWN.md) | Première lecture des documents d'un projet existant |
| 02 | [Valider le cadrage](02-VALIDATION-CADRAGE.md) | Vérifier les décisions et approuver une première tâche |
| 03 | [Réaliser la tranche](03-REALISER-ETAPE.md) | Développer seulement le travail approuvé |
| 04 | [Relecture et tests](04-REVUE-TESTS.md) | Contrôler la réalisation, avec ou sans deuxième IA |
| 05 | [Reprise / passation](05-REPRISE-PASSATION.md) | Changer d'IA sans repartir de zéro |
| 06 | [Livraison et sauvegarde](06-LIVRAISON-SAUVEGARDE.md) | Vérifier, sauvegarder et transmettre l'état réel |

Parcours type : **01 → 02 → 03 → 04 → 06** ; utiliser 05 à toute interruption ou changement d'assistant. Ne pas envoyer toutes les étapes à la fois.

Ces fichiers sont dans le kit et copiés sous **instructions/prompts/parcours-universel/** lors de la préparation d'un **nouveau projet**. Pour un projet existant, le bouton lit les originaux dans le kit **sans modifier les instructions du projet**.

Sans IA, le kit et ses vérifications restent utilisables. Les extensions VS Code ne lisent pas toutes automatiquement des fichiers Markdown : coller le prompt copié dans le chat de l'extension choisie. La copie seule ne garantit pas que l'IA a accès au dossier : ouvrir le bon espace de travail dans VS Code.

Ne jamais inclure de secrets, réaliser de suppression, ni autoriser de publication sans vérification et confirmation.
