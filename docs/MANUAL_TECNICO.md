# Manual técnico

## Docker
- `app`: `php:8.5-apache` con `pdo_pgsql`, `intl`, `zip`; DocumentRoot `/var/www/html/public`; zona horaria America/Bogota. El proyecto se monta como volumen para editar sin reconstruir.
- `db`: `postgres:18-alpine`, volumen `biblioteca_datos`, healthcheck, puerto **no** publicado. Monta `database/sql` en `/scripts` (solo lectura) y `backups/` en `/backups`.
- Variables en `.env` (ver `.env.example`). `.env` está en `.gitignore`. Puerto web: `APP_PORT` (8080).

## PHP
`app/inicio.php` carga helpers, autocarga clases, define el manejador de errores (los errores van al log del contenedor, no al usuario) y abre la sesión (cookie HttpOnly, SameSite=Lax, `Secure` si hay HTTPS, modo estricto). Cabeceras: CSP, X-Frame-Options, nosniff.
Si usa HTTPS detrás de un proxy, envíe `X-Forwarded-Proto: https`.

## Seguridad
PDO con consultas preparadas y tipos enlazados; `e()` para toda salida; CSRF en todos los POST; `password_hash/verify`; regeneración de sesión al ingresar; límite de intentos (5 por correo / 20 por IP cada 15 min); sesión revalidada en BD en cada petición; roles verificados en cada página/acción; Excel validado (extensión, firma ZIP, tamaño ≤5 MB, XML leído con `LIBXML_NONET`) y guardado fuera de `public/`.

## Copias de seguridad
```powershell
# Crear copia (queda en .\backups)
docker compose exec -T db pg_dump -U biblioteca -d biblioteca_escolar -Fc -f /backups/biblioteca.dump
# Restaurar (sobre una base vacía o nueva)
docker compose exec -T db pg_restore -U biblioteca -d biblioteca_escolar --no-owner /backups/biblioteca.dump
```
La carpeta `backups/` está en `.gitignore`. Guarde copias fuera del equipo.

## Git
Rama principal: `master`. Remoto: https://github.com/cvasquezr/biblioteca-escolar (público). Los Excel, `.env` y backups están ignorados.

## Cuenta de prueba local
Durante el desarrollo se creó `admin@ejemplo.com` solo para la base local. **Cree su propio administrador y desactive esa cuenta** antes de usar el sistema en serio.

## Despliegue en la nube (Render)
`render.yaml` define el servicio web (Dockerfile) y una base PostgreSQL gratuita. Al arrancar, `scripts/preparar.php` espera a la base, aplica los scripts 01 y 02 (repetibles), crea el primer administrador si no existe ninguno (`ADMIN_EMAIL`/`ADMIN_PASSWORD`) y, si `DEMO_DATA=true` y la base está vacía, carga los datos de demostración.
Variables relevantes: `DB_*`, `DB_SSLMODE` (p. ej. `require` si la base exige SSL), `TRUST_PROXY=true` (usa la IP real del cliente detrás del proxy para el límite de intentos), `APP_DEBUG=false`.
El disco del contenedor es temporal: los Excel subidos a `database/import/` se pierden al reiniciar.
