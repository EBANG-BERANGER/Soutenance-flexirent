# Lance FlexiRent en local : demarre MySQL si besoin, prepare le projet si besoin, puis le serveur Laravel.
# Usage : clic droit > "Executer avec PowerShell", ou dans un terminal : .\start.ps1

$ErrorActionPreference = "Stop"

$phpBin      = "C:\tools\php84"
$composerBin = "C:\tools\composer"
$mysqlBin    = "C:\Program Files\MySQL\MySQL Server 8.4\bin"
$mysqlIni    = "C:\ProgramData\MySQL\my.ini"
$projectDir  = $PSScriptRoot

$env:Path = "$phpBin;$composerBin;$env:Path"

Write-Host "=== FlexiRent - demarrage ===" -ForegroundColor Cyan

# 1) Demarrer MySQL s'il ne tourne pas deja
$mysqlRunning = Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue
if (-not $mysqlRunning) {
    Write-Host "Demarrage de MySQL..." -ForegroundColor Yellow
    # Lance via WMI pour que MySQL survive a la fermeture de cette fenetre.
    Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{ CommandLine = "`"$mysqlBin\mysqld.exe`" --defaults-file=`"$mysqlIni`"" } | Out-Null
    $tries = 0
    while (-not (Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue) -and $tries -lt 30) {
        Start-Sleep -Seconds 1
        $tries++
    }
    if ($tries -ge 30) { Write-Host "MySQL n'a pas demarre. Voir C:\ProgramData\MySQL\MySQL Server 8.4\Data\*.err" -ForegroundColor Red; exit 1 }
} else {
    Write-Host "MySQL est deja demarre." -ForegroundColor Green
}

Set-Location $projectDir

# 2) Installer les dependances si vendor/ est absent
if (-not (Test-Path "$projectDir\vendor")) {
    Write-Host "Installation des dependances Composer..." -ForegroundColor Yellow
    composer install --no-interaction
}

# 3) Preparer .env si absent
if (-not (Test-Path "$projectDir\.env")) {
    Write-Host "Creation du fichier .env..." -ForegroundColor Yellow
    Copy-Item "$projectDir\.env.example" "$projectDir\.env"
    php artisan key:generate --ansi
}

# 4) S'assurer que la base flexirent existe
$env:MYSQL_PWD = 'Achille1994'
& "$mysqlBin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS flexirent;"
Remove-Item Env:\MYSQL_PWD

# 5) Appliquer les migrations en attente
Write-Host "Verification des migrations..." -ForegroundColor Yellow
php artisan migrate --force --ansi

# 6) Lancer le serveur de developpement
Write-Host ""
Write-Host "FlexiRent est pret sur http://localhost:8000" -ForegroundColor Green
Write-Host "Ctrl+C pour arreter le serveur (MySQL continuera de tourner en arriere-plan)." -ForegroundColor DarkGray
Write-Host ""
php artisan serve
