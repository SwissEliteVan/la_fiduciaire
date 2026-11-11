# 04 — Relecture indépendante et contrôles

Cette étape peut être exécutée par la même IA, une autre IA ou un humain ; **la deuxième IA n'est pas obligatoire**.

- Consulte le rapport de développement, les fichiers réellement modifiés, le cadrage approuvé et les tests. Ne repars pas sur un audit complet.
- Vérifie les comportements attendus, cas limites, accessibilité et sécurité, ainsi que l'absence de perte de données ou de travail déjà fait.
- Exécute uniquement les contrôles disponibles et sûrs ; rapporte les résultats réels. Un test non lancé vaut **NON EXÉCUTÉ**, pas PASS.
- Distingue défaut prouvé, inquiétude théorique, choix produit et limite d'environnement. Si une correction est nécessaire, demande autorisation avant de changer le code.
- Ne confonds pas unit tests avec tests réels de navigateur, Docker, DPI, API ou facturation fournisseur.

Rends un verdict **APPROUVABLE / CORRECTIONS REQUISES / NON VÉRIFIABLE**, les preuves exactes, les réserves et la prochaine action. Aucun push ou déploiement sans accord.
