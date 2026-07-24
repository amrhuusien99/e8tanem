# First-time local setup (requires Docker Desktop)
$ErrorActionPreference = "Stop"
Set-Location (Split-Path $PSScriptRoot -Parent)

if (-not (Test-Path .env)) { Copy-Item .env.example .env }
New-Item -ItemType Directory -Force -Path database | Out-Null
if (-not (Test-Path database\database.sqlite)) { New-Item -ItemType File -Path database\database.sqlite | Out-Null }

docker run --rm -v "${PWD}:/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --no-interaction --prefer-dist --ignore-platform-req=ext-intl
docker run --rm -v "${PWD}:/var/www/html" -w /var/www/html laravelsail/php84-composer:latest php artisan key:generate --force
docker run --rm -v "${PWD}:/var/www/html" -w /var/www/html laravelsail/php84-composer:latest php artisan migrate --seed --force
docker run --rm -v "${PWD}:/var/www/html" -w /var/www/html laravelsail/php84-composer:latest php artisan db:seed --class=DevSampleSeeder --force

Write-Host "`nSetup complete. Start the server with: docker compose up -d"
