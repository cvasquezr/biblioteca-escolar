<?php

declare(strict_types=1);

final class Prestamo
{
    private const FROM = "FROM prestamos p
        JOIN usuarios u ON u.id = p.usuario_id
        JOIN ejemplares e ON e.id = p.ejemplar_id
        JOIN libros l ON l.id = e.libro_id
        JOIN cuentas c ON c.id = p.cuenta_id";

    private const COLUMNAS = "p.id, p.fecha_prestamo, p.fecha_limite, p.fecha_devolucion, p.estado, p.observaciones,
        u.id AS usuario_id, u.identificacion, u.nombres || ' ' || u.apellidos AS lector,
        e.id AS ejemplar_id, e.codigo_inventario, l.id AS libro_id, l.titulo, c.nombre AS registrado_por";

    /**
     * Lista préstamos con filtros.
     * estado: activo | vencido | devuelto | '' (todos)
     *
     * @return array{filas:array, paginacion:array}
     */
    public static function buscar(array $f, int $porPagina = 15): array
    {
        $where = ['TRUE'];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = "(u.identificacion ILIKE :q OR unaccent(u.nombres || ' ' || u.apellidos) ILIKE unaccent(:q)
                         OR unaccent(l.titulo) ILIKE unaccent(:q) OR e.codigo_inventario ILIKE :q)";
            $params['q'] = '%' . addcslashes($f['q'], '%_\\') . '%';
        }
        switch ($f['estado'] ?? '') {
            case 'activo':
                $where[] = "p.estado = 'activo'";
                break;
            case 'vencido':
                $where[] = "p.estado = 'activo' AND p.fecha_limite < CURRENT_DATE";
                break;
            case 'devuelto':
                $where[] = "p.estado = 'devuelto'";
                break;
        }
        if (fechaValida($f['desde'] ?? '')) {
            $where[] = 'p.fecha_prestamo >= :desde';
            $params['desde'] = $f['desde'];
        }
        if (fechaValida($f['hasta'] ?? '')) {
            $where[] = 'p.fecha_prestamo <= :hasta';
            $params['hasta'] = $f['hasta'];
        }
        $w = implode(' AND ', $where);
        $total = (int) BaseDatos::valor("SELECT count(*) " . self::FROM . " WHERE $w", $params);
        $pag = calcularPaginacion($total, $porPagina);

        $orden = ($f['orden'] ?? '') === 'limite'
            ? 'p.fecha_limite ASC, p.id' : 'p.fecha_prestamo DESC, p.id DESC';
        $st = BaseDatos::conexion()->prepare(
            'SELECT ' . self::COLUMNAS . ' ' . self::FROM . " WHERE $w ORDER BY $orden LIMIT :lim OFFSET :off"
        );
        foreach ($params as $k => $v) {
            $st->bindValue(':' . $k, $v);
        }
        $st->bindValue(':lim', $pag['porPagina'], PDO::PARAM_INT);
        $st->bindValue(':off', $pag['desplazamiento'], PDO::PARAM_INT);
        $st->execute();
        return ['filas' => $st->fetchAll(), 'paginacion' => $pag];
    }

    public static function obtener(int $id): ?array
    {
        return BaseDatos::fila('SELECT ' . self::COLUMNAS . ' ' . self::FROM . ' WHERE p.id = :id', ['id' => $id]);
    }

    public static function ultimos(int $cantidad = 6): array
    {
        $st = BaseDatos::conexion()->prepare(
            'SELECT ' . self::COLUMNAS . ' ' . self::FROM . ' ORDER BY p.created_at DESC, p.id DESC LIMIT :n'
        );
        $st->bindValue(':n', $cantidad, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
}
