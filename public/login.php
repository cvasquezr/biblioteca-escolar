<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/inicio.php';

if (cuentaActual() !== null) {
    redirigir('/');
}
iniciarSesionSegura();

$error = null;
$correo = '';
if (esPost()) {
    validarCsrf();
    $correo = post('correo');
    $error = intentarLogin($correo, (string) ($_POST['password'] ?? ''));
    if ($error === null) {
        redirigir('/');
    }
}
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · Biblioteca Escolar</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/css/estilos.css" rel="stylesheet">
</head>
<body>
<main class="pagina-login">
    <div class="tarjeta-login">
        <div class="card p-4 p-sm-5">
            <div class="logo-login"><i class="bi bi-book-half"></i></div>
            <h1 class="h4 text-center fw-bold mb-1">Biblioteca Escolar</h1>
            <p class="text-center text-muted mb-4">Ingrese con su cuenta para continuar</p>

            <?php if ($error !== null): ?>
                <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-octagon"></i> <?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= campoCsrf() ?>
                <div class="mb-3">
                    <label for="correo" class="form-label">Correo electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="correo" name="correo" value="<?= e($correo) ?>"
                               autocomplete="username" required autofocus maxlength="160">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               autocomplete="current-password" required maxlength="200">
                    </div>
                </div>
                <button class="btn btn-primary w-100 py-2" type="submit"><i class="bi bi-box-arrow-in-right"></i> Ingresar</button>
            </form>
        </div>
        <p class="text-center text-muted small mt-3">El acceso lo administra la persona encargada de la biblioteca.</p>
    </div>
</main>
</body>
</html>
