# Lancement du gestionnaire local de médias (aucun cloud, aucun compte).
# Usage : clic droit > « Exécuter avec PowerShell », ou :
#   powershell -ExecutionPolicy Bypass -File tools/media-manager/lancer.ps1

$ErrorActionPreference = 'Stop'

$port = 8124
$dossierOutil = $PSScriptRoot
$racineProjet = Split-Path -Parent (Split-Path -Parent $dossierOutil)
$url = "http://127.0.0.1:$port/"

$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
    Write-Error "PHP est introuvable dans le PATH. Installe PHP 8.1+ ou ajoute-le au PATH, puis relance."
    exit 1
}

$dejaActif = Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue
if ($dejaActif) {
    Write-Host "Le gestionnaire écoute déjà sur $url — ouverture du navigateur."
    Start-Process $url
    exit 0
}

$journal = Join-Path $racineProjet 'storage\media-manager\logs\serveur.log'
$dossierJournal = Split-Path -Parent $journal
if (-not (Test-Path $dossierJournal)) {
    New-Item -ItemType Directory -Path $dossierJournal -Force | Out-Null
}

$arguments = @(
    '-d', 'upload_max_filesize=256M',
    '-d', 'post_max_size=300M',
    '-d', 'max_file_uploads=20',
    '-S', "127.0.0.1:$port",
    (Join-Path $dossierOutil 'server.php')
)

Write-Host "Démarrage du gestionnaire de médias sur $url"
Write-Host "Journal du serveur : $journal"

Start-Process -FilePath $php.Source -ArgumentList $arguments -WorkingDirectory $racineProjet -WindowStyle Minimized -RedirectStandardError $journal -RedirectStandardOutput ($journal + '.out')
Start-Sleep -Milliseconds 1200
Start-Process $url
