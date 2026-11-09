<?php

declare(strict_types=1);

/**
 * Arranque común: todas las páginas públicas incluyen este archivo primero.
 */

define('RAIZ', dirname(__DIR__));

require_once RAIZ . '/app/helpers/entorno.php';
require_once RAIZ . '/app/helpers/seguridad.php';
require_once RAIZ . '/app/helpers/respuestas.php';
require_once RAIZ . '/app/helpers/autenticacion.php';

cargarEntorno(RAIZ . '/.env');
date_default_timezone_set(entorno('APP_TIMEZONE', 'America/Bogota'));

// Los errores técnicos se registran en el log; nunca se muestran al usuario.
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Carga automática de clases (modelos y servicios)
spl_autoload_register(static function (string $clase): void {
    foreach (['configuracion', 'modelos', 'servicios'] as $carpeta) {
        $archivo = RAIZ . "/app/$carpeta/$clase.php";
        if (is_file($archivo)) {
            require_once $archivo;
            return;
        }
    }
});

set_exception_handler(static function (Throwable $e): void {
    error_log('Error no controlado: ' . get_class($e) . ' - ' . $e->getMessage()
        . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    $detalle = entorno('APP_DEBUG', 'false') === 'true' && entorno('APP_ENV', 'local') === 'local'
        ? $e->getMessage() : null;
    abortarPagina(500, 'Ocurrió un error inesperado. Intente de nuevo; si persiste, avise al administrador.', $detalle);
});

enviarCabecerasSeguridad();
iniciarSesionSegura();
