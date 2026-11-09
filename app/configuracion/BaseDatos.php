<?php

declare(strict_types=1);

/** Conexión PDO única a PostgreSQL. */
final class BaseDatos
{
    private static ?PDO $conexion = null;

    public static function conexion(): PDO
    {
        if (self::$conexion === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                entorno('DB_HOST', 'db'),
                entorno('DB_PORT', '5432'),
                entorno('DB_DATABASE', 'biblioteca_escolar')
            );
            self::$conexion = new PDO($dsn, entorno('DB_USERNAME', 'biblioteca'), entorno('DB_PASSWORD', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            // Fechas "de hoy" calculadas con la hora de Colombia
            self::$conexion->exec("SET TIME ZONE 'America/Bogota'");
        }
        return self::$conexion;
    }

    /** Prepara la consulta y enlaza cada parámetro con su tipo (los booleanos de PHP necesitan PARAM_BOOL). */
    private static function preparar(string $sql, array $params): PDOStatement
    {
        $st = self::conexion()->prepare($sql);
        foreach ($params as $clave => $valor) {
            $tipo = match (true) {
                is_bool($valor) => PDO::PARAM_BOOL,
                is_int($valor) => PDO::PARAM_INT,
                $valor === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $st->bindValue(is_int($clave) ? $clave + 1 : $clave, $valor, $tipo);
        }
        $st->execute();
        return $st;
    }

    /** Ejecuta una consulta preparada y devuelve todas las filas. */
    public static function filas(string $sql, array $params = []): array
    {
        $st = self::preparar($sql, $params);
        return $st->fetchAll();
    }

    public static function fila(string $sql, array $params = []): ?array
    {
        $st = self::preparar($sql, $params);
        $f = $st->fetch();
        return $f === false ? null : $f;
    }

    public static function valor(string $sql, array $params = []): mixed
    {
        $st = self::preparar($sql, $params);
        $v = $st->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function ejecutar(string $sql, array $params = []): int
    {
        $st = self::preparar($sql, $params);
        return $st->rowCount();
    }
}

