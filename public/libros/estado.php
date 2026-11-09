<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirRol('administrador', 'bibliotecario');

if (!esPost()) {
    redirigir('/libros/');
}
validarCsrf();
$id = (int) post('id');
$activo = post('activo') === '1';

if (Libro::obtener($id) === null) {
    abortarPagina(404, 'El libro solicitado no existe.');
}
try {
    Libro::cambiarEstado($id, $activo);
    flash('success', $activo ? 'El libro fue reactivado.' : 'El libro fue desactivado.');
} catch (ErrorNegocio $e) {
    flash('danger', $e->getMessage());
}
redirigir('/libros/ver.php?id=' . $id);
