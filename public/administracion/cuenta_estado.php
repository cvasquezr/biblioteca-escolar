<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
$yo = requerirRol('administrador');

if (!esPost()) {
    redirigir('/administracion/cuentas.php');
}
validarCsrf();
$id = (int) post('id');
$activo = post('activo') === '1';

if (Cuenta::obtener($id) === null) {
    abortarPagina(404, 'La cuenta solicitada no existe.');
}
try {
    Cuenta::cambiarEstado($id, $activo, (int) $yo['id']);
    flash('success', $activo ? 'La cuenta fue reactivada.' : 'La cuenta fue desactivada.');
} catch (ErrorNegocio $e) {
    flash('danger', $e->getMessage());
}
redirigir('/administracion/cuentas.php');
