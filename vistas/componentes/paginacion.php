<?php
/** Espera $paginacion (de calcularPaginacion). Muestra rango, filas por página, salto de página y navegación. */
if (($paginacion['total'] ?? 0) === 0) {
    return;
}
$actual = $paginacion['pagina'];
$ultima = $paginacion['paginas'];
$desde = max(1, $actual - 2);
$hasta = min($ultima, $actual + 2);
$opciones = OPCIONES_POR_PAGINA;
if (!in_array($paginacion['porPagina'], $opciones, true)) {
    $opciones[] = $paginacion['porPagina'];
    sort($opciones);
}
// Parámetros actuales (filtros) que el formulario debe conservar
$conservar = array_filter($_GET, static fn($v, $k) => !in_array($k, ['pagina', 'por_pagina'], true) && is_string($v) && $v !== '', ARRAY_FILTER_USE_BOTH);
?>
<div class="paginacion-barra mt-3">
    <form method="get" class="d-flex flex-wrap align-items-center gap-2 justify-content-between" data-autoenviar>
        <?php foreach ($conservar as $k => $v): ?>
            <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
        <?php endforeach; ?>

        <div class="d-flex align-items-center gap-2 small text-muted">
            <label for="por_pagina" class="mb-0">Filas por página</label>
            <select class="form-select form-select-sm w-auto" id="por_pagina" name="por_pagina">
                <?php foreach ($opciones as $o): ?>
                    <option value="<?= $o ?>"<?= $o === $paginacion['porPagina'] ? ' selected' : '' ?>><?= $o ?></option>
                <?php endforeach; ?>
            </select>
            <span>Mostrando <strong><?= $paginacion['desde'] ?>–<?= $paginacion['hasta'] ?></strong> de <strong><?= $paginacion['total'] ?></strong></span>
        </div>

        <?php if ($ultima > 1): ?>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <ul class="pagination pagination-sm mb-0 flex-wrap">
                <li class="page-item<?= $actual <= 1 ? ' disabled' : '' ?>">
                    <a class="page-link" href="<?= e(urlCon(['pagina' => $actual - 1])) ?>" aria-label="Página anterior">&laquo;</a>
                </li>
                <?php if ($desde > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?= e(urlCon(['pagina' => 1])) ?>">1</a></li>
                    <?php if ($desde > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <?php endif; ?>
                <?php for ($i = $desde; $i <= $hasta; $i++): ?>
                    <li class="page-item<?= $i === $actual ? ' active' : '' ?>">
                        <a class="page-link" href="<?= e(urlCon(['pagina' => $i])) ?>"<?= $i === $actual ? ' aria-current="page"' : '' ?>><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($hasta < $ultima): ?>
                    <?php if ($hasta < $ultima - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="<?= e(urlCon(['pagina' => $ultima])) ?>"><?= $ultima ?></a></li>
                <?php endif; ?>
                <li class="page-item<?= $actual >= $ultima ? ' disabled' : '' ?>">
                    <a class="page-link" href="<?= e(urlCon(['pagina' => $actual + 1])) ?>" aria-label="Página siguiente">&raquo;</a>
                </li>
            </ul>
            <div class="input-group input-group-sm w-auto">
                <label class="input-group-text" for="ir_pagina">Ir a</label>
                <input type="number" class="form-control salto-pagina" id="ir_pagina" name="pagina" min="1" max="<?= $ultima ?>" value="<?= $actual ?>" aria-label="Número de página (de <?= $ultima ?>)">
                <span class="input-group-text">de <?= $ultima ?></span>
                <button class="btn btn-outline-primary" type="submit">Ir</button>
            </div>
        </div>
        <?php endif; ?>
    </form>
</div>
