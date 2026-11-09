<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirRol('administrador', 'bibliotecario');

$archivos = ImportadorExcel::archivosDisponibles();
$accion = esPost() ? post('accion') : '';

// ---- Subir un archivo nuevo ----
if ($accion === 'subir') {
    validarCsrf();
    try {
        $nombre = ImportadorExcel::guardarSubida($_FILES['archivo'] ?? []);
        flash('success', 'Archivo recibido. Revise el informe previo antes de importar.');
        redirigir('/importacion/?archivo=' . urlencode($nombre));
    } catch (ErrorNegocio $e) {
        flash('danger', $e->getMessage());
        redirigir('/importacion/');
    }
}

// ---- Confirmar la importación ----
if ($accion === 'importar') {
    validarCsrf();
    $ruta = ImportadorExcel::rutaSegura(post('archivo'));
    if ($ruta === null || post('confirmo') !== '1') {
        flash('danger', 'Debe elegir un archivo y marcar la confirmación para importar.');
        redirigir('/importacion/');
    }
    try {
        $r = ImportadorExcel::importar($ruta);
        flash('success', "Importación terminada: {$r['libros']} libros nuevos y {$r['ejemplares']} ejemplares creados.");
    } catch (ErrorNegocio $e) {
        flash('danger', $e->getMessage());
    }
    redirigir('/libros/');
}

// ---- Análisis (informe previo) o comparación ----
$seleccionado = consulta('archivo');
$analisis = null;
$comparacion = null;
$error = null;
try {
    if ($seleccionado !== '') {
        $ruta = ImportadorExcel::rutaSegura($seleccionado);
        if ($ruta === null) {
            throw new ErrorNegocio('El archivo seleccionado no existe.');
        }
        $analisis = ImportadorExcel::analizar($ruta);
    }
    if (consulta('comparar_a') !== '' && consulta('comparar_b') !== '') {
        $rutaA = ImportadorExcel::rutaSegura(consulta('comparar_a'));
        $rutaB = ImportadorExcel::rutaSegura(consulta('comparar_b'));
        if ($rutaA === null || $rutaB === null) {
            throw new ErrorNegocio('Alguno de los archivos a comparar no existe.');
        }
        $comparacion = ImportadorExcel::comparar($rutaA, $rutaB);
    }
} catch (ErrorNegocio $e) {
    $error = $e->getMessage();
}

$tituloPagina = 'Importar inventario desde Excel';
$menuActivo = 'importacion';
require RAIZ . '/vistas/layouts/cabecera.php';

