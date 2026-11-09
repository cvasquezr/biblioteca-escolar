<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/inicio.php';
$cuenta = requerirLogin();

$errores = [];
if (esPost()) {
    validarCsrf();
    $actual = (string) ($_POST['actual'] ?? '');
    $nueva = (string) ($_POST['nueva'] ?? '');
    $confirmar = (string) ($_POST['confirmar'] ?? '');
    if (!password_verify($actual, $cuenta['password'])) {
        $errores['actual'] = 'La contraseña actual no es correcta.';
    }
    $errores += Cuenta::validarPassword($nueva);
    if ($nueva !== $confirmar) {
        $errores['confirmar'] = 'La confirmación no coincide.';
    }
    if (!$errores) {
        Cuenta::cambiarPassword((int) $cuenta['id'], $nueva);
        session_regenerate_id(true);
        flash('success', 'Su contraseña fue actualizada.');
        redirigir('/perfil.php');
    }
}

$tituloPagina = 'Cambiar mi contraseña';
$menuActivo = '';
require RAIZ . '/vistas/layouts/cabecera.php';
?>
<div class="card col-lg-6">
    <div class="card-body">
        <p class="text-muted">Cuenta: <strong><?= e($cuenta['correo']) ?></strong></p>
        <form method="post" novalidate>
            <?= campoCsrf() ?>
            <?php foreach (['actual' => 'Contraseña actual', 'nueva' => 'Nueva contraseña (mínimo 8 caracteres)', 'confirmar' => 'Repita la nueva contraseña'] as $campo => $etiqueta): ?>
                <div class="mb-3">
                    <label class="form-label requerido" for="<?= $campo ?>"><?= e($etiqueta) ?></label>
                    <input type="password" class="form-control<?= isset($errores[$campo]) ? ' is-invalid' : '' ?>" id="<?= $campo ?>"
                           name="<?= $campo ?>" required maxlength="72" autocomplete="<?= $campo === 'actual' ? 'current-password' : 'new-password' ?>">
                    <?php if (isset($errores[$campo])): ?><div class="invalid-feedback"><?= e($errores[$campo]) ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Guardar contraseña</button>
        </form>
    </div>
</div>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
