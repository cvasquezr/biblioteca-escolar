<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/inicio.php';
requerirLogin();

$id = (int) (consulta('id') ?: ($_POST['id'] ?? 0));
$libro = $id > 0 ? Libro::obtener($id) : null;
if ($id > 0 && $libro === null) {
    abortarPagina(404, 'El libro solicitado no existe.');
}

$campos = ['titulo', 'autor', 'editorial', 'paginas', 'isbn', 'categoria', 'ubicacion', 'descripcion'];
$datos = [];
foreach ($campos as $c) {
    $datos[$c] = $libro !== null ? (string) ($libro[$c] ?? '') : '';
}
$errores = [];

if (esPost()) {
    validarCsrf();
    foreach ($campos as $c) {
        $datos[$c] = post($c);
    }
    $errores = Libro::validar($datos);
    if (!$errores) {
        if ($libro === null) {
            $nuevoId = Libro::crear($datos);
            flash('success', 'Libro registrado. Ahora puede agregarle ejemplares.');
            redirigir('/libros/ver.php?id=' . $nuevoId);
        }
        Libro::actualizar($id, $datos);
        flash('success', 'Los cambios del libro se guardaron.');
        redirigir('/libros/ver.php?id=' . $id);
    }
}

$tituloPagina = $libro === null ? 'Nuevo libro' : 'Editar libro';
$menuActivo = 'libros';
require RAIZ . '/vistas/layouts/cabecera.php';

?>
<div class="card">
    <div class="card-body">
        <form method="post" novalidate>
            <?= campoCsrf() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label requerido" for="titulo">Título</label>
                    <input class="<?= claseCampo($errores, 'titulo') ?>" id="titulo" name="titulo" value="<?= e($datos['titulo']) ?>" maxlength="255" required>
                    <?= errorCampo($errores, 'titulo') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label requerido" for="autor">Autor</label>
                    <input class="<?= claseCampo($errores, 'autor') ?>" id="autor" name="autor" value="<?= e($datos['autor']) ?>" maxlength="255" required>
                    <?= errorCampo($errores, 'autor') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="editorial">Editorial</label>
                    <input class="<?= claseCampo($errores, 'editorial') ?>" id="editorial" name="editorial" value="<?= e($datos['editorial']) ?>" maxlength="150">
                    <?= errorCampo($errores, 'editorial') ?>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label" for="paginas">Páginas</label>
                    <input type="number" min="1" class="<?= claseCampo($errores, 'paginas') ?>" id="paginas" name="paginas" value="<?= e($datos['paginas']) ?>">
                    <?= errorCampo($errores, 'paginas') ?>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label" for="isbn">ISBN</label>
                    <input class="<?= claseCampo($errores, 'isbn') ?>" id="isbn" name="isbn" value="<?= e($datos['isbn']) ?>" maxlength="20">
                    <?= errorCampo($errores, 'isbn') ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="categoria">Categoría</label>
                    <input class="<?= claseCampo($errores, 'categoria') ?>" id="categoria" name="categoria" value="<?= e($datos['categoria']) ?>" maxlength="80" list="listaCategorias">
                    <datalist id="listaCategorias">
                        <?php foreach (Libro::categorias() as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?>
                    </datalist>
                    <?= errorCampo($errores, 'categoria') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="ubicacion">Ubicación o sección</label>
                    <input class="<?= claseCampo($errores, 'ubicacion') ?>" id="ubicacion" name="ubicacion" value="<?= e($datos['ubicacion']) ?>" maxlength="80" placeholder="Ej.: Estante A2">
                    <?= errorCampo($errores, 'ubicacion') ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <textarea class="<?= claseCampo($errores, 'descripcion') ?>" id="descripcion" name="descripcion" rows="3" maxlength="3000"><?= e($datos['descripcion']) ?></textarea>
                    <?= errorCampo($errores, 'descripcion') ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Guardar</button>
                <a class="btn btn-outline-secondary" href="<?= $libro ? '/libros/ver.php?id=' . $id : '/libros/' ?>">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php require RAIZ . '/vistas/layouts/pie.php'; ?>
