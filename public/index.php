<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/inicio.php';
requerirLogin();

$ind = Panel::indicadores();
$ultimos = Prestamo::ultimos(6);

$tituloPagina = 'Panel principal';
$menuActivo = 'panel';
require RAIZ . '/vistas/layouts/cabecera.php';

$tarjetas = [
    ['Títulos', $ind['titulos'], 'book', '', '/libros/'],
    ['Ejemplares físicos', $ind['ejemplares'], 'upc-scan', '', '/ejemplares/'],
    ['Ejemplares disponibles', $ind['disponibles'], 'check-circle', 'verde', '/ejemplares/?estado=disponible'],
    ['Préstamos activos', $ind['activos'], 'journal-arrow-up', 'ambar', '/prestamos/activos.php'],
    ['Préstamos vencidos', $ind['vencidos'], 'alarm', 'rojo', '/prestamos/activos.php?estado=vencido'],
];
?>
<div class="row g-3 mb-4">
    <?php foreach ($tarjetas as [$texto, $valor, $icono, $color, $enlace]): ?>
        <div class="col-6 col-md-4 col-xl">
            <a class="card indicador p-3" href="<?= e($enlace) ?>">
                <div class="d-flex align-items-center gap-3">
                    <div class="icono <?= e($color) ?>"><i class="bi bi-<?= e($icono) ?>"></i></div>
                    <div>
                        <div class="valor"><?= $valor ?></div>
                        <div class="text-muted small"><?= e($texto) ?></div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><a class="acceso-directo" href="/prestamos/nuevo.php"><i class="bi bi-journal-plus"></i> Nuevo préstamo</a></div>
    <div class="col-6 col-lg-3"><a class="acceso-directo" href="/prestamos/devolver.php"><i class="bi bi-journal-check"></i> Registrar devolución</a></div>
    <div class="col-6 col-lg-3"><a class="acceso-directo" href="/libros/"><i class="bi bi-book"></i> Libros</a></div>
    <div class="col-6 col-lg-3"><a class="acceso-directo" href="/usuarios/"><i class="bi bi-people"></i> Lectores (<?= $ind['lectores'] ?>)</a></div>
    <div class="col-6 col-lg-3"><a class="acceso-directo" href="/informes/"><i class="bi bi-bar-chart-line"></i> Informes</a></div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history"></i> Últimos préstamos registrados</span>
        <a href="/prestamos/historial.php" class="small">Ver historial</a>
    </div>
    <?php if (!$ultimos): ?>
        <?php
        $vacioIcono = 'journal-bookmark';
        $vacioTitulo = 'Aún no hay préstamos';
        $vacioTexto = 'Cuando registre el primer préstamo aparecerá aquí.';
        $vacioAccion = '<a class="btn btn-primary" href="/prestamos/nuevo.php"><i class="bi bi-journal-plus"></i> Registrar préstamo</a>';
        require RAIZ . '/vistas/componentes/estado_vacio.php';
        ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Fecha</th><th>Lector</th><th>Libro</th><th>Límite</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($ultimos as $p): ?>
                    <tr>
                        <td><?= e(fecha($p['fecha_prestamo'])) ?></td>
                        <td><?= e($p['lector']) ?></td>
                        <td class="truncar" title="<?= e($p['titulo']) ?>"><?= e($p['titulo']) ?> <small class="text-muted"><?= e($p['codigo_inventario']) ?></small></td>
                        <td><?= e(fecha($p['fecha_limite'])) ?></td>
                        <td><?= insigniaPrestamo($p) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
