-- Crea la base de datos biblioteca_escolar si no existe.
-- Se ejecuta conectado a la base "postgres" (no a biblioteca_escolar):
--   docker compose exec -T db psql -U biblioteca -d postgres -f /scripts/00_crear_base_datos.sql
-- Nota: con Docker Compose la base ya se crea sola (POSTGRES_DB), por eso
-- este script es seguro de repetir: solo crea la base si falta.
SELECT 'CREATE DATABASE biblioteca_escolar ENCODING ''UTF8'''
WHERE NOT EXISTS (SELECT 1 FROM pg_database WHERE datname = 'biblioteca_escolar')
\gexec
