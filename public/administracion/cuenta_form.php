<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
$yo = requerirRol('administrador');

$id = (int) (consulta('id') ?: ($_POST['id'] ?? 0));
$cuenta = $id > 0 ? Cuenta::obtener($id) : null;
if ($id > 0 && $cuenta === null) {
    abortarPagina(404, 'La cuenta solicitada no existe.');
}

$datos = [
    'nombre' => (string) ($cuenta['nombre'] ?? ''),
    'correo' => (string) ($cuenta['correo'] ?? ''),
    'rol' => (string) ($cuenta['rol'] ?? 'bibliotecario'),
    'password' => '',
];
$errores = [];

if (esPost()) {
    validarCsrf();
    $datos = [
        'nombre' => post('nombre'),
        'correo' => mb_strtolower(post('correo')),
        'rol' => post('rol'),
        'password' => (string) ($_POST['password'] ?? ''),
    ];
    $errores = Cuenta::validar($datos, $cuenta ? $id : null, $cuenta === null);
    if (!$errores) {
        try {
            if ($cuenta === null) {
                Cuenta::crear($datos);
                flash('success', 'Cuenta creada. Entregue la contraseña a la persona de forma segura.');
            } else {
                Cuenta::actualizar($id, $datos);
                flash('success', 'Cuenta actualizada.');
            }
            redirigir('/administracion/cuentas.php');
        } catch (ErrorNegocio $e) {
            $errores['rol'] = $e->getMessage();
        }
    }
}

$tituloPagina = $cuenta === null ? 'Nueva cuenta' : 'Editar cuenta';
$menuActivo = 'cuentas';
require RAIZ . '/vistas/layouts/cabecera.php';
?>
<div class="card col-lg-8">
    <div class="card-body">
        <form method="post" novalidate autocomplete="off">
            <?= campoCsrf() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label requerido" for="nombre">Nombre</label>
                    <input class="<?= claseCampo($errores, 'nombre') ?>" id="nombre" name="nombre" value="<?= e($datos['nombre']) ?>" maxlength="120" required>
                    <?= errorCampo($errores, 'nombre') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label requerido" for="correo">Correo (usuario de acceso)</label>
                    <input type="email" class="<?= claseCampo($errores, 'correo') ?>" id="correo" name="correo" value="<?= e($datos['correo']) ?>" maxlength="160" required>
                    <?= errorCampo($errores, 'correo') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label requerido" for="rol">Rol</label>
                    <select class="<?= claseCampo($errores, 'rol', 'form-select') ?>" id="rol" name="rol">
                        <?php foreach (ROLES as $v => $t): ?>
                            <option value="<?= $v ?>"<?= $v === $datos['rol'] ? ' selected' : '' ?>><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= errorCampo($errores, 'rol') ?>
                    <div class="form-text">El bibliotecario gestiona la biblioteca pero no administra cuentas.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label<?= $cuenta === null ? ' requerido' : '' ?>" for="password"><?= $cuenta === null ? 'Contraseña' : 'Nueva contraseña (opcional)' ?></label>
                    <input type="password" class="<?= claseCampo($errores, 'password') ?>" id="password" name="password" maxlength="72" autocomplete="new-password">
                    <?= errorCampo($errores, 'password') ?>
                    <div class="form-text"><?= $cuenta === null ? 'Mínimo 8 caracteres.' : 'Déjela vacía para conservar la actual.' ?></div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Guardar</button>
                <a class="btn btn-outline-secondary" href="/administracion/cuentas.php">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
