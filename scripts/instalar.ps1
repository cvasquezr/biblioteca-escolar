# Prepara la base de datos (tablas, índices). Ejecutar desde la raíz del proyecto:
#   .\scripts\instalar.ps1          -> estructura vacía
#   .\scripts\instalar.ps1 -Demo    -> además carga datos ficticios de demostración
param([switch]$Demo)
$ErrorActionPreference = 'Stop'
if (-not (Test-Path .env)) { Copy-Item .env.example .env; Write-Host 'Se creó .env: cambie DB_PASSWORD antes de usar en serio.' }
docker compose up -d --build
$u = 'biblioteca'
docker compose exec -T db psql -v ON_ERROR_STOP=1 -U $u -d postgres           -f /scripts/00_crear_base_datos.sql
docker compose exec -T db psql -v ON_ERROR_STOP=1 -U $u -d biblioteca_escolar -f /scripts/01_crear_tablas.sql
docker compose exec -T db psql -v ON_ERROR_STOP=1 -U $u -d biblioteca_escolar -f /scripts/02_indices_y_restricciones.sql
if ($Demo) { docker compose exec -T db psql -v ON_ERROR_STOP=1 -U $u -d biblioteca_escolar -f /scripts/03_datos_demostracion.sql }
Write-Host 'Listo. Cree el administrador con: docker compose exec app php scripts/crear_administrador.php'
Write-Host 'Abra http://localhost:8080'
