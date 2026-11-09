# Scripts SQL

Los scripts son independientes de PHP y se ejecutan con `psql` dentro del contenedor `db`.
En `compose.yaml` la carpeta `database/sql` se monta como `/scripts` (solo lectura).

| Orden | Archivo | Qué hace | Base a la que se conecta |
|---|---|---|---|
| 0 | `00_crear_base_datos.sql` | Crea `biblioteca_escolar` si no existe | `postgres` |
| 1 | `01_crear_tablas.sql` | Crea tablas y relaciones | `biblioteca_escolar` |
| 2 | `02_indices_y_restricciones.sql` | Índices, restricciones, triggers | `biblioteca_escolar` |
| 3 | `03_datos_demostracion.sql` | Datos ficticios (opcional) | `biblioteca_escolar` |

## Ejecución (PowerShell, desde la raíz del proyecto)

```powershell
docker compose exec -T db psql -U biblioteca -d postgres          -f /scripts/00_crear_base_datos.sql
docker compose exec -T db psql -U biblioteca -d biblioteca_escolar -f /scripts/01_crear_tablas.sql
docker compose exec -T db psql -U biblioteca -d biblioteca_escolar -f /scripts/02_indices_y_restricciones.sql
docker compose exec -T db psql -U biblioteca -d biblioteca_escolar -f /scripts/03_datos_demostracion.sql
```

El script `scripts/instalar.ps1` ejecuta los pasos 0, 1 y 2 (y el 3 si se le pasa `-Demo`).

## Notas

- Docker Compose crea la base `biblioteca_escolar` automáticamente (`POSTGRES_DB`), por lo que
  el script 00 se puede omitir. Es seguro ejecutarlo igualmente: solo crea la base si falta.
- Los scripts 00, 01 y 02 se pueden repetir sin dañar datos (usan `IF NOT EXISTS`).
- El script 03 solo debe ejecutarse **una vez y en una base vacía**. No lo ejecute en producción
  si no quiere datos de ejemplo; puede borrarlos con una restauración de copia de seguridad.
- Ningún script contiene `DROP` ni `TRUNCATE`, ni contraseñas. La cuenta de administrador se crea
  con `docker compose exec app php scripts/crear_administrador.php`.
- La cuenta "Cuenta de demostración" creada por el script 03 está inactiva y no puede iniciar sesión;
  solo existe porque cada préstamo debe registrar qué cuenta lo creó.
