<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirRol('administrador', 'bibliotecario');

$tipo = consulta('tipo');
if (!isset(Informe::TIPOS[$tipo])) {
    $tipo = 'inventario';
}
$desde = consulta('desde');
$hasta = consulta('hasta');
$errorFechas = null;

// Los informes por periodo traen un rango sugerido si no se indicó ninguno
if ($desde === '' && $hasta === '' && !isset($_GET['filtrar']) && ($rango = Informe::rangoPorDefecto($tipo)) !== null) {
    [$desde, $hasta] = $rango;
}
if (($desde !== '' && !fechaValida($desde)) || ($hasta !== '' && !fechaValida($hasta))) {
    $errorFechas = 'Las fechas indicadas no son válidas.';
    $desde = $hasta = '';
} elseif ($desde !== '' && $hasta !== '' && $desde > $hasta) {
    $errorFechas = 'La fecha "desde" no puede ser posterior a la fecha "hasta".';
    $desde = $hasta = '';
}

$definicion = Informe::definir($tipo, $desde, $hasta);

if (consulta('formato') === 'csv' && !$errorFechas) {
    Informe::exportarCsv($tipo, $definicion);
}

$resultado = Informe::pagina($definicion);
$paginacion = $resultado['paginacion'];

$tituloPagina = 'Informes';
$menuActivo = 'informes';
require RAIZ . '/vistas/layouts/cabecera.php';
?>
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="filtrar" value="1">
            <div class="col-12 col-md-4">
                <label class="form-label" for="tipo">Informe</label>
                <select class="form-select" id="tipo" name="tipo">
                    <?php foreach (Informe::TIPOS as $v => $t): ?>
                        <option value="<?= $v ?>"<?= $v === $tipo ? ' selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="desde">Desde</label>
                <input type="date" class="form-control" id="desde" name="desde" value="<?= e($desde) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="hasta">Hasta</label>
                <input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($hasta) ?>">
            </div>
            <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Generar</button>
                <a class="btn btn-outline-secondary" href="/informes/">Limpiar</a>
                <a class="btn btn-outline-success" href="<?= e(urlCon(['formato' => 'csv', 'pagina' => ''])) ?>"><i class="bi bi-filetype-csv"></i> Exportar CSV</a>
            </div>
        </form>
        <div class="form-text mt-2">
            Las fechas filtran por la fecha de <strong><?= e(Informe::FILTRO_FECHA[$tipo]) ?></strong>. Déjelas vacías para ver todo el historial.
        </div>
        <?php if ($errorFechas): ?><div class="alert alert-danger mt-3 mb-0"><?= e($errorFechas) ?></div><?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-bar-chart-line"></i> <?= e(Informe::TIPOS[$tipo]) ?></span>
        <span class="text-muted small fw-normal"><?= (int) $paginacion['total'] ?> filas</span>
    </div>
    <?php if (!$resultado['filas']): ?>
        <?php
        $vacioIcono = 'bar-chart-line';
        $vacioTitulo = 'Sin resultados';
        $vacioTexto = 'No hay datos para este informe con los filtros indicados.';
        require RAIZ . '/vistas/componentes/estado_vacio.php';
        ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><?php foreach ($definicion['columnas'] as $titulo): ?><th><?= e($titulo) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                <?php foreach ($resultado['filas'] as $fila): ?>
                    <tr>
                        <?php foreach (array_keys($definicion['columnas']) as $col): ?>
                            <td><?= e($fila[$col]) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require RAIZ . '/vistas/componentes/paginacion.php'; ?>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
