<?php

declare(strict_types=1);

/**
 * Crea una cuenta de administrador. Solo se ejecuta desde la línea de comandos:
 *
 *   docker compose exec app php scripts/crear_administrador.php
 *   docker compose exec app php scripts/crear_administrador.php "Nombre" correo@ejemplo.com
 *
 * La contraseña se pide por teclado (no queda en el historial ni en archivos).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se puede ejecutar desde la consola.');
}

define('RAIZ', dirname(__DIR__));
require RAIZ . '/app/helpers/entorno.php';
require RAIZ . '/app/helpers/seguridad.php';
require RAIZ . '/app/helpers/respuestas.php';
require RAIZ . '/app/configuracion/BaseDatos.php';
require RAIZ . '/app/configuracion/ErrorNegocio.php';
require RAIZ . '/app/modelos/Cuenta.php';

cargarEntorno(RAIZ . '/.env');

function preguntar(string $texto, bool $oculto = false): string
{
    echo $texto;
    if ($oculto && DIRECTORY_SEPARATOR === '/') {
        system('stty -echo');
    }
    $respuesta = trim((string) fgets(STDIN));
    if ($oculto && DIRECTORY_SEPARATOR === '/') {
        system('stty echo');
        echo PHP_EOL;
    }
    return $respuesta;
}

$nombre = $argv[1] ?? preguntar('Nombre completo: ');
$correo = $argv[2] ?? preguntar('Correo electrónico: ');
$password = getenv('ADMIN_PASSWORD') ?: preguntar('Contraseña (mínimo 8 caracteres): ', true);
if (getenv('ADMIN_PASSWORD') === false) {
    $repetir = preguntar('Repita la contraseña: ', true);
    if ($repetir !== $password) {
        fwrite(STDERR, "Las contraseñas no coinciden.\n");
        exit(1);
    }
}

$datos = ['nombre' => trim($nombre), 'correo' => mb_strtolower(trim($correo)), 'rol' => 'administrador', 'password' => $password];
$errores = Cuenta::validar($datos, null, true);
if ($errores) {
    fwrite(STDERR, "No se pudo crear la cuenta:\n - " . implode("\n - ", $errores) . "\n");
    exit(1);
}

$id = Cuenta::crear($datos);
echo "Administrador creado (id $id): {$datos['correo']}\n";
