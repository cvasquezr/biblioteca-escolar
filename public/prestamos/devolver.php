<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirRol('administrador', 'bibliotecario');

if (esPost()) {
    validarCsrf();
    $volver = '/prestamos/devolver.php' . (post('q') !== '' ? '?q=' . urlencode(post('q')) : '');
    try {
        DevolucionService::registrar(
            (int) post('prestamo_id'),
            post('fecha_devolucion'),
            post('estado_ejemplar'),
            mb_substr(post('observaciones'), 0, 300)
        );
        flash('success', 'Devolución registrada. El ejemplar quedó actualizado.');
    } catch (ErrorNegocio $e) {
        flash('danger', $e->getMessage());
    }
    redirigir($volver);
}

$q = consulta('q');
$resultado = Prestamo::buscar(['q' => $q, 'estado' => 'activo', 'orden' => 'limite']);
$paginacion = $resultado['paginacion'];

$tituloPagina = 'Registrar devolución';
$menuActivo = 'devolver';
require RAIZ . '/vistas/layouts/cabecera.php';
?>
<form method="get" class="row g-2 mb-3" role="search">
    <div class="col-12 col-md-7">
        <input type="search" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Busque el préstamo por lector, identificación, libro o código del ejemplar" aria-label="Buscar préstamo activo" autofocus>
    </div>
    <div class="col-12 col-md-auto d-flex gap-2">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
        <a class="btn btn-outline-secondary" href="/prestamos/devolver.php">Limpiar</a>
    </div>
</form>

<div class="card">
<?php if (!$resultado['filas']): ?>
    <?php
    $vacioIcono = 'journal-check';
    $vacioTitulo = 'No hay préstamos activos para devolver';
    $vacioTexto = $q !== '' ? 'No hay coincidencias con la búsqueda.' : 'Todos los libros están en la biblioteca.';
    require RAIZ . '/vistas/componentes/estado_vacio.php';
    ?>
<?php else: ?>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Lector</th><th>Libro</th><th>Fechas</th><th>Registrar devolución</th></tr></thead>
            <tbody>
            <?php foreach ($resultado['filas'] as $p): ?>
                <tr class="<?= $p['fecha_limite'] < hoy() ? 'fila-vencida' : '' ?>">
                    <td><?= e($p['lector']) ?><div class="small text-muted"><?= e($p['identificacion']) ?></div></td>
                    <td><?= e($p['titulo']) ?><div class="small text-muted">Código <?= e($p['codigo_inventario']) ?></div></td>
                    <td class="small">
                        Prestado: <?= e(fecha($p['fecha_prestamo'])) ?><br>
                        Límite: <?= e(fecha($p['fecha_limite'])) ?> <?= insigniaPrestamo($p) ?>
                    </td>
                    <td>
                        <form method="post" class="row g-1 align-items-end"
                              data-confirmar="¿Registrar la devolución de «<?= e($p['titulo']) ?>» de <?= e($p['lector']) ?>?">
                            <?= campoCsrf() ?>
                            <input type="hidden" name="prestamo_id" value="<?= (int) $p['id'] ?>">
                            <input type="hidden" name="q" value="<?= e($q) ?>">
                            <div class="col-6 col-xl-4">
                                <label class="form-label small mb-0" for="fd<?= (int) $p['id'] ?>">Fecha</label>
                                <input type="date" class="form-control form-control-sm" id="fd<?= (int) $p['id'] ?>" name="fecha_devolucion"
                                       value="<?= e(hoy()) ?>" min="<?= e($p['fecha_prestamo']) ?>" max="<?= e(hoy()) ?>" required>
                            </div>
                            <div class="col-6 col-xl-4">
                                <label class="form-label small mb-0" for="ee<?= (int) $p['id'] ?>">Estado del libro</label>
                                <select class="form-select form-select-sm" id="ee<?= (int) $p['id'] ?>" name="estado_ejemplar">
                                    <option value="disponible">Buen estado</option>
                                    <option value="danado">Dañado</option>
                                    <option value="perdido">Perdido</option>
                                </select>
                            </div>
                            <div class="col-12 col-xl-4 d-grid">
                                <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-journal-check"></i> Devolver</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>
<?php require RAIZ . '/vistas/componentes/paginacion.php'; ?>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
