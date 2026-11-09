<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirLogin();

$filtros = ['q' => consulta('q'), 'categoria' => consulta('categoria'), 'estado' => consulta('estado') ?: 'activo'];
$resultado = Libro::buscar($filtros);
$paginacion = $resultado['paginacion'];
$categorias = Libro::categorias();

$tituloPagina = 'Libros';
$menuActivo = 'libros';
require RAIZ . '/vistas/layouts/cabecera.php';
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <form method="get" class="row g-2 flex-grow-1" role="search">
        <div class="col-12 col-md-5">
            <input type="search" class="form-control" name="q" value="<?= e($filtros['q']) ?>" placeholder="Buscar por título, autor o ISBN" aria-label="Buscar">
        </div>
        <div class="col-6 col-md-3">
            <select class="form-select" name="categoria" aria-label="Categoría">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= e($c) ?>"<?= $c === $filtros['categoria'] ? ' selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="estado" aria-label="Estado">
                <?php foreach (['activo' => 'Activos', 'inactivo' => 'Inactivos', 'todos' => 'Todos'] as $v => $t): ?>
                    <option value="<?= $v ?>"<?= $v === $filtros['estado'] ? ' selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-auto d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
            <a class="btn btn-outline-secondary" href="/libros/">Limpiar</a>
        </div>
    </form>
    <a class="btn btn-primary" href="/libros/form.php"><i class="bi bi-plus-lg"></i> Nuevo libro</a>
</div>

<div class="card">
<?php if (!$resultado['filas']): ?>
    <?php
    $vacioIcono = 'book';
    $vacioTitulo = 'No se encontraron libros';
    $vacioTexto = $filtros['q'] !== '' || $filtros['categoria'] !== '' ? 'Pruebe con otros filtros o limpie la búsqueda.' : 'Registre el primer libro o importe el inventario desde Excel.';
    $vacioAccion = '<a class="btn btn-primary" href="/libros/form.php"><i class="bi bi-plus-lg"></i> Nuevo libro</a>';
    require RAIZ . '/vistas/componentes/estado_vacio.php';
    ?>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Título</th><th>Autor</th><th class="d-none d-lg-table-cell">Categoría</th><th class="text-center">Ejemplares</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($resultado['filas'] as $l): ?>
                <tr>
                    <td class="truncar" title="<?= e($l['titulo']) ?>"><a href="/libros/ver.php?id=<?= (int) $l['id'] ?>" class="fw-semibold text-decoration-none"><?= e($l['titulo']) ?></a></td>
                    <td class="truncar"><?= e($l['autor']) ?></td>
                    <td class="d-none d-lg-table-cell"><?= e($l['categoria'] ?? '—') ?></td>
                    <td class="text-center"><span title="Disponibles / total"><?= (int) $l['disponibles'] ?> / <?= (int) $l['total_ejemplares'] ?></span></td>
                    <td><?= insigniaActivo((bool) $l['activo']) ?></td>
                    <td class="col-acciones">
                        <a class="btn btn-sm btn-outline-primary" href="/libros/ver.php?id=<?= (int) $l['id'] ?>" title="Ver detalle"><i class="bi bi-eye"></i><span class="visually-hidden">Ver</span></a>
                        <a class="btn btn-sm btn-outline-secondary" href="/libros/form.php?id=<?= (int) $l['id'] ?>" title="Editar"><i class="bi bi-pencil"></i><span class="visually-hidden">Editar</span></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>
<?php require RAIZ . '/vistas/componentes/paginacion.php'; ?>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
