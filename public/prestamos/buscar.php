<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';

// Endpoint JSON para el autocompletado de la pantalla de préstamos
if (cuentaActual() === null) {
    http_response_code(401);
    header('Content-Type: application/json; charset=UTF-8');
    echo '[]';
    exit;
}

$tipo = consulta('tipo');
$q = mb_substr(consulta('q'), 0, 80);
$patron = '%' . addcslashes($q, '%_\\') . '%';
$salida = [];

if ($q !== '' && $tipo === 'lector') {
    $filas = BaseDatos::filas(
        "SELECT id, identificacion, nombres, apellidos, tipo_usuario, grado FROM usuarios
         WHERE activo AND (identificacion ILIKE :q OR unaccent(nombres || ' ' || apellidos) ILIKE unaccent(:q))
         ORDER BY lower(apellidos), lower(nombres) LIMIT 10",
        ['q' => $patron]
    );
    foreach ($filas as $f) {
        $salida[] = [
            'id' => (int) $f['id'],
            'texto' => $f['nombres'] . ' ' . $f['apellidos'],
            'detalle' => $f['identificacion'] . ' · ' . (TIPOS_USUARIO[$f['tipo_usuario']] ?? '') . ($f['grado'] ? ' · ' . $f['grado'] : ''),
        ];
    }
} elseif ($q !== '' && $tipo === 'ejemplar') {
    $filas = BaseDatos::filas(
        "SELECT e.id, e.codigo_inventario, l.titulo, l.autor
         FROM ejemplares e JOIN libros l ON l.id = e.libro_id
         WHERE e.estado = 'disponible' AND l.activo
           AND NOT EXISTS (SELECT 1 FROM prestamos p WHERE p.ejemplar_id = e.id AND p.estado = 'activo')
           AND (e.codigo_inventario ILIKE :q OR unaccent(l.titulo) ILIKE unaccent(:q) OR unaccent(l.autor) ILIKE unaccent(:q))
         ORDER BY lower(l.titulo), e.codigo_inventario LIMIT 10",
        ['q' => $patron]
    );
    foreach ($filas as $f) {
        $salida[] = [
            'id' => (int) $f['id'],
            'texto' => $f['titulo'] . ' (' . $f['codigo_inventario'] . ')',
            'detalle' => $f['autor'],
        ];
    }
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
