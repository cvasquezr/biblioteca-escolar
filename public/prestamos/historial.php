<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirLogin();

$filtros = ['q' => consulta('q'), 'estado' => consulta('estado'), 'desde' => consulta('desde'), 'hasta' => consulta('hasta')];
$resultado = Prestamo::buscar($filtros);
$paginacion = $resultado['paginacion'];

$tituloPagina = 'Historial de préstamos';
$menuActivo = 'historial';
require RAIZ . '/vistas/layouts/cabecera.php';
?>
<form method="get" class="row g-2 mb-3" role="search">
    <div class="col-12 col-lg-4">
        <input type="search" class="form-control" name="q" value="<?= e($filtros['q']) ?>" placeholder="Lector, identificación, libro o código" aria-label="Buscar">
    </div>
    <div class="col-6 col-lg-2">
        <select class="form-select" name="estado" aria-label="Estado">
            <?php foreach (['' => 'Todos', 'activo' => 'Activos', 'vencido' => 'Vencidos', 'devuelto' => 'Devueltos'] as $v => $t): ?>
                <option value="<?= $v ?>"<?= $v === $filtros['estado'] ? ' selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-lg-2">
        <input type="date" class="form-control" name="desde" value="<?= e($filtros['desde']) ?>" aria-label="Desde">
    </div>
    <div class="col-6 col-lg-2">
        <input type="date" class="form-control" name="hasta" value="<?= e($filtros['hasta']) ?>" aria-label="Hasta">
    </div>
    <div class="col-6 col-lg-auto d-flex gap-2">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Filtrar</button>
        <a class="btn btn-outline-secondary" href="/prestamos/historial.php">Limpiar</a>
    </div>
</form>

<div class="card">
<?php if (!$resultado['filas']): ?>
    <?php
    $vacioIcono = 'clock-history';
    $vacioTitulo = 'No hay préstamos en el historial';
    $vacioTexto = 'Ajuste los filtros o registre un préstamo.';
    require RAIZ . '/vistas/componentes/estado_vacio.php';
    ?>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Préstamo</th><th>Lector</th><th>Libro</th><th class="d-none d-md-table-cell">Límite</th><th>Devolución</th><th>Estado</th><th class="d-none d-xl-table-cell">Registró</th></tr></thead>
            <tbody>
            <?php foreach ($resultado['filas'] as $p): ?>
                <tr>
                    <td><?= e(fecha($p['fecha_prestamo'])) ?></td>
                    <td><?= e($p['lector']) ?><div class="small text-muted"><?= e($p['identificacion']) ?></div></td>
                    <td class="truncar" title="<?= e($p['titulo']) ?>"><?= e($p['titulo']) ?><div class="small text-muted"><?= e($p['codigo_inventario']) ?></div></td>
                    <td class="d-none d-md-table-cell"><?= e(fecha($p['fecha_limite'])) ?></td>
                    <td><?= e(fecha($p['fecha_devolucion'])) ?></td>
                    <td><?= insigniaPrestamo($p) ?></td>
                    <td class="d-none d-xl-table-cell"><?= e($p['registrado_por']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>
<?php require RAIZ . '/vistas/componentes/paginacion.php'; ?>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
