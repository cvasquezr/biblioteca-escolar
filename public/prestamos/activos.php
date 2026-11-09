<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirLogin();

$estado = consulta('estado') === 'vencido' ? 'vencido' : 'activo';
$filtros = ['q' => consulta('q'), 'estado' => $estado, 'orden' => 'limite'];
$resultado = Prestamo::buscar($filtros);
$paginacion = $resultado['paginacion'];

$tituloPagina = 'Préstamos activos';
$menuActivo = 'activos';
require RAIZ . '/vistas/layouts/cabecera.php';
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <form method="get" class="row g-2 flex-grow-1" role="search">
        <div class="col-12 col-md-6">
            <input type="search" class="form-control" name="q" value="<?= e($filtros['q']) ?>" placeholder="Buscar por lector, identificación, libro o código" aria-label="Buscar">
        </div>
        <div class="col-6 col-md-3">
            <select class="form-select" name="estado" aria-label="Filtro">
                <option value="activo"<?= $estado === 'activo' ? ' selected' : '' ?>>Todos los activos</option>
                <option value="vencido"<?= $estado === 'vencido' ? ' selected' : '' ?>>Solo vencidos</option>
            </select>
        </div>
        <div class="col-6 col-md-auto d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
            <a class="btn btn-outline-secondary" href="/prestamos/activos.php">Limpiar</a>
        </div>
    </form>
    <a class="btn btn-primary" href="/prestamos/nuevo.php"><i class="bi bi-journal-plus"></i> Nuevo préstamo</a>
</div>

<div class="card">
<?php if (!$resultado['filas']): ?>
    <?php
    $vacioIcono = 'journal-check';
    $vacioTitulo = $estado === 'vencido' ? 'No hay préstamos vencidos' : 'No hay préstamos activos';
    $vacioTexto = 'Cuando se preste un libro aparecerá en esta lista hasta que sea devuelto.';
    $vacioAccion = '<a class="btn btn-primary" href="/prestamos/nuevo.php"><i class="bi bi-journal-plus"></i> Registrar préstamo</a>';
    require RAIZ . '/vistas/componentes/estado_vacio.php';
    ?>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Lector</th><th>Libro</th><th class="d-none d-md-table-cell">Préstamo</th><th>Límite</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($resultado['filas'] as $p): ?>
                <tr class="<?= $p['fecha_limite'] < hoy() ? 'fila-vencida' : '' ?>">
                    <td><?= e($p['lector']) ?><div class="small text-muted"><?= e($p['identificacion']) ?></div></td>
                    <td class="truncar" title="<?= e($p['titulo']) ?>"><?= e($p['titulo']) ?><div class="small text-muted"><?= e($p['codigo_inventario']) ?></div></td>
                    <td class="d-none d-md-table-cell"><?= e(fecha($p['fecha_prestamo'])) ?></td>
                    <td><?= e(fecha($p['fecha_limite'])) ?></td>
                    <td><?= insigniaPrestamo($p) ?></td>
                    <td class="col-acciones"><a class="btn btn-sm btn-primary" href="/prestamos/devolver.php?q=<?= urlencode($p['codigo_inventario']) ?>"><i class="bi bi-journal-check"></i> Devolver</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>
<?php require RAIZ . '/vistas/componentes/paginacion.php'; ?>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
