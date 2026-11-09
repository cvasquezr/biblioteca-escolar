<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
$yo = requerirRol('administrador');

$cuentas = Cuenta::listar();

$tituloPagina = 'Cuentas del sistema';
$menuActivo = 'cuentas';
require RAIZ . '/vistas/layouts/cabecera.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="text-muted mb-0">Personas que pueden iniciar sesión en el sistema. No son los lectores de la biblioteca.</p>
    <a class="btn btn-primary" href="/administracion/cuenta_form.php"><i class="bi bi-person-plus"></i> Nueva cuenta</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th class="d-none d-md-table-cell">Último acceso</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($cuentas as $c): ?>
                <tr>
                    <td><?= e($c['nombre']) ?><?= (int) $c['id'] === (int) $yo['id'] ? ' <span class="badge text-bg-light border">Usted</span>' : '' ?></td>
                    <td><?= e($c['correo']) ?></td>
                    <td><?= e(ROLES[$c['rol']] ?? $c['rol']) ?></td>
                    <td class="d-none d-md-table-cell"><?= e(fechaHora($c['ultimo_acceso'])) ?></td>
                    <td><?= insigniaActivo((bool) $c['activo']) ?></td>
                    <td class="col-acciones">
                        <a class="btn btn-sm btn-outline-secondary" href="/administracion/cuenta_form.php?id=<?= (int) $c['id'] ?>"><i class="bi bi-pencil"></i> Editar</a>
                        <?php if ((int) $c['id'] !== (int) $yo['id']): ?>
                            <form method="post" action="/administracion/cuenta_estado.php" class="d-inline"
                                  <?= $c['activo'] ? 'data-confirmar="¿Desactivar la cuenta de ' . e($c['nombre']) . '? Ya no podrá iniciar sesión."' : '' ?>>
                                <?= campoCsrf() ?>
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="activo" value="<?= $c['activo'] ? '0' : '1' ?>">
                                <?php if ($c['activo']): ?>
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-slash-circle"></i> Desactivar</button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-success" type="submit"><i class="bi bi-check-circle"></i> Reactivar</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
