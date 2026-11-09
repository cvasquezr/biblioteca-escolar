<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirLogin();

$filtros = ['q' => consulta('q'), 'tipo' => consulta('tipo'), 'estado' => consulta('estado') ?: 'activo'];
$resultado = Usuario::buscar($filtros);
$paginacion = $resultado['paginacion'];

$tituloPagina = 'Lectores';
$menuActivo = 'lectores';
require RAIZ . '/vistas/layouts/cabecera.php';
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <form method="get" class="row g-2 flex-grow-1" role="search">
        <div class="col-12 col-md-5">
            <input type="search" class="form-control" name="q" value="<?= e($filtros['q']) ?>" placeholder="Buscar por identificación o nombre" aria-label="Buscar">
        </div>
        <div class="col-6 col-md-3">
            <select class="form-select" name="tipo" aria-label="Tipo de lector">
                <option value="">Todos los tipos</option>
                <?php foreach (TIPOS_USUARIO as $v => $t): ?>
                    <option value="<?= $v ?>"<?= $v === $filtros['tipo'] ? ' selected' : '' ?>><?= e($t) ?></option>
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
            <a class="btn btn-outline-secondary" href="/usuarios/">Limpiar</a>
        </div>
    </form>
    <a class="btn btn-primary" href="/usuarios/form.php"><i class="bi bi-person-plus"></i> Nuevo lector</a>
</div>

<div class="card">
<?php if (!$resultado['filas']): ?>
    <?php
    $vacioIcono = 'people';
    $vacioTitulo = 'No se encontraron lectores';
    $vacioTexto = $filtros['q'] !== '' ? 'Revise la búsqueda o registre al lector.' : 'Registre a las personas que usarán la biblioteca.';
    $vacioAccion = '<a class="btn btn-primary" href="/usuarios/form.php"><i class="bi bi-person-plus"></i> Nuevo lector</a>';
    require RAIZ . '/vistas/componentes/estado_vacio.php';
    ?>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Identificación</th><th>Nombre</th><th class="d-none d-md-table-cell">Tipo</th><th class="d-none d-lg-table-cell">Grado</th><th class="text-center">Préstamos activos</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($resultado['filas'] as $u): ?>
                <tr>
                    <td><?= e($u['identificacion']) ?></td>
                    <td><a class="fw-semibold text-decoration-none" href="/usuarios/ver.php?id=<?= (int) $u['id'] ?>"><?= e($u['apellidos'] . ', ' . $u['nombres']) ?></a></td>
                    <td class="d-none d-md-table-cell"><?= e(TIPOS_USUARIO[$u['tipo_usuario']] ?? $u['tipo_usuario']) ?></td>
                    <td class="d-none d-lg-table-cell"><?= e($u['grado'] ?? '—') ?></td>
                    <td class="text-center"><?= (int) $u['prestamos_activos'] ?></td>
                    <td><?= insigniaActivo((bool) $u['activo']) ?></td>
                    <td class="col-acciones">
                        <a class="btn btn-sm btn-outline-primary" href="/usuarios/ver.php?id=<?= (int) $u['id'] ?>" title="Ver"><i class="bi bi-eye"></i><span class="visually-hidden">Ver</span></a>
                        <a class="btn btn-sm btn-outline-secondary" href="/usuarios/form.php?id=<?= (int) $u['id'] ?>" title="Editar"><i class="bi bi-pencil"></i><span class="visually-hidden">Editar</span></a>
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