/** Tabla sencilla de registros del informe. */
function tablaRegistros(array $filas, array $columnas): void
{
    echo '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr>';
    foreach ($columnas as $t) {
        echo '<th>' . e($t) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($filas as $f) {
        echo '<tr>';
        foreach (array_keys($columnas) as $c) {
            echo '<td>' . e(is_array($f[$c] ?? '') ? implode(', ', $f[$c]) : ($f[$c] ?? '')) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
?>
<?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-octagon"></i> <?= e($error) ?></div><?php endif; ?>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    Primero se muestra un <strong>informe previo</strong> y nada se guarda hasta que usted confirme.
    Las filas con el mismo título, autor y editorial se importan como <strong>ejemplares de un mismo libro</strong>.
    Los libros cuyo título y autor ya existen se omiten, por lo que repetir la importación no duplica nada.
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-file-earmark-spreadsheet"></i> 1. Elegir el archivo</div>
            <div class="card-body">
                <?php if ($archivos): ?>
                    <form method="get" class="mb-3">
                        <label class="form-label" for="archivo">Archivos disponibles en <code>database/import/</code></label>
                        <div class="input-group">
                            <select class="form-select" id="archivo" name="archivo">
                                <?php foreach ($archivos as $a): ?>
                                    <option value="<?= e($a) ?>"<?= $a === $seleccionado ? ' selected' : '' ?>><?= e($a) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-clipboard-data"></i> Ver informe previo</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-muted">No hay archivos .xlsx todavía. Copie el Excel a <code>database/import/</code> o súbalo aquí.</p>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="accion" value="subir">
                    <label class="form-label" for="subida">O suba un archivo (.xlsx, máximo 5 MB)</label>
                    <div class="input-group">
                        <input type="file" class="form-control" id="subida" name="archivo" accept=".xlsx" required>
                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-upload"></i> Subir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php if (count($archivos) >= 2): ?>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-intersect"></i> Comparar dos inventarios</div>
            <div class="card-body">
                <form method="get" class="row g-2">
                    <div class="col-sm-6">
                        <label class="form-label" for="comparar_a">Archivo A</label>
                        <select class="form-select" id="comparar_a" name="comparar_a">
                            <?php foreach ($archivos as $a): ?><option value="<?= e($a) ?>"<?= $a === consulta('comparar_a') ? ' selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="comparar_b">Archivo B</label>
                        <select class="form-select" id="comparar_b" name="comparar_b">
                            <?php foreach ($archivos as $i => $a): ?><option value="<?= e($a) ?>"<?= $a === consulta('comparar_b') ? ' selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-intersect"></i> Comparar</button></div>
                </form>
                <p class="form-text mb-0 mt-2">Genera un informe de diferencias para que la persona responsable decida qué inventario usar.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($comparacion): ?>
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-intersect"></i> Informe de diferencias</div>
    <div class="card-body">
        <div class="row text-center g-3 mb-3">
            <div class="col-6 col-md-3"><div class="fs-4 fw-bold"><?= (int) $comparacion['a']['leidas'] ?></div><div class="small text-muted">filas en A (<?= e(consulta('comparar_a')) ?>)</div></div>
            <div class="col-6 col-md-3"><div class="fs-4 fw-bold"><?= (int) $comparacion['b']['leidas'] ?></div><div class="small text-muted">filas en B (<?= e(consulta('comparar_b')) ?>)</div></div>
            <div class="col-6 col-md-3"><div class="fs-4 fw-bold"><?= count($comparacion['solo_a']) ?></div><div class="small text-muted">títulos solo en A</div></div>
            <div class="col-6 col-md-3"><div class="fs-4 fw-bold"><?= count($comparacion['solo_b']) ?></div><div class="small text-muted">títulos solo en B</div></div>
        </div>
        <p class="small text-muted">Se comparan por título + autor, ignorando mayúsculas y tildes. No se asume que ninguno sea el correcto.</p>
        <?php foreach ([['solo_a', 'Solo en A', 'A'], ['solo_b', 'Solo en B', 'B']] as [$clave, $titulo]): ?>
            <h3 class="h6 mt-3"><?= e($titulo) ?> (<?= count($comparacion[$clave]) ?>)</h3>
            <?php if ($comparacion[$clave]): tablaRegistros($comparacion[$clave], ['fila' => 'Fila', 'titulo' => 'Título', 'autor' => 'Autor', 'editorial' => 'Editorial', 'paginas' => 'Págs.']);
            else: ?><p class="text-muted small">Ninguno.</p><?php endif; ?>
        <?php endforeach; ?>
        <h3 class="h6 mt-3">En ambos, con diferencias (<?= count($comparacion['diferencias']) ?>)</h3>
        <?php if ($comparacion['diferencias']): tablaRegistros($comparacion['diferencias'], ['titulo' => 'Título', 'autor' => 'Autor', 'motivo' => 'Diferencia']);
        else: ?><p class="text-muted small">Ninguna.</p><?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($analisis): ?>
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-clipboard-data"></i> 2. Informe previo: <?= e($seleccionado) ?></div>
    <div class="card-body">
        <div class="row text-center g-3 mb-3">
            <div class="col-6 col-md-2"><div class="fs-4 fw-bold"><?= (int) $analisis['leidas'] ?></div><div class="small text-muted">filas leídas</div></div>
            <div class="col-6 col-md-2"><div class="fs-4 fw-bold"><?= (int) $analisis['libros_a_crear'] ?></div><div class="small text-muted">libros nuevos</div></div>
            <div class="col-6 col-md-2"><div class="fs-4 fw-bold"><?= (int) $analisis['ejemplares_a_crear'] ?></div><div class="small text-muted">ejemplares nuevos</div></div>
            <div class="col-6 col-md-2"><div class="fs-4 fw-bold"><?= count($analisis['ya_existen']) ?></div><div class="small text-muted">ya existen (se omiten)</div></div>
            <div class="col-6 col-md-2"><div class="fs-4 fw-bold text-danger"><?= count($analisis['incompletas']) ?></div><div class="small text-muted">requieren revisión</div></div>
            <div class="col-6 col-md-2"><div class="fs-4 fw-bold"><?= (int) $analisis['vacias'] ?></div><div class="small text-muted">filas vacías</div></div>
        </div>

        <?php if ($analisis['incompletas']): ?>
            <h3 class="h6 text-danger"><i class="bi bi-exclamation-triangle"></i> Filas que requieren revisión (no se importarán)</h3>
            <?php tablaRegistros($analisis['incompletas'], ['fila' => 'Fila', 'titulo' => 'Título', 'autor' => 'Autor', 'paginas' => 'Págs.', 'motivo' => 'Problema']); ?>
        <?php endif; ?>

        <?php if ($analisis['agrupados']): ?>
            <h3 class="h6 mt-3"><i class="bi bi-stack"></i> Libros con varias copias en el archivo (<?= count($analisis['agrupados']) ?>)</h3>
            <p class="small text-muted">Filas idénticas en título, autor y editorial se crearán como varios ejemplares de un mismo libro.</p>
            <?php tablaRegistros($analisis['agrupados'], ['titulo' => 'Título', 'autor' => 'Autor', 'ejemplares' => 'Ejemplares', 'filas' => 'Filas']); ?>
        <?php endif; ?>

        <?php if ($analisis['ya_existen']): ?>
            <details class="mt-3"><summary class="fw-semibold">Libros que ya existen en la base de datos (<?= count($analisis['ya_existen']) ?>)</summary>
                <?php tablaRegistros($analisis['ya_existen'], ['fila' => 'Fila', 'titulo' => 'Título', 'autor' => 'Autor']); ?>
            </details>
        <?php endif; ?>

        <?php if ($analisis['nuevos']): ?>
            <details class="mt-3"><summary class="fw-semibold">Libros que se crearán (<?= count($analisis['nuevos']) ?>)</summary>
                <?php tablaRegistros($analisis['nuevos'], ['titulo' => 'Título', 'autor' => 'Autor', 'editorial' => 'Editorial', 'paginas' => 'Págs.', 'ejemplares' => 'Ejemplares']); ?>
            </details>
        <?php endif; ?>
    </div>
    <div class="card-footer bg-white">
        <?php if ($analisis['libros_a_crear'] === 0): ?>
            <span class="text-muted">No hay libros nuevos para importar.</span>
        <?php else: ?>
            <form method="post" data-confirmar="Se crearán <?= (int) $analisis['libros_a_crear'] ?> libros y <?= (int) $analisis['ejemplares_a_crear'] ?> ejemplares. ¿Desea continuar?">
                <?= campoCsrf() ?>
                <input type="hidden" name="accion" value="importar">
                <input type="hidden" name="archivo" value="<?= e($seleccionado) ?>">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmo" name="confirmo" value="1" required>
                    <label class="form-check-label" for="confirmo">
                        Revisé el informe y confirmo que este es el inventario que se debe importar.
                    </label>
                </div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-box-arrow-in-down"></i> Importar <?= (int) $analisis['libros_a_crear'] ?> libros</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
