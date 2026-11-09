<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirLogin();

$id = (int) consulta('id');
$libro = Libro::obtener($id);
if ($libro === null) {
    abortarPagina(404, 'El libro solicitado no existe.');
}
$ejemplares = Ejemplar::porLibro($id);

$tituloPagina = 'Detalle del libro';
$menuActivo = 'libros';
require RAIZ . '/vistas/layouts/cabecera.php';

$detalles = [
    'Autor' => $libro['autor'], 'Editorial' => $libro['editorial'], 'Páginas' => $libro['paginas'],
    'ISBN' => $libro['isbn'], 'Categoría' => $libro['categoria'], 'Ubicación' => $libro['ubicacion'],
];
?>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <h2 class="h4 mb-0"><?= e($libro['titulo']) ?></h2>
                    <?= insigniaActivo((bool) $libro['activo']) ?>
                </div>
                <dl class="row mb-3">
                    <?php foreach ($detalles as $etiqueta => $valor): ?>
                        <dt class="col-sm-4"><?= e($etiqueta) ?></dt>
                        <dd class="col-sm-8"><?= $valor === null || $valor === '' ? '—' : e($valor) ?></dd>
                    <?php endforeach; ?>
                    <dt class="col-sm-4">Descripción</dt>
                    <dd class="col-sm-8"><?= $libro['descripcion'] ? nl2br(e($libro['descripcion'])) : '—' ?></dd>
                    <dt class="col-sm-4">Registrado</dt>
                    <dd class="col-sm-8"><?= e(fechaHora($libro['created_at'])) ?></dd>
                </dl>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary" href="/libros/form.php?id=<?= $id ?>"><i class="bi bi-pencil"></i> Editar</a>
                    <form method="post" action="/libros/estado.php" class="m-0"
                          <?= $libro['activo'] ? 'data-confirmar="¿Desactivar este libro? Dejará de aparecer para nuevos préstamos, pero se conserva su historial."' : '' ?>>
                        <?= campoCsrf() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="activo" value="<?= $libro['activo'] ? '0' : '1' ?>">
                        <?php if ($libro['activo']): ?>
                            <button class="btn btn-outline-danger" type="submit"><i class="bi bi-slash-circle"></i> Desactivar</button>
                        <?php else: ?>
                            <button class="btn btn-outline-success" type="submit"><i class="bi bi-check-circle"></i> Reactivar</button>
                        <?php endif; ?>
                    </form>
                    <a class="btn btn-outline-secondary" href="/libros/"><i class="bi bi-arrow-left"></i> Volver</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-plus-circle"></i> Agregar ejemplares</div>
            <div class="card-body">
                <form method="post" action="/ejemplares/crear.php" class="row g-2 align-items-end">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="libro_id" value="<?= $id ?>">
                    <div class="col-4 col-md-2">
                        <label class="form-label" for="cantidad">Cantidad</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" value="1" min="1" max="100" required>
                    </div>
                    <div class="col-8 col-md-4">
                        <label class="form-label" for="codigo">Código (opcional)</label>
                        <input class="form-control" id="codigo" name="codigo" maxlength="40" placeholder="Automático">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="observaciones">Observaciones</label>
                        <input class="form-control" id="observaciones" name="observaciones" maxlength="500">
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg"></i> Crear</button>
                    </div>
                </form>
                <div class="form-text">Sin código, el sistema genera uno como <code>EJ-<?= str_pad((string) $id, 5, '0', STR_PAD_LEFT) ?>-01</code>.</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-upc-scan"></i> Ejemplares (<?= count($ejemplares) ?>)</div>
            <?php if (!$ejemplares): ?>
                <?php
                $vacioIcono = 'upc-scan';
                $vacioTitulo = 'Este libro no tiene ejemplares';
                $vacioTexto = 'Use el formulario de arriba para registrar las copias físicas.';
                require RAIZ . '/vistas/componentes/estado_vacio.php';
                ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Código</th><th>Estado</th><th>Observaciones</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($ejemplares as $ej): ?>
                            <tr>
                                <td><code><?= e($ej['codigo_inventario']) ?></code></td>
                                <td>
                                    <?= insigniaEjemplar($ej['estado_efectivo']) ?>
                                    <?php if ($ej['prestado_a']): ?><div class="small text-muted"><?= e($ej['prestado_a']) ?> · hasta <?= e(fecha($ej['fecha_limite'])) ?></div><?php endif; ?>
                                </td>
                                <td class="truncar"><?= e($ej['observaciones'] ?? '—') ?></td>
                                <td class="col-acciones"><a class="btn btn-sm btn-outline-secondary" href="/ejemplares/editar.php?id=<?= (int) $ej['id'] ?>"><i class="bi bi-pencil"></i> Editar</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
