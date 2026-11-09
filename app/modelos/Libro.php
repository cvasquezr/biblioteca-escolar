<?php

declare(strict_types=1);

final class Libro
{
    /** Condición SQL para buscar sin importar mayúsculas ni tildes. */
    private static function condiciones(array $f, array &$params): string
    {
        $where = ['TRUE'];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(unaccent(l.titulo) ILIKE unaccent(:q) OR unaccent(l.autor) ILIKE unaccent(:q)
                         OR l.isbn ILIKE :q)';
            $params['q'] = '%' . addcslashes($f['q'], '%_\\') . '%';
        }
        if (($f['categoria'] ?? '') !== '') {
            $where[] = 'l.categoria = :categoria';
            $params['categoria'] = $f['categoria'];
        }
        $estado = $f['estado'] ?? 'activo';
        if ($estado === 'activo') {
            $where[] = 'l.activo';
        } elseif ($estado === 'inactivo') {
            $where[] = 'NOT l.activo';
        }
        return implode(' AND ', $where);
    }

    /** @return array{filas:array, paginacion:array} */
    public static function buscar(array $filtros, int $porPagina = 15): array
    {
        $params = [];
        $where = self::condiciones($filtros, $params);
        $total = (int) BaseDatos::valor("SELECT count(*) FROM libros l WHERE $where", $params);
        $pag = calcularPaginacion($total, $porPagina);

        $sql = "SELECT l.*,
                  (SELECT count(*) FROM ejemplares e WHERE e.libro_id = l.id) AS total_ejemplares,
                  (SELECT count(*) FROM ejemplares e WHERE e.libro_id = l.id AND e.estado = 'disponible'
                     AND NOT EXISTS (SELECT 1 FROM prestamos p WHERE p.ejemplar_id = e.id AND p.estado = 'activo')) AS disponibles
                FROM libros l WHERE $where
                ORDER BY lower(l.titulo), l.id
                LIMIT :lim OFFSET :off";
        $st = BaseDatos::conexion()->prepare($sql);
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
        return BaseDatos::fila('SELECT * FROM libros WHERE id = :id', ['id' => $id]);
    }

    public static function categorias(): array
    {
        return array_column(BaseDatos::filas(
            "SELECT DISTINCT categoria FROM libros WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria"
        ), 'categoria');
    }

    /** @return array<string,string> errores por campo */
    public static function validar(array $d): array
    {
        $e = [];
        if ($d['titulo'] === '' || longitud($d['titulo']) > 255) {
            $e['titulo'] = 'El título es obligatorio (máximo 255 caracteres).';
        }
        if ($d['autor'] === '' || longitud($d['autor']) > 255) {
            $e['autor'] = 'El autor es obligatorio (máximo 255 caracteres).';
        }
        if (longitud($d['editorial']) > 150) {
            $e['editorial'] = 'La editorial no puede superar 150 caracteres.';
        }
        if ($d['paginas'] !== '' && (!ctype_digit($d['paginas']) || (int) $d['paginas'] < 1 || (int) $d['paginas'] > 100000)) {
            $e['paginas'] = 'El número de páginas debe ser un entero positivo.';
        }
        if ($d['isbn'] !== '' && !preg_match('/^[0-9Xx][0-9Xx\- ]{8,18}[0-9Xx]$/', $d['isbn'])) {
            $e['isbn'] = 'El ISBN solo puede tener números, guiones y la letra X (10 a 20 caracteres).';
        }
        if (longitud($d['categoria']) > 80) {
            $e['categoria'] = 'La categoría no puede superar 80 caracteres.';
        }
        if (longitud($d['ubicacion']) > 80) {
            $e['ubicacion'] = 'La ubicación no puede superar 80 caracteres.';
        }
        if (longitud($d['descripcion']) > 3000) {
            $e['descripcion'] = 'La descripción no puede superar 3000 caracteres.';
        }
        return $e;
    }

    private static function parametros(array $d): array
    {
        return [
            'titulo' => $d['titulo'],
            'autor' => $d['autor'],
            'editorial' => nuloSiVacio($d['editorial']),
            'paginas' => $d['paginas'] === '' ? null : (int) $d['paginas'],
            'isbn' => nuloSiVacio($d['isbn']),
            'categoria' => nuloSiVacio($d['categoria']),
            'descripcion' => nuloSiVacio($d['descripcion']),
            'ubicacion' => nuloSiVacio($d['ubicacion']),
        ];
    }

    public static function crear(array $d): int
    {
        return (int) BaseDatos::valor(
            'INSERT INTO libros (titulo, autor, editorial, paginas, isbn, categoria, descripcion, ubicacion)
             VALUES (:titulo, :autor, :editorial, :paginas, :isbn, :categoria, :descripcion, :ubicacion) RETURNING id',
            self::parametros($d)
        );
    }

    public static function actualizar(int $id, array $d): void
    {
        BaseDatos::ejecutar(
            'UPDATE libros SET titulo = :titulo, autor = :autor, editorial = :editorial, paginas = :paginas,
                    isbn = :isbn, categoria = :categoria, descripcion = :descripcion, ubicacion = :ubicacion
             WHERE id = :id',
            self::parametros($d) + ['id' => $id]
        );
    }

    /** Desactiva o reactiva. No se desactiva si algún ejemplar está prestado. */
    public static function cambiarEstado(int $id, bool $activo): void
    {
        if (!$activo) {
            $prestados = (int) BaseDatos::valor(
                "SELECT count(*) FROM prestamos p JOIN ejemplares e ON e.id = p.ejemplar_id
                 WHERE e.libro_id = :id AND p.estado = 'activo'",
                ['id' => $id]
            );
            if ($prestados > 0) {
                throw new ErrorNegocio('No se puede desactivar: el libro tiene ejemplares prestados. Registre primero las devoluciones.');
            }
        }
        BaseDatos::ejecutar('UPDATE libros SET activo = :a WHERE id = :id', ['a' => $activo, 'id' => $id]);
    }
}
