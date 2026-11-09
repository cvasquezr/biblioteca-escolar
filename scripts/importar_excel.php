<?php

declare(strict_types=1);

/**
 * Importa un inventario .xlsx de database/import desde la consola:
 *   docker compose exec app php scripts/importar_excel.php nombre.xlsx
 * Usa el mismo código que la pantalla "Importar inventario" (transacción, sin duplicar).
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
require RAIZ . '/app/modelos/Libro.php';
require RAIZ . '/app/modelos/Ejemplar.php';
require RAIZ . '/app/servicios/ImportadorExcel.php';
cargarEntorno(RAIZ . '/.env');

$ruta = ImportadorExcel::rutaSegura($argv[1] ?? '');
if ($ruta === null) {
    fwrite(STDERR, "Indique un archivo .xlsx existente en database/import.\n");
    exit(1);
}
$a = ImportadorExcel::analizar($ruta);
echo "Filas leídas: {$a['leidas']} | libros nuevos: {$a['libros_a_crear']} | ejemplares: {$a['ejemplares_a_crear']} | ya existen: " . count($a['ya_existen']) . " | a revisar: " . count($a['incompletas']) . "\n";
foreach ($a['incompletas'] as $f) {
    echo "  Fila {$f['fila']} omitida ({$f['motivo']}): {$f['titulo']}\n";
}
$r = ImportadorExcel::importar($ruta);
echo "Importado: {$r['libros']} libros y {$r['ejemplares']} ejemplares.\n";
