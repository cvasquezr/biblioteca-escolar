<?php
/** Espera $vacioIcono, $vacioTitulo, $vacioTexto y opcionalmente $vacioAccion (HTML ya escapado). */
?>
<div class="estado-vacio text-center py-5">
    <i class="bi bi-<?= e($vacioIcono ?? 'inbox') ?> display-5 text-secondary"></i>
    <h2 class="h5 mt-3"><?= e($vacioTitulo ?? 'Sin registros') ?></h2>
    <p class="text-muted mb-3"><?= e($vacioTexto ?? '') ?></p>
    <?= $vacioAccion ?? '' ?>
</div>
