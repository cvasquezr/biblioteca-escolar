# Sistema de Gestión de Biblioteca Escolar

Aplicación web para organizar una biblioteca escolar: inventario de libros y ejemplares, lectores,
préstamos, devoluciones e informes. **PHP 8.5 puro** (sin frameworks), **PostgreSQL 18**, Apache,
Docker Compose, Bootstrap 5 por CDN y JavaScript nativo. Sin Node.js ni compilación.

## Requisitos
Docker Desktop (con Docker Compose) y Git. Se necesita internet para cargar Bootstrap desde el CDN.

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

## Documentación
`docs/REQUERIMIENTOS.md`, `docs/ARQUITECTURA.md`, `docs/MODELO_DATOS.md`, `docs/MANUAL_USUARIO.md`,
`docs/MANUAL_TECNICO.md`, `docs/PRUEBAS.md`, `database/sql/README_SQL.md`.
