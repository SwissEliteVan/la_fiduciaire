# Skill — design professionnel et conversion mesurable

## Procédure IA B et IA A

1. **Direction artistique** : contexte secteur, positionnement, profil visiteurs, références visuelles **sans copier le design d'un tiers**. Définir trois adjectifs design, ton de voix, personnalité, contraintes de marque et objectifs du hero.
2. **Tokens** : couleur primaire/neutres/états, espaces 4/8px, typographie 2 familles max, tailles fluides, rayons/ombres ; centralisation CSS et modes hover/focus/disabled/error.
3. **Architecture d'information** : 1 promesse claire + preuve + CTA principal visible, parcours offres, confiance, contact ; limiter la distraction autour de la vidéo hero.
4. **Composants** : boutons, liens, badges, formulaire, cartes, navigation, footer, sélecteur langue, modale, états systèmes ; documenter variantes, contraste et focus.
5. **Motion** : toujours fonctionnelle et intentionnelle. Limiter durée et fréquence ; `prefers-reduced-motion` coupe les effets non essentiels ; animations vidéo autonomes sans scrolling forcé.
6. **Recette** : tester 320/390/768/1024/1440/1920, zoom 200 %, clavier, lecteur d'écran lorsque disponible, paysage mobile, textes longs DE/IT, hero sur réseau lent.
7. **Conversion** : mesurer clic CTA, formulaire commencé/terminé (sans PII), rendez-vous lorsque mesure locale disponible ; ne pas affirmer causalité sans test.

## Rapport UX standard

```text
PAGE / viewport / langue / SHA :
Parcours et objectif :
Défauts P0-P2, capture ou reproduction :
Hypothèse UX et critère de succès :
Correctif minimal / composants concernés :
Coût JS/poids média estimé puis réellement mesuré :
Acceptation IA B :
```

**Règle :** la vidéo hero est toujours présente comme élément produit, mais l'expérience doit rester excellente lorsque sa lecture échoue ou que l'utilisateur préfère moins de mouvement.
