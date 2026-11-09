<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirLogin();

$filtros = ['q' => consulta('q'), 'estado' => consulta('estado')];
$resultado = Ejemplar::buscar($filtros);
$paginacion = $resultado['paginacion'];

$tituloPagina = 'Ejemplares';
$menuActivo = 'ejemplares';
require RAIZ . '/vistas/layouts/cabecera.php';
?>
<form method="get" class="row g-2 mb-3" role="search">
    <div class="col-12 col-md-6">
        <input type="search" class="form-control" name="q" value="<?= e($filtros['q']) ?>" placeholder="Buscar por código, título o autor" aria-label="Buscar">
    </div>
    <div class="col-6 col-md-3">
        <select class="form-select" name="estado" aria-label="Estado">
            <option value="">Todos los estados</option>
            <?php foreach (['disponible' => 'Disponible', 'prestado' => 'Prestado', 'danado' => 'Dañado', 'perdido' => 'Perdido', 'inactivo' => 'Inactivo'] as $v => $t): ?>
                <option value="<?= $v ?>"<?= $v === $filtros['estado'] ? ' selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-auto d-flex gap-2">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
        <a class="btn btn-outline-secondary" href="/ejemplares/">Limpiar</a>
    </div>
</form>

<div class="card">
<?php if (!$resultado['filas']): ?>
    <?php
    $vacioIcono = 'upc-scan';
    $vacioTitulo = 'No se encontraron ejemplares';
    $vacioTexto = 'Los ejemplares se crean desde el detalle de cada libro.';
    $vacioAccion = '<a class="btn btn-primary" href="/libros/">Ir a libros</a>';
    require RAIZ . '/vistas/componentes/estado_vacio.php';
    ?>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Código</th><th>Libro</th><th class="d-none d-md-table-cell">Autor</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($resultado['filas'] as $ej): ?>
                <tr>
                    <td><code><?= e($ej['codigo_inventario']) ?></code></td>
                    <td class="truncar" title="<?= e($ej['titulo']) ?>"><a class="text-decoration-none" href="/libros/ver.php?id=<?= (int) $ej['libro_id'] ?>"><?= e($ej['titulo']) ?></a></td>
                    <td class="d-none d-md-table-cell truncar"><?= e($ej['autor']) ?></td>
                    <td><?= insigniaEjemplar($ej['estado_efectivo']) ?></td>
                    <td class="col-acciones"><a class="btn btn-sm btn-outline-secondary" href="/ejemplares/editar.php?id=<?= (int) $ej['id'] ?>"><i class="bi bi-pencil"></i> Editar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>
<?php require RAIZ . '/vistas/componentes/paginacion.php'; ?>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
