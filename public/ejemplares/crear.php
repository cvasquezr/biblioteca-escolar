<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirRol('administrador', 'bibliotecario');

if (!esPost()) {
    redirigir('/ejemplares/');
}
validarCsrf();
$libroId = (int) post('libro_id');
if (Libro::obtener($libroId) === null) {
    abortarPagina(404, 'El libro solicitado no existe.');
}

$cantidadTexto = post('cantidad');
$cantidad = ctype_digit($cantidadTexto) ? (int) $cantidadTexto : 0;
try {
    $codigos = Ejemplar::crear($libroId, $cantidad, nuloSiVacio(post('codigo')), nuloSiVacio(mb_substr(post('observaciones'), 0, 500)));
    flash('success', count($codigos) === 1
        ? 'Ejemplar creado con el código ' . $codigos[0] . '.'
        : count($codigos) . ' ejemplares creados (' . $codigos[0] . ' a ' . end($codigos) . ').');
} catch (ErrorNegocio $e) {
    flash('danger', $e->getMessage());
}
redirigir('/libros/ver.php?id=' . $libroId);
