<?php

declare(strict_types=1);

/**
 * Copias físicas. El estado "prestado" no se guarda: se deduce de un préstamo activo.
 */
final class Ejemplar
{
    /** Expresión SQL con el estado efectivo (prestado / disponible / danado / ...). */
    private const ESTADO_EFECTIVO = "CASE WHEN pa.id IS NOT NULL THEN 'prestado' ELSE e.estado END";

    private const FROM = "FROM ejemplares e
        JOIN libros l ON l.id = e.libro_id
        LEFT JOIN prestamos pa ON pa.ejemplar_id = e.id AND pa.estado = 'activo'";

    public static function obtener(int $id): ?array
    {
        return BaseDatos::fila(
            'SELECT e.*, l.titulo, l.autor, ' . self::ESTADO_EFECTIVO . ' AS estado_efectivo ' . self::FROM . ' WHERE e.id = :id',
            ['id' => $id]
        );
    }

    public static function porLibro(int $libroId): array
    {
        return BaseDatos::filas(
            'SELECT e.*, ' . self::ESTADO_EFECTIVO . ' AS estado_efectivo,
                    (SELECT u.nombres || \' \' || u.apellidos FROM usuarios u WHERE u.id = pa.usuario_id) AS prestado_a,
                    pa.fecha_limite
             ' . self::FROM . ' WHERE e.libro_id = :id ORDER BY e.codigo_inventario',
            ['id' => $libroId]
        );
    }

    /** @return array{filas:array, paginacion:array} */
    public static function buscar(array $f, int $porPagina = 20): array
    {
        $where = ['TRUE'];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(e.codigo_inventario ILIKE :q OR unaccent(l.titulo) ILIKE unaccent(:q) OR unaccent(l.autor) ILIKE unaccent(:q))';
            $params['q'] = '%' . addcslashes($f['q'], '%_\\') . '%';
        }
        if (($f['estado'] ?? '') !== '') {
            $where[] = self::ESTADO_EFECTIVO . ' = :estado';
            $params['estado'] = $f['estado'];
        }
        $w = implode(' AND ', $where);
        $total = (int) BaseDatos::valor("SELECT count(*) " . self::FROM . " WHERE $w", $params);
        $pag = calcularPaginacion($total, $porPagina);

        $st = BaseDatos::conexion()->prepare(
            'SELECT e.id, e.codigo_inventario, e.observaciones, e.libro_id, l.titulo, l.autor, '
            . self::ESTADO_EFECTIVO . " AS estado_efectivo " . self::FROM
            . " WHERE $w ORDER BY lower(l.titulo), e.codigo_inventario LIMIT :lim OFFSET :off"
        );
        foreach ($params as $k => $v) {
            $st->bindValue(':' . $k, $v);
        }
        $st->bindValue(':lim', $pag['porPagina'], PDO::PARAM_INT);
        $st->bindValue(':off', $pag['desplazamiento'], PDO::PARAM_INT);
        $st->execute();
        return ['filas' => $st->fetchAll(), 'paginacion' => $pag];
    }

    /**
     * Crea 1 o varios ejemplares de un libro con códigos EJ-<libro>-<n>.
     * Si se crea uno solo se puede indicar un código propio.
     *
     * @return list<string> códigos creados
     */
    public static function crear(int $libroId, int $cantidad, ?string $codigoPropio, ?string $observaciones): array
    {
        if ($cantidad < 1 || $cantidad > 100) {
            throw new ErrorNegocio('La cantidad debe estar entre 1 y 100.');
        }
        if ($codigoPropio !== null && ($cantidad !== 1 || !preg_match('/^[A-Za-z0-9._\-\/]{2,40}$/', $codigoPropio))) {
            throw new ErrorNegocio('El código propio solo se admite para un ejemplar y debe tener 2 a 40 caracteres (letras, números, . _ - /).');
        }
        $pdo = BaseDatos::conexion();
        $propia = !$pdo->inTransaction(); // puede llamarse dentro de otra transacción (importación)
        if ($propia) {
            $pdo->beginTransaction();
        }
        try {
            // Se bloquea el libro para que dos personas no generen el mismo consecutivo
            $libro = BaseDatos::fila('SELECT id, activo FROM libros WHERE id = :id FOR UPDATE', ['id' => $libroId]);
            if ($libro === null) {
                throw new ErrorNegocio('El libro no existe.');
            }
            $prefijo = 'EJ-' . str_pad((string) $libroId, 5, '0', STR_PAD_LEFT) . '-';
            $siguiente = 1 + (int) BaseDatos::valor(
                "SELECT COALESCE(max(substring(codigo_inventario from '[0-9]+$')::int), 0)
                 FROM ejemplares WHERE libro_id = :id AND codigo_inventario ~ ('^' || :p || '[0-9]+$')",
                ['id' => $libroId, 'p' => $prefijo]
            );
            $codigos = [];
            for ($i = 0; $i < $cantidad; $i++) {
                $codigo = $codigoPropio ?? $prefijo . str_pad((string) ($siguiente + $i), 2, '0', STR_PAD_LEFT);
                try {
                    BaseDatos::ejecutar(
                        'INSERT INTO ejemplares (libro_id, codigo_inventario, observaciones) VALUES (:l, :c, :o)',
                        ['l' => $libroId, 'c' => $codigo, 'o' => $observaciones]
                    );
                } catch (PDOException $ex) {
                    if ($ex->getCode() === '23505') {
                        throw new ErrorNegocio("El código de inventario $codigo ya existe.");
                    }
                    throw $ex;
                }
                $codigos[] = $codigo;
            }
            if ($propia) {
                $pdo->commit();
            }
            return $codigos;
        } catch (Throwable $e) {
            if ($propia && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function actualizar(int $id, string $estado, ?string $observaciones): void
    {
        if (!isset(ESTADOS_EJEMPLAR[$estado])) {
            throw new ErrorNegocio('Estado de ejemplar no válido.');
        }
        $pdo = BaseDatos::conexion();
        $pdo->beginTransaction();
        try {
            $actual = BaseDatos::fila('SELECT estado FROM ejemplares WHERE id = :id FOR UPDATE', ['id' => $id]);
            if ($actual === null) {
                throw new ErrorNegocio('El ejemplar no existe.');
            }
            $prestado = BaseDatos::valor(
                "SELECT 1 FROM prestamos WHERE ejemplar_id = :id AND estado = 'activo'",
                ['id' => $id]
            );
            if ($prestado !== null && $estado !== $actual['estado']) {
                throw new ErrorNegocio('El ejemplar está prestado: registre primero la devolución para cambiar su estado.');
            }
            BaseDatos::ejecutar(
                'UPDATE ejemplares SET estado = :e, observaciones = :o WHERE id = :id',
                ['e' => $estado, 'o' => $observaciones, 'id' => $id]
            );
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
