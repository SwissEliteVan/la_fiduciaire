# Charte graphique — La Fiduciaire

**Direction : du dossier au dépôt.** Le site présente le cabinet et ses prestations. L’espace client permet de transmettre ses justificatifs et de savoir où ils en sont. L’identité doit être la même des deux côtés, avec un portail plus calme et plus fonctionnel que la vitrine.

## Fiche de dérivation (source de la charte)

| Élément | Constat extrait du service et du code |
|---|---|
| Service vendu | Comptabilité, TVA, salaires, pilotage, création d’entreprise, bouclement en Suisse romande (`app/content/fr.php`). |
| Public | Indépendants, TPE et PME ; B2B ; français ; maturité numérique moyenne (formulaire, PDF, pas de compte public). |
| Confiance attendue | **Réglementé et sensible** : données financières, secret professionnel, aucune promesse non validée. |
| Concurrence visuelle | Bleu marine générique, photos de bureau, sliders. À éviter : le décor et le carrousel. À assumer : la sobriété et la densité d’information. |
| Ton de voix | Précis, discret, direct. |
| Contrainte métier | Logo fourni non recolorable (bleu `#0F3D5A`), vidéo hero obligatoire, tarifs / zones / mentions `[À CONFIRMER]`. |

Conséquence : neutres froids, **un seul accent** (le bleu du logo) réservé aux actions et aux repères, titres structurés, registres plutôt que cartes promotionnelles, aucune animation décorative.

## Échelle et grille

- Espacement 8 px : `--sp-1` 4, `--sp-2` 8, `--sp-3` 12, `--sp-4` 16, `--sp-5` 24, `--sp-6` 32, `--sp-7` 40, `--sp-8` 48, `--sp-10` 64.
- Typographie base 16 px, ratio 1,25 : `--text-sm` 14, `--text-base` 16, `--text-md` 18, `--text-lg` 20, `--text-xl` 25, `--text-2xl` 31, `--text-3xl` 39, `--text-4xl` 49.
- Interligne 1,6 pour le texte courant, 1,12 pour les titres ; longueur de ligne bornée par `--measure` (68ch) et `--measure-tight` (56ch).
- Marges latérales de 16 px sur téléphone ; `--wrap` 1120 px au-delà de 60rem.

## États et focus

- Cinq états par contrôle : défaut, `:hover`, `:focus-visible`, `:active`, `[disabled]` / `aria-disabled` ; les champs ajoutent `[aria-invalid="true"]` (bordure 2 px + fond d’erreur + consigne).
- Focus : `outline: 3px solid var(--focus)` avec décalage de 3 px. Sur fond bleu, le repère passe en blanc (`--focus-dark`), car `#246494` sur `#0F3D5A` ne mesure que **1,81:1**.
- Cibles tactiles : 44 px pour les actions, plancher 24 × 24 pour la case à cocher (2.5.8).
- `prefers-reduced-motion` coupe la vidéo et les transitions ; `forced-colors` ajoute des bordures système aux panneaux, statuts et champs.

## Contraste mesuré (paires réellement utilisées)

| Paire | Ratio |
|---|---|
| `#435A69` sur `#F4F7FA` / `#FFFFFF` / `#EAF1F6` | 6,72 / 7,23 / 6,34 |
| `#0F3D5A` sur `#FFFFFF` / `#F4F7FA` | 11,44 / 10,64 |
| Blanc sur `#0F3D5A` (CTA, focus foncé) | 11,44 |
| Blanc 90 % et 86 % sur `#0F3D5A` (sous-titre, preuves) | 9,61 / 8,90 |
| Reçu / Traité / À remplacer / Erreur (texte sur fond d’état) | 8,54 / 7,60 / 7,45 / 7,30 |

## Signature

- **Logotype horizontal** : `assets/brand/la-fiduciaire.png` (512 × 149, fourni par le propriétaire). À privilégier dans l’en-tête et sur les écrans de connexion. Garder ses proportions et laisser au minimum 8 px autour dans l’interface. Largeur conseillée : 132 à 176 px.
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

Les valeurs applicables au site sont dans `assets/css/brand.css`. La vitrine les applique dans `assets/css/vitrine.css` : panneau d’accueil bleu LF face à la vidéo, appels à l’action inversés, filets verticaux et numéros de section, **registre de prestations** (une ligne par métier, filet de séparation, chiffres tabulaires) à la place de six cartes égales, et **signaux de confiance placés sous le bouton du hero**. Le devis reprend le même bandeau et la carte blanche. Référence d’accessibilité : [WCAG 2.2](https://www.w3.org/TR/WCAG22/). Le contraste des paires ne remplace pas la recette clavier, zoom, mobile et lecteur d’écran.
