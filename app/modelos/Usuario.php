<?php

declare(strict_types=1);

/** Lectores de la biblioteca (tabla usuarios). */
final class Usuario
{
    /** @return array{filas:array, paginacion:array} */
    public static function buscar(array $f, int $porPagina = 15): array
    {
        $where = ['TRUE'];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = "(u.identificacion ILIKE :q OR unaccent(u.nombres || ' ' || u.apellidos) ILIKE unaccent(:q))";
            $params['q'] = '%' . addcslashes($f['q'], '%_\\') . '%';
        }
        if (isset(TIPOS_USUARIO[$f['tipo'] ?? ''])) {
            $where[] = 'u.tipo_usuario = :tipo';
            $params['tipo'] = $f['tipo'];
        }
        $estado = $f['estado'] ?? 'activo';
        if ($estado === 'activo') {
            $where[] = 'u.activo';
        } elseif ($estado === 'inactivo') {
            $where[] = 'NOT u.activo';
        }
        $w = implode(' AND ', $where);
        $total = (int) BaseDatos::valor("SELECT count(*) FROM usuarios u WHERE $w", $params);
        $pag = calcularPaginacion($total, $porPagina);

        $st = BaseDatos::conexion()->prepare(
            "SELECT u.*, (SELECT count(*) FROM prestamos p WHERE p.usuario_id = u.id AND p.estado = 'activo') AS prestamos_activos
             FROM usuarios u WHERE $w ORDER BY lower(u.apellidos), lower(u.nombres), u.id LIMIT :lim OFFSET :off"
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
        return BaseDatos::fila('SELECT * FROM usuarios WHERE id = :id', ['id' => $id]);
    }

    public static function porIdentificacion(string $identificacion): ?array
    {
        return BaseDatos::fila('SELECT * FROM usuarios WHERE identificacion = :i', ['i' => $identificacion]);
    }

    /** @return array<string,string> */
    public static function validar(array $d, ?int $idActual): array
    {
        $e = [];
        if ($d['identificacion'] === '' || longitud($d['identificacion']) > 30) {
            $e['identificacion'] = 'La identificación es obligatoria (máximo 30 caracteres).';
        } else {
            $otro = self::porIdentificacion($d['identificacion']);
            if ($otro !== null && (int) $otro['id'] !== $idActual) {
                $e['identificacion'] = 'Ya existe un lector con esa identificación.';
            }
        }
        if ($d['nombres'] === '' || longitud($d['nombres']) > 100) {
            $e['nombres'] = 'Los nombres son obligatorios (máximo 100 caracteres).';
        }
        if ($d['apellidos'] === '' || longitud($d['apellidos']) > 100) {
            $e['apellidos'] = 'Los apellidos son obligatorios (máximo 100 caracteres).';
        }
        if (!isset(TIPOS_USUARIO[$d['tipo_usuario']])) {
            $e['tipo_usuario'] = 'Seleccione el tipo de lector.';
        }
        if (longitud($d['grado']) > 30) {
            $e['grado'] = 'El grado no puede superar 30 caracteres.';
        }
        if ($d['telefono'] !== '' && !preg_match('/^[0-9+()\- ]{5,30}$/', $d['telefono'])) {
            $e['telefono'] = 'Ingrese un teléfono válido (solo números, espacios, + - y paréntesis).';
        }
        if ($d['correo'] !== '' && (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL) || longitud($d['correo']) > 160)) {
            $e['correo'] = 'Ingrese un correo válido.';
        }
        return $e;
    }

    private static function parametros(array $d): array
    {
        return [
            'identificacion' => $d['identificacion'],
            'nombres' => $d['nombres'],
            'apellidos' => $d['apellidos'],
            'tipo' => $d['tipo_usuario'],
            'grado' => nuloSiVacio($d['grado']),
            'telefono' => nuloSiVacio($d['telefono']),
            'correo' => nuloSiVacio($d['correo']),
        ];
    }

    public static function crear(array $d): int
    {
        try {
            return (int) BaseDatos::valor(
                'INSERT INTO usuarios (identificacion, nombres, apellidos, tipo_usuario, grado, telefono, correo)
                 VALUES (:identificacion, :nombres, :apellidos, :tipo, :grado, :telefono, :correo) RETURNING id',
                self::parametros($d)
            );
        } catch (PDOException $ex) {
            throw self::traducir($ex);
        }
    }

    public static function actualizar(int $id, array $d): void
    {
        try {
            BaseDatos::ejecutar(
                'UPDATE usuarios SET identificacion = :identificacion, nombres = :nombres, apellidos = :apellidos,
                        tipo_usuario = :tipo, grado = :grado, telefono = :telefono, correo = :correo WHERE id = :id',
                self::parametros($d) + ['id' => $id]
            );
        } catch (PDOException $ex) {
            throw self::traducir($ex);
        }
    }

    /** Dos personas guardando a la vez la misma identificación: la base de datos lo impide. */
    private static function traducir(PDOException $ex): Throwable
    {
        return $ex->getCode() === '23505' ? new ErrorNegocio('Ya existe un lector con esa identificación.') : $ex;
    }

    public static function cambiarEstado(int $id, bool $activo): void
    {
        if (!$activo) {
            $activos = (int) BaseDatos::valor(
                "SELECT count(*) FROM prestamos WHERE usuario_id = :id AND estado = 'activo'",
                ['id' => $id]
            );
            if ($activos > 0) {
                throw new ErrorNegocio('No se puede desactivar: el lector tiene préstamos activos. Registre primero las devoluciones.');
            }
        }
        BaseDatos::ejecutar('UPDATE usuarios SET activo = :a WHERE id = :id', ['a' => $activo, 'id' => $id]);
    }

    public static function historial(int $id): array
    {
        return BaseDatos::filas(
            'SELECT p.*, e.codigo_inventario, l.titulo FROM prestamos p
             JOIN ejemplares e ON e.id = p.ejemplar_id JOIN libros l ON l.id = e.libro_id
             WHERE p.usuario_id = :id ORDER BY p.fecha_prestamo DESC, p.id DESC',
            ['id' => $id]
        );
    }
}
