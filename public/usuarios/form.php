<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirLogin();

$id = (int) (consulta('id') ?: ($_POST['id'] ?? 0));
$usuario = $id > 0 ? Usuario::obtener($id) : null;
if ($id > 0 && $usuario === null) {
    abortarPagina(404, 'El lector solicitado no existe.');
}

$campos = ['identificacion', 'nombres', 'apellidos', 'tipo_usuario', 'grado', 'telefono', 'correo'];
$datos = [];
foreach ($campos as $c) {
    $datos[$c] = $usuario !== null ? (string) ($usuario[$c] ?? '') : '';
}
if ($usuario === null) {
    $datos['tipo_usuario'] = 'estudiante';
}
$errores = [];

if (esPost()) {
    validarCsrf();
    foreach ($campos as $c) {
        $datos[$c] = post($c);
    }
    $errores = Usuario::validar($datos, $usuario ? $id : null);
    if (!$errores) {
        try {
            if ($usuario === null) {
                $id = Usuario::crear($datos);
                flash('success', 'Lector registrado.');
            } else {
                Usuario::actualizar($id, $datos);
                flash('success', 'Los datos del lector se guardaron.');
            }
            redirigir('/usuarios/ver.php?id=' . $id);
        } catch (ErrorNegocio $e) {
            $errores['identificacion'] = $e->getMessage();
        }
    }
}

$tituloPagina = $usuario === null ? 'Nuevo lector' : 'Editar lector';
$menuActivo = 'lectores';
require RAIZ . '/vistas/layouts/cabecera.php';
?>
<div class="card">
    <div class="card-body">
        <form method="post" novalidate>
            <?= campoCsrf() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label requerido" for="identificacion">Identificación</label>
                    <input class="<?= claseCampo($errores, 'identificacion') ?>" id="identificacion" name="identificacion" value="<?= e($datos['identificacion']) ?>" maxlength="30" required>
                    <?= errorCampo($errores, 'identificacion') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label requerido" for="nombres">Nombres</label>
                    <input class="<?= claseCampo($errores, 'nombres') ?>" id="nombres" name="nombres" value="<?= e($datos['nombres']) ?>" maxlength="100" required>
                    <?= errorCampo($errores, 'nombres') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label requerido" for="apellidos">Apellidos</label>
                    <input class="<?= claseCampo($errores, 'apellidos') ?>" id="apellidos" name="apellidos" value="<?= e($datos['apellidos']) ?>" maxlength="100" required>
                    <?= errorCampo($errores, 'apellidos') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label requerido" for="tipo_usuario">Tipo de lector</label>
                    <select class="<?= claseCampo($errores, 'tipo_usuario', 'form-select') ?>" id="tipo_usuario" name="tipo_usuario" required>
                        <?php foreach (TIPOS_USUARIO as $v => $t): ?>
                            <option value="<?= $v ?>"<?= $v === $datos['tipo_usuario'] ? ' selected' : '' ?>><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= errorCampo($errores, 'tipo_usuario') ?>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label" for="grado">Grado o curso</label>
                    <input class="<?= claseCampo($errores, 'grado') ?>" id="grado" name="grado" value="<?= e($datos['grado']) ?>" maxlength="30" placeholder="Ej.: 10-A">
                    <?= errorCampo($errores, 'grado') ?>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label" for="telefono">Teléfono</label>
                    <input class="<?= claseCampo($errores, 'telefono') ?>" id="telefono" name="telefono" value="<?= e($datos['telefono']) ?>" maxlength="30" inputmode="tel">
                    <?= errorCampo($errores, 'telefono') ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="correo">Correo</label>
                    <input type="email" class="<?= claseCampo($errores, 'correo') ?>" id="correo" name="correo" value="<?= e($datos['correo']) ?>" maxlength="160">
                    <?= errorCampo($errores, 'correo') ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Guardar</button>
                <a class="btn btn-outline-secondary" href="<?= $usuario ? '/usuarios/ver.php?id=' . $id : '/usuarios/' ?>">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
