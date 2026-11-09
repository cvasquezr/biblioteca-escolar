<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
$cuenta = requerirRol('administrador', 'bibliotecario');

$fechaPrestamo = hoy();
$fechaLimite = date('Y-m-d', strtotime('+' . PrestamoService::DIAS_POR_DEFECTO . ' days'));
$observaciones = '';
$usuarioId = (int) consulta('lector');
$ejemplarId = (int) consulta('ejemplar');
$error = null;

if (esPost()) {
    validarCsrf();
    $usuarioId = (int) post('usuario_id');
    $ejemplarId = (int) post('ejemplar_id');
    $fechaPrestamo = post('fecha_prestamo');
    $fechaLimite = post('fecha_limite');
    $observaciones = mb_substr(post('observaciones'), 0, 500);
    try {
        PrestamoService::registrar([
            'usuario_id' => $usuarioId,
            'ejemplar_id' => $ejemplarId,
            'fecha_prestamo' => $fechaPrestamo,
            'fecha_limite' => $fechaLimite,
            'observaciones' => $observaciones,
        ], (int) $cuenta['id']);
        flash('success', 'Préstamo registrado correctamente.');
        redirigir('/prestamos/activos.php');
    } catch (ErrorNegocio $e) {
        $error = $e->getMessage();
    }
}

// Textos para mostrar si ya hay una selección (por enlace o por error de validación)
$lectorTexto = '';
if ($usuarioId > 0 && ($u = Usuario::obtener($usuarioId)) !== null && $u['activo']) {
    $lectorTexto = $u['nombres'] . ' ' . $u['apellidos'];
} else {
    $usuarioId = 0;
}
$ejemplarTexto = '';
if ($ejemplarId > 0 && ($ej = Ejemplar::obtener($ejemplarId)) !== null) {
    $ejemplarTexto = $ej['titulo'] . ' (' . $ej['codigo_inventario'] . ')';
} else {
    $ejemplarId = 0;
}

$tituloPagina = 'Registrar préstamo';
$menuActivo = 'prestar';
require RAIZ . '/vistas/layouts/cabecera.php';
?>
<div class="card col-xl-9">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-octagon"></i> <?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <?= campoCsrf() ?>
            <input type="hidden" name="usuario_id" id="usuario_id" value="<?= $usuarioId ?: '' ?>">
            <input type="hidden" name="ejemplar_id" id="ejemplar_id" value="<?= $ejemplarId ?: '' ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label requerido" for="lector_buscar">Lector</label>
                    <div class="autocompletar-caja">
                        <input type="text" class="form-control autocompletar" id="lector_buscar" value="<?= e($lectorTexto) ?>"
                               data-url="/prestamos/buscar.php?tipo=lector" data-destino="#usuario_id"
                               placeholder="Escriba identificación o nombre" autocomplete="off" required>
                    </div>
                    <div class="form-text">Solo se listan lectores activos. ¿No existe? <a href="/usuarios/form.php">Regístrelo aquí</a>.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label requerido" for="ejemplar_buscar">Ejemplar disponible</label>
                    <div class="autocompletar-caja">
                        <input type="text" class="form-control autocompletar" id="ejemplar_buscar" value="<?= e($ejemplarTexto) ?>"
                               data-url="/prestamos/buscar.php?tipo=ejemplar" data-destino="#ejemplar_id"
                               placeholder="Escriba título, autor o código" autocomplete="off" required>
                    </div>
                    <div class="form-text">Solo se listan ejemplares disponibles para préstamo.</div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label requerido" for="fecha_prestamo">Fecha del préstamo</label>
                    <input type="date" class="form-control" id="fecha_prestamo" name="fecha_prestamo" value="<?= e($fechaPrestamo) ?>" max="<?= e(hoy()) ?>" required>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label requerido" for="fecha_limite">Fecha límite de devolución</label>
                    <input type="date" class="form-control" id="fecha_limite" name="fecha_limite" value="<?= e($fechaLimite) ?>"
                           data-dias="<?= PrestamoService::DIAS_POR_DEFECTO ?>" min="<?= e($fechaPrestamo) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="observaciones">Observaciones</label>
                    <input class="form-control" id="observaciones" name="observaciones" value="<?= e($observaciones) ?>" maxlength="500">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-journal-plus"></i> Registrar préstamo</button>
                <a class="btn btn-outline-secondary" href="/prestamos/activos.php">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
