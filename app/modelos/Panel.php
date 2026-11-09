<?php

declare(strict_types=1);

/** Indicadores del dashboard (consultas reales a PostgreSQL). */
final class Panel
{
    /** @return array<string,int> */
    public static function indicadores(): array
    {
        $fila = BaseDatos::fila(
            "SELECT
               (SELECT count(*) FROM libros WHERE activo) AS titulos,
               (SELECT count(*) FROM ejemplares) AS ejemplares,
               (SELECT count(*) FROM ejemplares e WHERE e.estado = 'disponible'
                  AND NOT EXISTS (SELECT 1 FROM prestamos p WHERE p.ejemplar_id = e.id AND p.estado = 'activo')) AS disponibles,
               (SELECT count(*) FROM prestamos WHERE estado = 'activo') AS activos,
               (SELECT count(*) FROM prestamos WHERE estado = 'activo' AND fecha_limite < CURRENT_DATE) AS vencidos,
               (SELECT count(*) FROM usuarios WHERE activo) AS lectores"
        );
        return array_map('intval', $fila);
    }
}
