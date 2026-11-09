<?php
/**
 * Plantilla superior de las páginas internas.
 * Variables esperadas: $tituloPagina (string), $menuActivo (string)
 */
$cuenta = requerirLogin();
$tituloPagina ??= 'Biblioteca';
$menuActivo ??= '';

$menu = [
    ['clave' => 'panel',       'url' => '/',                       'icono' => 'speedometer2',    'texto' => 'Panel'],
    ['clave' => 'libros',      'url' => '/libros/',                'icono' => 'book',            'texto' => 'Libros'],
    ['clave' => 'ejemplares',  'url' => '/ejemplares/',            'icono' => 'upc-scan',        'texto' => 'Ejemplares'],
    ['clave' => 'lectores',    'url' => '/usuarios/',              'icono' => 'people',          'texto' => 'Lectores'],
    ['grupo' => 'Circulación'],
    ['clave' => 'prestar',     'url' => '/prestamos/nuevo.php',    'icono' => 'journal-plus',    'texto' => 'Nuevo préstamo'],
    ['clave' => 'activos',     'url' => '/prestamos/activos.php',  'icono' => 'journal-arrow-up', 'texto' => 'Préstamos activos'],
    ['clave' => 'devolver',    'url' => '/prestamos/devolver.php', 'icono' => 'journal-check',   'texto' => 'Devoluciones'],
    ['clave' => 'historial',   'url' => '/prestamos/historial.php', 'icono' => 'clock-history',  'texto' => 'Historial'],
    ['grupo' => 'Gestión'],
    ['clave' => 'informes',    'url' => '/informes/',              'icono' => 'bar-chart-line',  'texto' => 'Informes'],
    ['clave' => 'importacion', 'url' => '/importacion/',           'icono' => 'file-earmark-spreadsheet', 'texto' => 'Importar inventario'],
];
if ($cuenta['rol'] === 'administrador') {
    $menu[] = ['clave' => 'cuentas', 'url' => '/administracion/cuentas.php', 'icono' => 'shield-lock', 'texto' => 'Cuentas del sistema'];
}
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tituloPagina) ?> · Biblioteca Escolar</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/css/estilos.css" rel="stylesheet">
</head>
<body>
<div class="d-lg-flex">
    <!-- Menú lateral (en móvil se despliega con el botón de la barra superior) -->
    <nav class="offcanvas-lg offcanvas-start menu-lateral" tabindex="-1" id="menuLateral" aria-label="Menú principal">
        <div class="offcanvas-header d-lg-none">
            <span class="fw-semibold">Menú</span>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#menuLateral" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body d-block p-0">
            <a class="marca" href="/"><i class="bi bi-book-half"></i> <span>Biblioteca<br><small>Escolar</small></span></a>
            <ul class="nav flex-column px-2 pb-3">
                <?php foreach ($menu as $item): ?>
                    <?php if (isset($item['grupo'])): ?>
                        <li class="menu-grupo"><?= e($item['grupo']) ?></li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link<?= $item['clave'] === $menuActivo ? ' active' : '' ?>" href="<?= e($item['url']) ?>"
                               <?= $item['clave'] === $menuActivo ? 'aria-current="page"' : '' ?>>
                                <i class="bi bi-<?= e($item['icono']) ?>"></i> <?= e($item['texto']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>

    <div class="contenido flex-grow-1">
        <header class="barra-superior">
            <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#menuLateral" aria-controls="menuLateral" aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>
            <div class="ms-auto dropdown">
                <button class="btn btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle"></i> <span class="d-none d-sm-inline"><?= e($cuenta['nombre']) ?></span>
                    <span class="badge text-bg-primary ms-1"><?= e(ROLES[$cuenta['rol']] ?? $cuenta['rol']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/perfil.php"><i class="bi bi-key"></i> Cambiar mi contraseña</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="/logout.php" class="m-0">
                            <?= campoCsrf() ?>
                            <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main class="p-3 p-md-4">
            <?php foreach (tomarFlashes() as $f): ?>
                <div class="alert alert-<?= e($f['tipo']) ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?= $f['tipo'] === 'success' ? 'check-circle' : ($f['tipo'] === 'danger' ? 'exclamation-octagon' : 'info-circle') ?>"></i>
                    <?= e($f['mensaje']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endforeach; ?>
            <h1 class="h3 mb-3 titulo-seccion"><?= e($tituloPagina) ?></h1>
