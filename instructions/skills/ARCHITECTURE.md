# Skill — architecture indépendante de la mode

## Quand l'activer

Création from scratch, évolution importante, application à comptes, choix de stockage, refonte du rendu SSR/static. Ne pas réévaluer toute la stack pour un simple bouton.

## Procédure

1. Résumer PRD en 1 paragraphe, parcours prioritaires, charge attendue, exigences légales et données.
2. Auditer capacités de l'hébergeur réel. Définir plan statique + PHP pour vitrine versus serveur PHP/MySQL pour application, Node managé seulement si confirmé. Éviter toutes les infrastructures extérieures obligatoires.
3. Dessiner couche UI → domaine → accès données → MySQL/local filesystem ; tracer permissions aux frontières. Pour statique, écrire `sans backend applicatif` et ne pas inventer DB.
4. Comparer candidats via `architecture/CHOIX-STACK.md` et enregistrer ADR. Définir versions à verrouiller, upgrades planifiées, builds reproductibles et sauvegarde.
5. Prévoir dès le départ langues, locale dans route et metadata, hero vidéo, optimisation médias hors runtime, tests/CI et surveillance serveur.
6. Créer en tranches : fondations, parcours complet utilisable, durcissement, améliorations. IA B contrôle le coût, la simplicité et les migrations.

## Contraintes de qualité

- Ne pas installer shadcn, Filament ou Next.js si la fonction n'en tire aucun avantage concret.
- Pas de secret dans `NEXT_PUBLIC_*` ni équivalent front ; clé serveur uniquement dans environnement privé de l'hébergement.
- Interfaces sensibles testées avec permissions négatives, validation d'entrées, transactions DB si état cohérent requis.
- Prévoir publication atomique ou méthode rollback *compatible avec compte réel* ; ne pas déployer sans accord.

**Terminé quand :** ADR + diagramme et dépendances + risques + plan de test vérifiés ; revue critique B publiée.
