<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirLogin();

$id = (int) consulta('id');
$u = Usuario::obtener($id);
if ($u === null) {
    abortarPagina(404, 'El lector solicitado no existe.');
}
$historial = Usuario::historial($id);

$tituloPagina = 'Detalle del lector';
$menuActivo = 'lectores';
require RAIZ . '/vistas/layouts/cabecera.php';

$detalles = [
    'Identificación' => $u['identificacion'], 'Tipo' => TIPOS_USUARIO[$u['tipo_usuario']] ?? $u['tipo_usuario'],
    'Grado o curso' => $u['grado'], 'Teléfono' => $u['telefono'], 'Correo' => $u['correo'],
];
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <h2 class="h4 mb-0"><?= e($u['nombres'] . ' ' . $u['apellidos']) ?></h2>
                    <?= insigniaActivo((bool) $u['activo']) ?>
                </div>
                <dl class="row mb-3">
                    <?php foreach ($detalles as $etiqueta => $valor): ?>
                        <dt class="col-sm-5"><?= e($etiqueta) ?></dt>
                        <dd class="col-sm-7"><?= $valor === null || $valor === '' ? '—' : e($valor) ?></dd>
                    <?php endforeach; ?>
                </dl>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary" href="/usuarios/form.php?id=<?= $id ?>"><i class="bi bi-pencil"></i> Editar</a>
                    <form method="post" action="/usuarios/estado.php" class="m-0"
                          <?= $u['activo'] ? 'data-confirmar="¿Desactivar a este lector? No podrá recibir nuevos préstamos, pero se conserva su historial."' : '' ?>>
                        <?= campoCsrf() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="activo" value="<?= $u['activo'] ? '0' : '1' ?>">
                        <?php if ($u['activo']): ?>
                            <button class="btn btn-outline-danger" type="submit"><i class="bi bi-slash-circle"></i> Desactivar</button>
                        <?php else: ?>
                            <button class="btn btn-outline-success" type="submit"><i class="bi bi-check-circle"></i> Reactivar</button>
                        <?php endif; ?>
                    </form>
                    <a class="btn btn-outline-secondary" href="/usuarios/"><i class="bi bi-arrow-left"></i> Volver</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history"></i> Historial de préstamos (<?= count($historial) ?>)</span>
                <?php if ($u['activo']): ?><a class="btn btn-sm btn-primary" href="/prestamos/nuevo.php?lector=<?= $id ?>"><i class="bi bi-journal-plus"></i> Nuevo préstamo</a><?php endif; ?>
            </div>
            <?php if (!$historial): ?>
                <?php
                $vacioIcono = 'journal-bookmark';
                $vacioTitulo = 'Sin préstamos';
                $vacioTexto = 'Este lector todavía no ha pedido libros.';
                require RAIZ . '/vistas/componentes/estado_vacio.php';
                ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Libro</th><th>Préstamo</th><th>Límite</th><th>Devolución</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($historial as $p): ?>
                            <tr>
                                <td class="truncar" title="<?= e($p['titulo']) ?>"><?= e($p['titulo']) ?> <small class="text-muted"><?= e($p['codigo_inventario']) ?></small></td>
                                <td><?= e(fecha($p['fecha_prestamo'])) ?></td>
                                <td><?= e(fecha($p['fecha_limite'])) ?></td>
                                <td><?= e(fecha($p['fecha_devolucion'])) ?></td>
                                <td><?= insigniaPrestamo($p) ?></td>
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
