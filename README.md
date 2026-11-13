# La Fiduciaire

Site en français pour les indépendants et les PME de Suisse romande. Le public consulte les prestations et peut demander un devis. Les clients invités déposent leurs justificatifs comptables. Le cabinet consulte les demandes, invite les clients et suit leurs pièces.

**Pour démarrer sur ton PC, il suffit de PHP.** Pas de base de données à créer, pas de dépendances à installer dans le projet, pas de compilation. Le portail est fermé par défaut. Tu peux l’ouvrir en local avec la configuration de recette ci-dessous.

## 1. Voir le site sur ton PC

Dans PowerShell, ouvre le dossier du projet, celui qui contient `index.php`, puis lance ces commandes. Le projet cible PHP 8.3 et a aussi été testé sous PHP 8.5 sur Windows.

```powershell
php -v
php -S 127.0.0.1:8123 index.php
```

Ouvre [l’accueil](http://127.0.0.1:8123/) ou [le formulaire de devis](http://127.0.0.1:8123/devis). Laisse PowerShell ouvert pendant que tu utilises le site. Pour arrêter le serveur, appuie sur **Ctrl+C**.

Sans `app/config.local.php`, la vitrine et le devis utilisent les réglages par défaut. Un devis n’est confirmé qu’après son enregistrement dans un fichier privé, hors du dossier du site. Aucun e-mail n’est envoyé automatiquement.

## 2. Tester le portail sans SQL

Utilise uniquement des comptes et des pièces fictifs. Dans PowerShell, depuis le dossier du projet, crée la configuration de recette **si tu n’as pas déjà de configuration locale**.

```powershell
if (Test-Path app/config.local.php) {
    Write-Host "Une configuration locale existe déjà. Ne la remplace pas sans la sauvegarder."
} else {
    Copy-Item app/config.recette.php app/config.local.php
}
```

Si une configuration existait déjà, compare-la à [la configuration de recette](app/config.recette.php) avant de continuer. `app/config.local.php` reste sur ton PC et ne se publie pas dans Git.

1. **Crée le premier compte cabinet.** Lance `php tools/portal/comptes.php cabinet cabinet@example.invalid`. Garde le code affiché dans le terminal. Il est valable 24 heures et ne fonctionne qu’une fois. Si ce compte existe déjà et que tu as besoin d’un nouveau code, lance `php tools/portal/comptes.php reinitialiser cabinet@example.invalid`. Cette commande invalide l’ancien accès et les sessions en cours.
2. **Démarre le site.** Arrête d’abord le serveur de l’étape 1 s’il tourne encore, puis lance cette commande pour tester jusqu’à cinq pièces de 10 Mio chacune.

   ```powershell
   php -d upload_max_filesize=10M -d post_max_size=55M -d max_file_uploads=6 -S 127.0.0.1:8123 index.php
   ```

3. **Active ton compte cabinet.** Ouvre [l’activation](http://127.0.0.1:8123/espace-client/activation), saisis le code, choisis ton mot de passe, puis [connecte-toi](http://127.0.0.1:8123/espace-client/connexion) avec `cabinet@example.invalid`.
4. **Essaie le parcours client.** Dans l’espace cabinet, choisis **Inviter un client**. Dans une fenêtre privée, active le compte fictif avec le nouveau code, puis connecte-toi et dépose un PDF, un JPG ou un PNG en choisissant un mois et une catégorie. Reviens dans l’espace cabinet pour retrouver la pièce et passer son statut de **Reçu** à **Traité** ou **À remplacer**. Le changement apparaît dans l’historique du client.

## Retrouver une demande de devis

Connecte-toi avec le compte cabinet et ouvre [Demandes de devis](http://127.0.0.1:8123/espace-client/demandes).

Si tu n’as pas activé le portail, tu peux lire les devis enregistrés en fichiers depuis un terminal ouvert dans le dossier du projet.

```powershell
php tools/devis.php liste
php tools/devis.php lire ID
```

Remplace `ID` par l’identifiant affiché dans la liste. `php tools/devis.php chemin` indique où les fichiers de devis sont stockés. Ne copie ni ces fichiers ni les pièces dans Git ou dans le dossier public du site.

## Si quelque chose bloque

| Ce que tu vois | Quoi vérifier |
| --- | --- |
| `php` n’est pas reconnu | Vérifie que PHP en ligne de commande est installé et accessible dans PowerShell avec `php -v`. |
| Le site ne s’ouvre pas | Garde la fenêtre du serveur ouverte et utilise `http://127.0.0.1:8123/`. Si le port est déjà pris, utilise `8124` dans la commande et dans l’adresse. |
| « Espace client non ouvert » | Le portail est désactivé par défaut. Vérifie `app/config.local.php` et la valeur `portal.enabled`. Pour la recette, utilise `127.0.0.1` et le serveur PHP intégré. |
| Le devis ne s’enregistre pas ou le dépôt échoue | Vérifie que PHP peut écrire dans un dossier privé à côté du site. Si tu as choisi un chemin personnalisé, il doit être absolu et hors du projet et de la racine web. Pour le dépôt de recette, vérifie aussi le démarrage avec les limites PHP de l’étape 2. |

## Tests et mise en ligne

Si Python est installé, lance les tests ci-dessous depuis le dossier du projet. Ils démarrent leur propre serveur temporaire et ne touchent pas à tes demandes ni à tes pièces.

```powershell
python tools/tests/devis-sans-sql.py
python tools/tests/portail-sans-sql.py
```

La recette Windows sans SQL est documentée dans [l’état du projet](projet/NOW.md). MySQL reste une option, pas un prérequis. Le scanner de recette est factice et ne doit jamais servir pour de vraies pièces. Avant une ouverture à des clients, il reste à vérifier sur l’hébergement cible HTTPS, le stockage privé, un antivirus réel et la restauration des sauvegardes. Le serveur `php -S` est réservé aux essais locaux.

Pour retrouver les références du projet, voir la [charte graphique](projet/CHARTE-GRAPHIQUE.md), les [décisions techniques](projet/DECISIONS.md) et le [guide du gestionnaire local de médias](tools/media-manager/README.md).
