# Charte graphique — La Fiduciaire

**Direction : du dossier au dépôt.** Le site présente le cabinet et ses prestations. L’espace client permet de transmettre ses justificatifs et de savoir où ils en sont. L’identité doit être la même des deux côtés, avec un portail plus calme et plus fonctionnel que la vitrine.

## Signature

- **Logotype horizontal** : `assets/brand/la-fiduciaire.png` (512 × 149, fourni par le propriétaire). À privilégier dans l’en-tête et sur les écrans de connexion. Garder ses proportions et laisser au minimum 8 px autour dans l’interface. Largeur conseillée : 124 à 166 px.
- **Monogramme LF** : `assets/brand/apple-touch-icon.png` (180 × 180, fourni). Réservé au favicon, à l’icône iOS et aux contextes compacts. Ne pas étirer, recolorer ni redessiner ces fichiers.
- **Motif de marque** : le trait vertical du logotype inspire les séparations de rubriques et les repères de lecture, sans devenir un code d’état.

## Palette et usages

| Rôle | Couleur | Usage |
|---|---|---|
| Bleu fiduciaire | `#0F3D5A` | Bleu extrait des deux logos. Bouton principal, liens, chiffres clés et repères |
| Bleu profond | `#0B2B40` | Survol principal, hero et zones sombres |
| Encre | `#132B3B` | Titres et texte courant |
| Encre secondaire | `#435A69` | Aide, métadonnées et légendes |
| Papier bleuté | `#F4F7FA` | Fond général et vide du portail |
| Blanc | `#FFFFFF` | Cartes, formulaires et texte sur bleu |
| Filet décoratif / contrôle | `#D5E1E8` / `#688495` | Séparateurs passifs / limites des champs et de la zone de dépôt |
| Focus | `#246494` + blanc | Double repère clavier, lisible sur clair et sombre |
| Reçu | `#16486D` sur `#E8F3FA` | Pièce déposée et enregistrée, pas encore traitée |
| Traité | `#17563F` sur `#E7F4EC` | Traitement marqué par le cabinet |
| À remplacer | `#7B3E16` sur `#FFF1E2` | Le cabinet demande une nouvelle pièce |
| Erreur | `#8C2C29` sur `#FBECEB` | Échec ou champ invalide avec une consigne claire |

Ne jamais distinguer deux états par leur couleur seule. Utiliser un libellé en toutes lettres et, si utile, un pictogramme. Les fonds froids laissent la priorité aux documents plutôt qu’à la décoration.

## Typographie, grille et composants

- Police système locale, sans téléchargement. Titres en graisse 700, texte courant en 16 px minimum, interligne autour de 1,6. Petits textes de 14 px pour les métadonnées, chiffres tabulaires pour les périodes et tailles.
- Grille d’espacement de 4/8 px. Marges latérales de 16 px sur téléphone. Rayons de 10 px pour champs et boutons, 14 px pour cartes. Contrôles principaux d’au moins 44 px de haut.
- **Vitrine** : la vidéo locale existante reste dans le hero avec son poster, son bouton pause et la réduction de mouvement. Action d’acquisition « Demander un devis » et accès distinct « Espace client ».
- **Portail** : une action dominante « Déposer les pièces ». Montrer le dossier et la période avant l’action. Afficher formats, taille et nombre autorisés avant la sélection. Le sélecteur natif reste utilisable sans glisser-déposer ni JavaScript.
- **Suivi** : liste lisible sur mobile, une ligne ou carte par pièce. Afficher nom, période, catégorie et statut, puis l’action de téléchargement. Les erreurs disent quoi corriger. N’afficher « Reçu » qu’après enregistrement réel.
- **Voix** : vouvoiement, verbes concrets et phrases brèves. Préférer « Déposer mes pièces » à « Gérer mes assets », et « À remplacer » à « Rejeté ».

Les valeurs applicables au site sont dans `assets/css/brand.css`. Référence d’accessibilité : [WCAG 2.2](https://www.w3.org/TR/WCAG22/). Le contraste des paires ne remplace pas la recette clavier, zoom, mobile et lecteur d’écran.
