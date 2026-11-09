<?php

declare(strict_types=1);

/**
 * Prepara la base de datos al arrancar el contenedor (útil en servicios en la nube):
 *  1. Espera a que PostgreSQL responda.
 *  2. Crea tablas e índices (los scripts 01 y 02 son repetibles y no borran datos).
 *  3. Si no hay ningún administrador y existen ADMIN_EMAIL y ADMIN_PASSWORD, crea el primero.
 *  4. Si DEMO_DATA=true y la base está vacía, carga los datos ficticios (script 03).
 *
 * Nunca imprime contraseñas. Es seguro ejecutarlo en cada arranque.
 */

if (PHP_SAPI !== 'cli') {
    exit('Solo desde la consola.');
}
define('RAIZ', dirname(__DIR__));
require RAIZ . '/app/helpers/entorno.php';
require RAIZ . '/app/helpers/seguridad.php';
require RAIZ . '/app/helpers/respuestas.php';
require RAIZ . '/app/configuracion/BaseDatos.php';
require RAIZ . '/app/configuracion/ErrorNegocio.php';
require RAIZ . '/app/modelos/Cuenta.php';
cargarEntorno(RAIZ . '/.env');

// 1. Esperar a la base de datos
$pdo = null;
for ($intento = 1; $intento <= 30; $intento++) {
    try {
        $pdo = BaseDatos::conexion();
        break;
    } catch (PDOException $e) {
        echo "Esperando a la base de datos ($intento/30)...\n";
        sleep(2);
    }
}
if ($pdo === null) {
    fwrite(STDERR, "No se pudo conectar a la base de datos.\n");
    exit(1);
}

// 2. Estructura
foreach (['01_crear_tablas.sql', '02_indices_y_restricciones.sql'] as $script) {
    $pdo->exec((string) file_get_contents(RAIZ . '/database/sql/' . $script));
}
echo "Base de datos lista (tablas e índices).\n";

// 3. Primer administrador
$correo = mb_strtolower(trim((string) entorno('ADMIN_EMAIL', '')));
$password = (string) entorno('ADMIN_PASSWORD', '');
if ($correo !== '' && $password !== '') {
    $hayAdmin = (int) BaseDatos::valor("SELECT count(*) FROM cuentas WHERE rol = 'administrador' AND activo");
    if ($hayAdmin === 0) {
        $datos = ['nombre' => 'Administrador', 'correo' => $correo, 'rol' => 'administrador', 'password' => $password];
        $errores = Cuenta::validar($datos, null, true);
        if ($errores) {
            fwrite(STDERR, 'No se creó el administrador: ' . implode(' ', $errores) . "\n");
        } else {
            Cuenta::crear($datos);
            echo "Administrador inicial creado: $correo\n";
        }
    }
}

// 4. Datos de demostración (solo si todo está vacío)
if (entorno('DEMO_DATA', 'false') === 'true') {
    $libros = (int) BaseDatos::valor('SELECT count(*) FROM libros');
    $demo = BaseDatos::valor("SELECT 1 FROM cuentas WHERE correo = 'demo@ejemplo.invalid'");
    if ($libros === 0 && $demo === null) {
        $pdo->exec((string) file_get_contents(RAIZ . '/database/sql/03_datos_demostracion.sql'));
        echo "Datos de demostración cargados.\n";
    }
}
