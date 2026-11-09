<?php
/** Espera $paginacion (de calcularPaginacion). */
if (($paginacion['paginas'] ?? 1) <= 1) {
    return;
}
$actual = $paginacion['pagina'];
$ultima = $paginacion['paginas'];
$desde = max(1, $actual - 2);
$hasta = min($ultima, $actual + 2);
?>
<nav aria-label="Paginación" class="mt-3">
    <ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">
        <li class="page-item<?= $actual <= 1 ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e(urlCon(['pagina' => $actual - 1])) ?>">&laquo; Anterior</a>
        </li>
        <?php if ($desde > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= e(urlCon(['pagina' => 1])) ?>">1</a></li>
            <?php if ($desde > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <?php endif; ?>
        <?php for ($i = $desde; $i <= $hasta; $i++): ?>
            <li class="page-item<?= $i === $actual ? ' active' : '' ?>">
                <a class="page-link" href="<?= e(urlCon(['pagina' => $i])) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <?php if ($hasta < $ultima): ?>
            <?php if ($hasta < $ultima - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?= e(urlCon(['pagina' => $ultima])) ?>"><?= $ultima ?></a></li>
        <?php endif; ?>
        <li class="page-item<?= $actual >= $ultima ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e(urlCon(['pagina' => $actual + 1])) ?>">Siguiente &raquo;</a>
        </li>
    </ul>
    <p class="text-center text-muted small mt-2 mb-0">
        Página <?= $actual ?> de <?= $ultima ?> · <?= (int) $paginacion['total'] ?> registros
    </p>
</nav>
