<?php

declare(strict_types=1);

/**
 * Informes: todo el filtrado y los cálculos los hace PostgreSQL.
 * Cada informe devuelve columnas (alias => título), una consulta y sus parámetros.
 */
final class Informe
{
    public const TIPOS = [
        'inventario' => 'Inventario general',
        'disponibles' => 'Ejemplares disponibles',
        'prestados' => 'Ejemplares prestados',
        'danados_perdidos' => 'Ejemplares dañados o perdidos',
        'activos' => 'Préstamos activos',
        'vencidos' => 'Préstamos vencidos',
        'historial' => 'Historial de préstamos',
        'semanales' => 'Préstamos semanales',
        'mensuales' => 'Préstamos mensuales',
        'semestrales' => 'Préstamos semestrales',
        'anuales' => 'Préstamos anuales',
        'mas_prestados' => 'Libros más prestados',
    ];

    /** Qué fecha filtra cada informe. */
    public const FILTRO_FECHA = [
        'inventario' => 'registro del ejemplar', 'disponibles' => 'registro del ejemplar',
        'prestados' => 'préstamo', 'danados_perdidos' => 'registro del ejemplar',
        'activos' => 'préstamo', 'vencidos' => 'préstamo', 'historial' => 'préstamo',
        'semanales' => 'préstamo', 'mensuales' => 'préstamo', 'semestrales' => 'préstamo',
        'anuales' => 'préstamo', 'mas_prestados' => 'préstamo',
    ];

    /** Rango sugerido para los informes por periodo cuando no se indican fechas. */
    public static function rangoPorDefecto(string $tipo): ?array
    {
        $hoy = new DateTimeImmutable('today');
        return match ($tipo) {
            'semanales' => [$hoy->modify('-84 days')->format('Y-m-d'), $hoy->format('Y-m-d')],
            'mensuales' => [$hoy->modify('first day of this month')->modify('-11 months')->format('Y-m-d'), $hoy->format('Y-m-d')],
            'semestrales' => [$hoy->modify('-3 years')->format('Y-m-d'), $hoy->format('Y-m-d')],
            'anuales' => [$hoy->modify('-5 years')->format('Y-m-d'), $hoy->format('Y-m-d')],
            default => null,
        };
    }

    private const EJEMPLAR_FROM = "FROM ejemplares e JOIN libros l ON l.id = e.libro_id
        LEFT JOIN prestamos pa ON pa.ejemplar_id = e.id AND pa.estado = 'activo'";

    private const ESTADO_EJEMPLAR_TEXTO = "CASE WHEN pa.id IS NOT NULL THEN 'Prestado'
        WHEN e.estado = 'disponible' THEN 'Disponible' WHEN e.estado = 'danado' THEN 'Dañado'
        WHEN e.estado = 'perdido' THEN 'Perdido' ELSE 'Inactivo' END";

    private const PRESTAMO_FROM = "FROM prestamos p JOIN usuarios u ON u.id = p.usuario_id
        JOIN ejemplares e ON e.id = p.ejemplar_id JOIN libros l ON l.id = e.libro_id";

