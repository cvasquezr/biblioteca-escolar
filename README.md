# Sistema de Gestión de Biblioteca Escolar

Aplicación web para organizar una biblioteca escolar: inventario de libros y ejemplares, lectores,
préstamos, devoluciones e informes. **PHP 8.5 puro** (sin frameworks), **PostgreSQL 18**, Apache,
Docker Compose, Bootstrap 5 (copia local en `public/vendor`) y JavaScript nativo. Sin Node.js ni compilación.

## Requisitos
Docker Desktop (con Docker Compose) y Git.

## Instalación (PowerShell)
```powershell
Copy-Item .env.example .env            # y cambie DB_PASSWORD
docker compose up -d --build
docker compose exec -T db psql -U biblioteca -d postgres           -f /scripts/00_crear_base_datos.sql
docker compose exec -T db psql -U biblioteca -d biblioteca_escolar -f /scripts/01_crear_tablas.sql
docker compose exec -T db psql -U biblioteca -d biblioteca_escolar -f /scripts/02_indices_y_restricciones.sql
# Opcional: datos ficticios de demostración (solo en base vacía)
docker compose exec -T db psql -U biblioteca -d biblioteca_escolar -f /scripts/03_datos_demostracion.sql
# Crear el primer administrador (pide nombre, correo y contraseña)
docker compose exec app php scripts/crear_administrador.php
```
(`scripts\instalar.ps1 [-Demo]` hace los pasos de base de datos de una vez.)
Abra **http://localhost:8080** e inicie sesión.

## Comandos útiles
```powershell
docker compose ps                                   # estado
docker compose logs -f                              # registros
docker compose exec app bash                        # entrar a PHP
docker compose exec db psql -U biblioteca -d biblioteca_escolar   # consola SQL
docker compose exec app php tests/ejecutar.php      # pruebas automáticas
docker compose down                                 # detener SIN borrar datos
```
> **Advertencia:** `docker compose down -v` borra el volumen y **elimina la base de datos**.

## Módulos
Panel · Libros · Ejemplares · Lectores · Nuevo préstamo · Préstamos activos · Devoluciones ·
Historial · Informes (con CSV) · Importar inventario desde Excel · Cuentas del sistema (solo administrador).

## Importar el inventario inicial
Los Excel están en `database/import/` (no se suben a Git). Menú **Importar inventario**: compare los
dos archivos, revise el informe previo y confirme. Ver `docs/MANUAL_USUARIO.md`.

## Demo en internet (Render, plan gratuito)

[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/cvasquezr/biblioteca-escolar)

1. Cree una cuenta en Render e inicie sesión con GitHub.
2. Pulse el botón de arriba (usa `render.yaml`), elija el repositorio y pulse **Apply**.
3. Espere unos minutos y abra la URL `https://biblioteca-escolar-xxxx.onrender.com`.
4. Ingrese con `admin@demo.local`. La contraseña la genera Render: en el servicio *biblioteca-escolar* → **Environment** → `ADMIN_PASSWORD`.

Las tablas se crean solas al arrancar (`scripts/preparar.php`) y se cargan datos ficticios (`DEMO_DATA=true`; póngalo en `false` para empezar vacío).
Limitaciones del plan gratis: la app se duerme tras ~15 min sin visitas (la primera visita tarda 30–60 s en despertarla) y la base gratuita **se borra a los 30 días**. Es solo para demostración: no guarde datos reales.

## Documentación
`docs/REQUERIMIENTOS.md`, `docs/ARQUITECTURA.md`, `docs/MODELO_DATOS.md`, `docs/MANUAL_USUARIO.md`,
`docs/MANUAL_TECNICO.md`, `docs/PRUEBAS.md`, `database/sql/README_SQL.md`.
