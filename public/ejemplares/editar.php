<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirRol('administrador', 'bibliotecario');

$id = (int) (consulta('id') ?: ($_POST['id'] ?? 0));
$ejemplar = Ejemplar::obtener($id);
if ($ejemplar === null) {
    abortarPagina(404, 'El ejemplar solicitado no existe.');
}
$prestado = $ejemplar['estado_efectivo'] === 'prestado';
$estado = $ejemplar['estado'];
$observaciones = (string) ($ejemplar['observaciones'] ?? '');
$error = null;

if (esPost()) {
    validarCsrf();
    $estado = post('estado');
    $observaciones = mb_substr(post('observaciones'), 0, 500);
    try {
        Ejemplar::actualizar($id, $estado, nuloSiVacio($observaciones));
        flash('success', 'El ejemplar fue actualizado.');
        redirigir('/libros/ver.php?id=' . (int) $ejemplar['libro_id']);
    } catch (ErrorNegocio $e) {
        $error = $e->getMessage();
    }
}

$tituloPagina = 'Editar ejemplar';
$menuActivo = 'ejemplares';
require RAIZ . '/vistas/layouts/cabecera.php';
?>
<div class="card col-lg-7">
    <div class="card-body">
        <p class="mb-1"><strong><?= e($ejemplar['titulo']) ?></strong> · <?= e($ejemplar['autor']) ?></p>
        <p class="text-muted">Código de inventario: <code><?= e($ejemplar['codigo_inventario']) ?></code> · Situación actual: <?= insigniaEjemplar($ejemplar['estado_efectivo']) ?></p>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($prestado): ?>
            <div class="alert alert-info"><i class="bi bi-info-circle"></i> El ejemplar está prestado. Solo puede editar las observaciones; para cambiar su estado registre antes la devolución.</div>
        <?php endif; ?>
        <form method="post" novalidate>
            <?= campoCsrf() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="mb-3">
                <label class="form-label" for="estado">Estado físico</label>
                <select class="form-select" id="estado" name="estado"<?= $prestado ? ' disabled' : '' ?>>
                    <?php foreach (ESTADOS_EJEMPLAR as $v => $t): ?>
                        <option value="<?= $v ?>"<?= $v === $estado ? ' selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($prestado): ?><input type="hidden" name="estado" value="<?= e($estado) ?>"><?php endif; ?>
                <div class="form-text">"Prestado" no se elige aquí: se determina automáticamente por el préstamo activo.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="observaciones">Observaciones</label>
                <textarea class="form-control" id="observaciones" name="observaciones" rows="3" maxlength="500"><?= e($observaciones) ?></textarea>
            </div>
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Guardar</button>
            <a class="btn btn-outline-secondary" href="/libros/ver.php?id=<?= (int) $ejemplar['libro_id'] ?>">Cancelar</a>
        </form>
    </div>
</div>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