    /**
     * @return array{columnas:array<string,string>, sql:string, params:array<string,string>}
     */
    public static function definir(string $tipo, string $desde, string $hasta): array
    {
        if (!isset(self::TIPOS[$tipo])) {
            throw new ErrorNegocio('Tipo de informe no válido.');
        }
        $params = [];
        $filtroEj = '';
        $filtroPr = '';
        if (fechaValida($desde)) {
            $filtroEj .= ' AND e.created_at::date >= :desde';
            $filtroPr .= ' AND p.fecha_prestamo >= :desde';
            $params['desde'] = $desde;
        }
        if (fechaValida($hasta)) {
            $filtroEj .= ' AND e.created_at::date <= :hasta';
            $filtroPr .= ' AND p.fecha_prestamo <= :hasta';
            $params['hasta'] = $hasta;
        }

        $colsEjemplar = [
            'codigo' => 'Código', 'titulo' => 'Título', 'autor' => 'Autor', 'editorial' => 'Editorial',
            'categoria' => 'Categoría', 'ubicacion' => 'Ubicación', 'estado' => 'Estado',
        ];
        $selEjemplar = "SELECT e.codigo_inventario AS codigo, l.titulo, l.autor, COALESCE(l.editorial, '') AS editorial,
                COALESCE(l.categoria, '') AS categoria, COALESCE(l.ubicacion, '') AS ubicacion,
                " . self::ESTADO_EJEMPLAR_TEXTO . " AS estado " . self::EJEMPLAR_FROM;
        $ordEjemplar = ' ORDER BY lower(l.titulo), e.codigo_inventario';

        $colsPrestamo = [
            'prestamo' => 'Fecha préstamo', 'limite' => 'Fecha límite', 'devolucion' => 'Devolución',
            'identificacion' => 'Identificación', 'lector' => 'Lector', 'titulo' => 'Libro',
            'codigo' => 'Código', 'estado' => 'Estado',
        ];
        $selPrestamo = "SELECT to_char(p.fecha_prestamo, 'DD/MM/YYYY') AS prestamo,
                to_char(p.fecha_limite, 'DD/MM/YYYY') AS limite,
                COALESCE(to_char(p.fecha_devolucion, 'DD/MM/YYYY'), '') AS devolucion,
                u.identificacion, u.nombres || ' ' || u.apellidos AS lector, l.titulo,
                e.codigo_inventario AS codigo,
                CASE WHEN p.estado = 'devuelto' THEN 'Devuelto'
                     WHEN p.fecha_limite < CURRENT_DATE THEN 'Vencido' ELSE 'Activo' END AS estado "
            . self::PRESTAMO_FROM;
        $ordPrestamo = ' ORDER BY p.fecha_prestamo DESC, p.id DESC';

        switch ($tipo) {
            case 'inventario':
                return ['columnas' => $colsEjemplar, 'params' => $params,
                    'sql' => "$selEjemplar WHERE TRUE $filtroEj $ordEjemplar"];
            case 'disponibles':
                return ['columnas' => $colsEjemplar, 'params' => $params,
                    'sql' => "$selEjemplar WHERE e.estado = 'disponible' AND pa.id IS NULL $filtroEj $ordEjemplar"];
            case 'danados_perdidos':
                return ['columnas' => $colsEjemplar, 'params' => $params,
                    'sql' => "$selEjemplar WHERE e.estado IN ('danado', 'perdido') $filtroEj $ordEjemplar"];
            case 'prestados':
                // Ejemplares prestados hoy; el rango filtra por fecha del préstamo
                return ['columnas' => $colsPrestamo, 'params' => $params,
                    'sql' => "$selPrestamo WHERE p.estado = 'activo' $filtroPr $ordPrestamo"];
            case 'activos':
                return ['columnas' => $colsPrestamo, 'params' => $params,
                    'sql' => "$selPrestamo WHERE p.estado = 'activo' $filtroPr ORDER BY p.fecha_limite, p.id"];
            case 'vencidos':
                return ['columnas' => $colsPrestamo, 'params' => $params,
                    'sql' => "$selPrestamo WHERE p.estado = 'activo' AND p.fecha_limite < CURRENT_DATE $filtroPr
                              ORDER BY p.fecha_limite, p.id"];
            case 'historial':
                return ['columnas' => $colsPrestamo, 'params' => $params,
                    'sql' => "$selPrestamo WHERE TRUE $filtroPr $ordPrestamo"];
            case 'mas_prestados':
                return [
                    'columnas' => ['titulo' => 'Título', 'autor' => 'Autor', 'total' => 'Préstamos', 'ultimo' => 'Último préstamo'],
                    'params' => $params,
                    'sql' => "SELECT l.titulo, l.autor, count(*) AS total, to_char(max(p.fecha_prestamo), 'DD/MM/YYYY') AS ultimo
                              " . self::PRESTAMO_FROM . " WHERE TRUE $filtroPr
                              GROUP BY l.id, l.titulo, l.autor ORDER BY count(*) DESC, lower(l.titulo)",
                ];
        }

        // Informes por periodo: semanales, mensuales, semestrales, anuales
        [$clave, $etiqueta, $titulo] = match ($tipo) {
            'semanales' => ["date_trunc('week', p.fecha_prestamo)::date", "to_char(date_trunc('week', p.fecha_prestamo), 'DD/MM/YYYY')", 'Semana que inicia el'],
            'mensuales' => ["date_trunc('month', p.fecha_prestamo)::date", "to_char(date_trunc('month', p.fecha_prestamo), 'YYYY-MM')", 'Mes'],
            'semestrales' => [
                "make_date(extract(year FROM p.fecha_prestamo)::int, CASE WHEN extract(month FROM p.fecha_prestamo) <= 6 THEN 1 ELSE 7 END, 1)",
                "extract(year FROM p.fecha_prestamo)::int || ' - ' || CASE WHEN extract(month FROM p.fecha_prestamo) <= 6 THEN 'Semestre 1' ELSE 'Semestre 2' END",
                'Semestre',
            ],
            default => ["date_trunc('year', p.fecha_prestamo)::date", "extract(year FROM p.fecha_prestamo)::int::text", 'Año'],
        };
        return [
            'columnas' => ['periodo' => $titulo, 'total' => 'Préstamos', 'devueltos' => 'Devueltos', 'activos' => 'Aún activos', 'vencidos' => 'Vencidos'],
            'params' => $params,
            'sql' => "SELECT $etiqueta AS periodo, count(*) AS total,
                        count(*) FILTER (WHERE p.estado = 'devuelto') AS devueltos,
                        count(*) FILTER (WHERE p.estado = 'activo') AS activos,
                        count(*) FILTER (WHERE p.estado = 'activo' AND p.fecha_limite < CURRENT_DATE) AS vencidos
                      " . self::PRESTAMO_FROM . " WHERE TRUE $filtroPr
                      GROUP BY $clave, 1 ORDER BY $clave DESC",
        ];
    }

    /** @return array{filas:array, paginacion:array} */
    public static function pagina(array $def, int $porPagina = 25): array
    {
        $total = (int) BaseDatos::valor("SELECT count(*) FROM ({$def['sql']}) t", $def['params']);
        $pag = calcularPaginacion($total, $porPagina);
        $st = BaseDatos::conexion()->prepare($def['sql'] . ' LIMIT :lim OFFSET :off');
        foreach ($def['params'] as $k => $v) {
            $st->bindValue(':' . $k, $v);
        }
        $st->bindValue(':lim', $pag['porPagina'], PDO::PARAM_INT);
        $st->bindValue(':off', $pag['desplazamiento'], PDO::PARAM_INT);
        $st->execute();
        return ['filas' => $st->fetchAll(), 'paginacion' => $pag];
    }

    /** Recorre todas las filas sin cargarlas en memoria (para el CSV). */
    public static function recorrer(array $def): Generator
    {
        $st = BaseDatos::conexion()->prepare($def['sql']);
        $st->execute($def['params']);
        while (($fila = $st->fetch()) !== false) {
            yield $fila;
        }
    }

    /** Envía el informe como CSV (UTF-8 con BOM y ; para que Excel en español lo abra bien). */
    public static function exportarCsv(string $tipo, array $def): never
    {
        $nombre = 'informe_' . $tipo . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, array_values($def['columnas']), ';', '"', '\\');
        foreach (self::recorrer($def) as $fila) {
            $linea = [];
            foreach (array_keys($def['columnas']) as $col) {
                $linea[] = self::celdaSegura((string) $fila[$col]);
            }
            fputcsv($salida, $linea, ';', '"', '\\');
        }
        fclose($salida);
        exit;
    }

    /** Evita que Excel interprete textos como fórmulas (=, +, -, @). */
    private static function celdaSegura(string $valor): string
    {
        return $valor !== '' && strpbrk($valor[0], "=+-@\t\r") !== false ? "'" . $valor : $valor;
    }
}
