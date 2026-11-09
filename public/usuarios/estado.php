<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirRol('administrador', 'bibliotecario');

if (!esPost()) {
    redirigir('/usuarios/');
}
validarCsrf();
$id = (int) post('id');
$activo = post('activo') === '1';

if (Usuario::obtener($id) === null) {
    abortarPagina(404, 'El lector solicitado no existe.');
}
try {
    Usuario::cambiarEstado($id, $activo);
    flash('success', $activo ? 'El lector fue reactivado.' : 'El lector fue desactivado.');
} catch (ErrorNegocio $e) {
    flash('danger', $e->getMessage());
}
redirigir('/usuarios/ver.php?id=' . $id);
