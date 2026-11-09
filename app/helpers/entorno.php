<?php

declare(strict_types=1);

/** Lee un archivo .env sencillo (CLAVE=valor) y lo deja disponible en $_ENV. */
function cargarEntorno(string $ruta): void
{
    if (!is_file($ruta)) {
        return;
    }
    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        // Las variables reales del contenedor tienen prioridad sobre el archivo
        if (getenv($clave) === false && !isset($_ENV[$clave])) {
            $_ENV[$clave] = trim($valor, " \t\"'");
        }
    }
}

/** Devuelve una variable de entorno o el valor por defecto. */
function entorno(string $clave, ?string $porDefecto = null): ?string
{
    $valor = getenv($clave);
    if ($valor !== false) {
        return $valor;
    }
    return $_ENV[$clave] ?? $porDefecto;
}
