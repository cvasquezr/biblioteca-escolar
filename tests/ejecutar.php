<?php

declare(strict_types=1);

/**
 * Pruebas del sistema. Se ejecutan dentro del contenedor:
 *
 *   docker compose exec app php tests/ejecutar.php
 *
 * Usan una base de datos SEPARADA (biblioteca_prueba) que se recrea en cada ejecución,
 * y un servidor PHP temporal en el puerto 8099. No tocan la base de datos real.
 */

if (PHP_SAPI !== 'cli') {
    exit('Solo desde la consola.');
}

const BASE_PRUEBA = 'biblioteca_prueba';
const PUERTO = 8099;
define('RAIZ', dirname(__DIR__));

require RAIZ . '/app/helpers/entorno.php';
require RAIZ . '/app/helpers/seguridad.php';
require RAIZ . '/app/helpers/respuestas.php';
require RAIZ . '/app/helpers/autenticacion.php';
cargarEntorno(RAIZ . '/.env');
date_default_timezone_set('America/Bogota');

if (entorno('DB_DATABASE') === BASE_PRUEBA) {
    exit("La base real no puede llamarse " . BASE_PRUEBA . ".\n");
}

/* ---------- Preparar la base de prueba ---------- */
$admin = new PDO(
    sprintf('pgsql:host=%s;port=%s;dbname=postgres', entorno('DB_HOST', 'db'), entorno('DB_PORT', '5432')),
    entorno('DB_USERNAME'),
    entorno('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$admin->exec('DROP DATABASE IF EXISTS ' . BASE_PRUEBA . ' WITH (FORCE)');
$admin->exec('CREATE DATABASE ' . BASE_PRUEBA . " ENCODING 'UTF8'");

putenv('DB_DATABASE=' . BASE_PRUEBA);
$_ENV['DB_DATABASE'] = BASE_PRUEBA;

require RAIZ . '/app/configuracion/BaseDatos.php';
require RAIZ . '/app/configuracion/ErrorNegocio.php';
foreach (['Cuenta', 'Libro', 'Ejemplar', 'Usuario', 'Prestamo', 'Panel'] as $c) {
    require RAIZ . "/app/modelos/$c.php";
}
foreach (["PrestamoService", "DevolucionService", "Informe", "ImportadorExcel"] as $c) {
    require RAIZ . "/app/servicios/$c.php";
}

$pdo = BaseDatos::conexion();
foreach (['01_crear_tablas.sql', '02_indices_y_restricciones.sql'] as $script) {
    $pdo->exec((string) file_get_contents(RAIZ . '/database/sql/' . $script));
}

/* ---------- Servidor temporal ---------- */
$entornoServidor = array_merge(getenv(), ['DB_DATABASE' => BASE_PRUEBA]);
$servidor = proc_open(
    [PHP_BINARY, '-S', '127.0.0.1:' . PUERTO, '-t', RAIZ . '/public'],
    [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $tuberias,
    RAIZ,
    $entornoServidor
);
register_shutdown_function(static function () use ($servidor, $admin): void {
    if (is_resource($servidor)) {
        proc_terminate($servidor);
    }
    $admin->exec('DROP DATABASE IF EXISTS ' . BASE_PRUEBA . ' WITH (FORCE)');
});
for ($i = 0; $i < 50; $i++) {
    $c = @fsockopen('127.0.0.1', PUERTO);
    if ($c) {
        fclose($c);
        break;
    }
    usleep(100000);
}

/* ---------- Mini marco de pruebas ---------- */
$resultados = ['ok' => 0, 'fallo' => 0];
function prueba(string $nombre, callable $fn): void
{
    global $resultados;
    try {
        $fn();
        $resultados['ok']++;
        echo "  [OK]    $nombre\n";
    } catch (Throwable $e) {
        $resultados['fallo']++;
        echo "  [FALLO] $nombre\n          -> " . $e->getMessage() . "\n";
    }
}
function afirmar(bool $condicion, string $mensaje = 'La condición no se cumplió'): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}
function esperarError(callable $fn, string $fragmento): void
{
    try {
        $fn();
    } catch (ErrorNegocio $e) {
        afirmar(str_contains($e->getMessage(), $fragmento), "Mensaje inesperado: {$e->getMessage()}");
        return;
    }
    throw new RuntimeException('Se esperaba un error de negocio y no ocurrió.');
}

/** Cliente HTTP con cookies. */
final class Cliente
{
    private string $jar;

    public function __construct()
    {
        $this->jar = tempnam(sys_get_temp_dir(), 'cj');
    }

    /** @return array{codigo:int, cuerpo:string, ubicacion:string, tipo:string} */
    public function pedir(string $ruta, ?array $post = null): array
    {
        $ch = curl_init('http://127.0.0.1:' . PUERTO . $ruta);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        ]);
        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        $respuesta = (string) curl_exec($ch);
        $tam = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $cab = substr($respuesta, 0, $tam);
        $r = [
            'codigo' => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
            'cuerpo' => substr($respuesta, $tam),
            'ubicacion' => preg_match('/^Location:\s*(\S+)/mi', $cab, $m) ? $m[1] : '',
            'tipo' => preg_match('/^Content-Type:\s*([^\r\n]+)/mi', $cab, $m) ? $m[1] : '',
        ];
        return $r;
    }

    public function csrf(string $ruta): string
    {
        $r = $this->pedir($ruta);
        preg_match('/name="_csrf" value="([^"]+)"/', $r['cuerpo'], $m);
        return $m[1] ?? '';
    }

    public function login(string $correo, string $password): array
    {
        return $this->pedir('/login.php', ['_csrf' => $this->csrf('/login.php'), 'correo' => $correo, 'password' => $password]);
    }
}

/* ---------- Datos base ---------- */
$idAdmin = Cuenta::crear(['nombre' => 'Admin Prueba', 'correo' => 'admin@prueba.test', 'password' => 'ClaveSegura123', 'rol' => 'administrador']);
$idBiblio = Cuenta::crear(['nombre' => 'Biblio Prueba', 'correo' => 'biblio@prueba.test', 'password' => 'ClaveSegura456', 'rol' => 'bibliotecario']);
$idInactiva = Cuenta::crear(['nombre' => 'Inactiva', 'correo' => 'inactiva@prueba.test', 'password' => 'ClaveSegura789', 'rol' => 'bibliotecario']);
Cuenta::cambiarEstado($idInactiva, false, $idAdmin);

echo "\n== Autenticación y permisos ==\n";

prueba('1. No se puede acceder al dashboard sin iniciar sesión', function () {
    $r = (new Cliente())->pedir('/');
    afirmar($r['codigo'] === 302 && str_contains($r['ubicacion'], '/login.php'), "Código {$r['codigo']} / {$r['ubicacion']}");
    foreach (['/libros/', '/prestamos/activos.php', '/informes/', '/administracion/cuentas.php'] as $ruta) {
        $x = (new Cliente())->pedir($ruta);
        afirmar($x['codigo'] === 302, "$ruta no redirige al login");
    }
});

prueba('2. El login funciona con credenciales correctas', function () {
    $c = new Cliente();
    $r = $c->login('admin@prueba.test', 'ClaveSegura123');
    afirmar($r['codigo'] === 302 && $r['ubicacion'] === '/', "Respuesta {$r['codigo']} {$r['ubicacion']}");
    $panel = $c->pedir('/');
    afirmar($panel['codigo'] === 200 && str_contains($panel['cuerpo'], 'Panel principal'), 'No se muestra el panel');
});

prueba('3. El login rechaza credenciales incorrectas (sin revelar si el correo existe)', function () {
    $a = (new Cliente())->login('admin@prueba.test', 'incorrecta');
    $b = (new Cliente())->login('noexiste@prueba.test', 'incorrecta');
    afirmar($a['codigo'] === 200 && str_contains($a['cuerpo'], 'Correo o contraseña incorrectos'), 'Sin mensaje de error');
    preg_match('/<div class="alert alert-danger".*?<\/div>/s', $a['cuerpo'], $ma);
    preg_match('/<div class="alert alert-danger".*?<\/div>/s', $b['cuerpo'], $mb);
    afirmar($ma[0] === $mb[0], 'El mensaje difiere entre correo existente e inexistente');
});

prueba('4. Una cuenta inactiva no puede ingresar', function () {
    $c = new Cliente();
    $r = $c->login('inactiva@prueba.test', 'ClaveSegura789');
    afirmar($r['codigo'] === 200 && str_contains($r['cuerpo'], 'incorrectos'), 'La cuenta inactiva no fue rechazada');
    afirmar($c->pedir('/')['codigo'] === 302, 'La cuenta inactiva obtuvo sesión');
});

prueba('5. Un bibliotecario no puede administrar cuentas (GET y POST)', function () use ($idAdmin) {
    $c = new Cliente();
    $c->login('biblio@prueba.test', 'ClaveSegura456');
    afirmar($c->pedir('/')['codigo'] === 200, 'El bibliotecario no entró');
    afirmar($c->pedir('/administracion/cuentas.php')['codigo'] === 403, 'Listado de cuentas accesible');
    afirmar($c->pedir('/administracion/cuenta_form.php')['codigo'] === 403, 'Formulario de cuentas accesible');
    $token = $c->csrf('/libros/');
    $r = $c->pedir('/administracion/cuenta_estado.php', ['_csrf' => $token, 'id' => (string) $idAdmin, 'activo' => '0']);
    afirmar($r['codigo'] === 403, 'POST de cuentas permitido');
    afirmar(Cuenta::obtener($idAdmin)['activo'] === true, 'La cuenta fue modificada');
});

prueba('5b. Un POST sin token CSRF es rechazado', function () {
    $c = new Cliente();
    $c->login('admin@prueba.test', 'ClaveSegura123');
    $r = $c->pedir('/libros/form.php', ['titulo' => 'Sin token', 'autor' => 'X']);
    afirmar($r['codigo'] === 419, "Código {$r['codigo']}");
    afirmar(BaseDatos::valor("SELECT count(*) FROM libros WHERE titulo = 'Sin token'") == 0, 'Se guardó sin token');
});

prueba('5c. El cierre de sesión destruye la sesión', function () {
    $c = new Cliente();
    $c->login('admin@prueba.test', 'ClaveSegura123');
    $r = $c->pedir('/logout.php', ['_csrf' => $c->csrf('/')]);
    afirmar($r['codigo'] === 302, 'No redirige');
    afirmar($c->pedir('/')['codigo'] === 302, 'La sesión sigue activa');
});

prueba('5d. Se limita el número de intentos de inicio de sesión', function () {
    BaseDatos::ejecutar('DELETE FROM intentos_login');
    $c = new Cliente();
    for ($i = 0; $i < MAX_INTENTOS_CORREO; $i++) {
        $c->login('biblio@prueba.test', 'mala');
    }
    $r = $c->login('biblio@prueba.test', 'ClaveSegura456'); // contraseña correcta, pero bloqueada
    afirmar(str_contains($r['cuerpo'], 'Demasiados intentos'), 'No se bloqueó');
    afirmar($c->pedir('/')['codigo'] === 302, 'Ingresó estando bloqueado');
    BaseDatos::ejecutar('DELETE FROM intentos_login');
});

prueba('5e. Las contraseñas se guardan con hash (nunca en texto plano)', function () use ($idAdmin) {
    $h = BaseDatos::valor('SELECT password FROM cuentas WHERE id = :id', ['id' => $idAdmin]);
    afirmar($h !== 'ClaveSegura123' && password_verify('ClaveSegura123', $h), 'Hash inválido');
});

echo "\n== Libros y ejemplares ==\n";

$datosLibro = ['titulo' => 'Libro de Prueba', 'autor' => 'Autora de Prueba', 'editorial' => 'Editorial P', 'paginas' => '120',
    'isbn' => '', 'categoria' => 'Pruebas', 'descripcion' => '', 'ubicacion' => 'A1'];
$libroId = 0;

prueba('6. Se puede crear, consultar, editar y desactivar un libro', function () use ($datosLibro, &$libroId) {
    afirmar(Libro::validar($datosLibro) === [], 'Datos válidos rechazados');
    $libroId = Libro::crear($datosLibro);
    $l = Libro::obtener($libroId);
    afirmar($l['titulo'] === 'Libro de Prueba' && $l['activo'] === true, 'No se consultó bien');
    Libro::actualizar($libroId, ['titulo' => 'Libro Editado'] + $datosLibro);
    afirmar(Libro::obtener($libroId)['titulo'] === 'Libro Editado', 'No se editó');
    afirmar(count(Libro::validar(['titulo' => '', 'autor' => ''] + $datosLibro)) === 2, 'No valida título/autor obligatorios');
    afirmar(isset(Libro::validar(['paginas' => '-5'] + $datosLibro)['paginas']), 'Acepta páginas negativas');
    Libro::cambiarEstado($libroId, false);
    afirmar(Libro::obtener($libroId)['activo'] === false, 'No se desactivó');
    Libro::cambiarEstado($libroId, true);
    afirmar(Libro::obtener($libroId)['activo'] === true, 'No se reactivó');
});

prueba('6b. Crear un libro por la interfaz web (con CSRF)', function () use ($datosLibro) {
    $c = new Cliente();
    $c->login('admin@prueba.test', 'ClaveSegura123');
    $datos = ['_csrf' => $c->csrf('/libros/form.php'), 'id' => '0', 'titulo' => 'Libro Web ñandú', 'autor' => 'Autor Web'] + $datosLibro;
    $r = $c->pedir('/libros/form.php', $datos);
    afirmar($r['codigo'] === 302 && str_contains($r['ubicacion'], '/libros/ver.php?id='), "Respuesta {$r['codigo']}");
    $busqueda = $c->pedir('/libros/?q=NANDU');   // búsqueda sin importar tildes ni mayúsculas
    afirmar(str_contains($busqueda['cuerpo'], 'Libro Web ñandú'), 'La búsqueda no encuentra el libro');
});

prueba('7. No se puede registrar dos veces la misma identificación de lector', function () {
    $d = ['identificacion' => 'T-001', 'nombres' => 'Ana', 'apellidos' => 'Prueba', 'tipo_usuario' => 'estudiante', 'grado' => '', 'telefono' => '', 'correo' => ''];
    afirmar(Usuario::validar($d, null) === [], 'Lector válido rechazado');
    Usuario::crear($d);
    afirmar(isset(Usuario::validar($d, null)['identificacion']), 'La validación no detecta el duplicado');
    esperarError(fn() => Usuario::crear($d), 'Ya existe un lector');
});

prueba('8. Se pueden crear varios ejemplares para un título', function () use (&$libroId) {
    $codigos = Ejemplar::crear($libroId, 3, null, null);
    afirmar(count($codigos) === 3, 'No se crearon 3');
    afirmar(count(array_unique($codigos)) === 3, 'Códigos repetidos');
    $mas = Ejemplar::crear($libroId, 2, null, 'Donación');
    afirmar($mas[0] !== $codigos[0] && count(Ejemplar::porLibro($libroId)) === 5, 'El consecutivo no continúa');
    esperarError(fn() => Ejemplar::crear($libroId, 1, $codigos[0], null), 'ya existe');
});

echo "\n== Préstamos y devoluciones ==\n";

$lector = (int) BaseDatos::valor("SELECT id FROM usuarios WHERE identificacion = 'T-001'");
$ejs = array_column(Ejemplar::porLibro($libroId), 'id');
$manana = date('Y-m-d', strtotime('+7 days'));
$prestar = fn(int $ej, ?int $u = null, ?string $fp = null, ?string $fl = null) => PrestamoService::registrar([
    'usuario_id' => $u ?? $lector, 'ejemplar_id' => $ej, 'fecha_prestamo' => $fp ?? hoy(),
    'fecha_limite' => $fl ?? $manana, 'observaciones' => '',
], $idBiblio);

prueba('9. Se puede prestar un ejemplar disponible', function () use ($prestar, $ejs) {
    $id = $prestar((int) $ejs[0]);
    afirmar($id > 0, 'No se creó el préstamo');
    afirmar(Ejemplar::obtener((int) $ejs[0])['estado_efectivo'] === 'prestado', 'El ejemplar no figura como prestado');
});

prueba('10. No se puede prestar un ejemplar que ya tiene un préstamo activo', function () use ($prestar, $ejs, $pdo) {
    esperarError(fn() => $prestar((int) $ejs[0]), 'préstamo activo');
});

prueba('10b. La base de datos impide dos préstamos activos del mismo ejemplar (índice único parcial)', function () use ($ejs, $lector, $idBiblio) {
    try {
        BaseDatos::ejecutar("INSERT INTO prestamos (usuario_id, ejemplar_id, cuenta_id, fecha_prestamo, fecha_limite) VALUES (:u, :e, :c, CURRENT_DATE, CURRENT_DATE + 3)",
            ['u' => $lector, 'e' => $ejs[0], 'c' => $idBiblio]);
    } catch (PDOException $e) {
        afirmar($e->getCode() === '23505', 'Error distinto al esperado: ' . $e->getCode());
        return;
    }
    throw new RuntimeException('La base permitió dos préstamos activos');
});

prueba('11. No se puede prestar un ejemplar dañado, perdido o inactivo', function () use ($prestar, $ejs) {
    foreach (['danado', 'perdido', 'inactivo'] as $i => $estado) {
        Ejemplar::actualizar((int) $ejs[$i + 1], $estado, null);
        esperarError(fn() => $prestar((int) $ejs[$i + 1]), 'no está en condiciones');
    }
});

prueba('11b. Reglas del lector y de las fechas', function () use ($prestar, $ejs, $lector) {
    $ok = (int) $ejs[4];
    esperarError(fn() => $prestar($ok, null, hoy(), date('Y-m-d', strtotime('-1 day'))), 'anterior');
    esperarError(fn() => $prestar($ok, null, date('Y-m-d', strtotime('+3 days')), date('Y-m-d', strtotime('+9 days'))), 'futura');
    Usuario::cambiarEstado($lector, true);
    BaseDatos::ejecutar('UPDATE usuarios SET activo = FALSE WHERE id = :id', ['id' => $lector]);
    esperarError(fn() => $prestar($ok), 'inactivo');
    BaseDatos::ejecutar('UPDATE usuarios SET activo = TRUE WHERE id = :id', ['id' => $lector]);
});

prueba('12. Se puede registrar una devolución y el ejemplar vuelve a estar disponible', function () use ($ejs) {
    $pid = (int) BaseDatos::valor("SELECT id FROM prestamos WHERE ejemplar_id = :e AND estado = 'activo'", ['e' => $ejs[0]]);
    DevolucionService::registrar($pid, hoy(), 'disponible', 'Sin novedad');
    $p = Prestamo::obtener($pid);
    afirmar($p['estado'] === 'devuelto' && $p['fecha_devolucion'] === hoy(), 'No quedó devuelto');
    afirmar(Ejemplar::obtener((int) $ejs[0])['estado_efectivo'] === 'disponible', 'El ejemplar no volvió a estar disponible');
});

prueba('13. No se puede devolver dos veces el mismo préstamo', function () use ($ejs) {
    $pid = (int) BaseDatos::valor("SELECT id FROM prestamos WHERE ejemplar_id = :e ORDER BY id DESC LIMIT 1", ['e' => $ejs[0]]);
    esperarError(fn() => DevolucionService::registrar($pid, hoy(), 'disponible', ''), 'ya fue devuelto');
});

prueba('13b. Devolver un libro dañado lo deja no disponible', function () use ($prestar, $ejs) {
    $pid = $prestar((int) $ejs[0]);
    DevolucionService::registrar($pid, hoy(), 'danado', 'Se mojó');
    afirmar(Ejemplar::obtener((int) $ejs[0])['estado_efectivo'] === 'danado', 'Debió quedar dañado');
    esperarError(fn() => $prestar((int) $ejs[0]), 'no está en condiciones');
});

prueba('13c. No se desactiva un lector ni un libro con préstamos activos', function () use ($prestar, $ejs, $lector, $libroId) {
    BaseDatos::ejecutar("UPDATE ejemplares SET estado = 'disponible' WHERE id = :e", ['e' => $ejs[4]]);
    $pid = $prestar((int) $ejs[4]);
    esperarError(fn() => Usuario::cambiarEstado($lector, false), 'préstamos activos');
    esperarError(fn() => Libro::cambiarEstado($libroId, false), 'ejemplares prestados');
    DevolucionService::registrar($pid, hoy(), 'disponible', '');
});

echo "\n== Informes ==\n";

prueba('14. Los informes respetan los filtros de fechas', function () use ($ejs, $lector, $idBiblio) {
    BaseDatos::ejecutar('DELETE FROM prestamos');
    BaseDatos::ejecutar("UPDATE ejemplares SET estado = 'disponible'");
    // Préstamos devueltos en fechas conocidas
    foreach ([['2025-01-10', '2025-01-17'], ['2025-02-05', '2025-02-12'], ['2025-02-20', '2025-02-27']] as $i => [$fp, $fl]) {
        BaseDatos::ejecutar("INSERT INTO prestamos (usuario_id, ejemplar_id, cuenta_id, fecha_prestamo, fecha_limite, fecha_devolucion, estado)
                             VALUES (:u, :e, :c, :fp, :fl, :fl, 'devuelto')", ['u' => $lector, 'e' => $ejs[$i], 'c' => $idBiblio, 'fp' => $fp, 'fl' => $fl]);
    }
    $contar = static fn(string $tipo, string $d, string $h): int => (int) BaseDatos::valor(
        'SELECT count(*) FROM (' . Informe::definir($tipo, $d, $h)['sql'] . ') t', Informe::definir($tipo, $d, $h)['params']);
    afirmar($contar('historial', '', '') === 3, 'Sin filtros debe haber 3');
    afirmar($contar('historial', '2025-02-01', '2025-02-28') === 2, 'Febrero debe tener 2');
    afirmar($contar('historial', '2025-01-01', '2025-01-31') === 1, 'Enero debe tener 1');
    afirmar($contar('historial', '2025-03-01', '2025-03-31') === 0, 'Marzo debe tener 0');
    afirmar($contar('historial', '2025-02-20', '2025-02-20') === 1, 'El límite del rango debe incluirse');
    // Informe mensual agrupado por PostgreSQL
    $mensual = BaseDatos::filas(Informe::definir('mensuales', '2025-01-01', '2025-12-31')['sql'], Informe::definir('mensuales', '2025-01-01', '2025-12-31')['params']);
    afirmar(count($mensual) === 2, 'Debe haber 2 meses');
    afirmar($mensual[0]['periodo'] === '2025-02' && (int) $mensual[0]['total'] === 2, 'Febrero debe sumar 2');
    $anual = BaseDatos::filas(Informe::definir('anuales', '', '')['sql']);
    afirmar((int) $anual[0]['total'] === 3, 'Anual debe sumar 3');
    afirmar($contar('inventario', '', '') === 5, 'Inventario debe tener 5 ejemplares');
    afirmar($contar('mas_prestados', '2025-02-01', '2025-02-28') === 1, 'Más prestados febrero: 1 libro');
});

prueba('14b. La exportación a CSV funciona y respeta los filtros', function () {
    $c = new Cliente();
    $c->login('admin@prueba.test', 'ClaveSegura123');
    $r = $c->pedir('/informes/?tipo=historial&desde=2025-02-01&hasta=2025-02-28&formato=csv');
    afirmar(str_contains($r['tipo'], 'text/csv'), "Tipo: {$r['tipo']}");
    $lineas = array_filter(explode("\n", trim($r['cuerpo'])));
    afirmar(count($lineas) === 3, 'Debe tener encabezado + 2 filas, tiene ' . count($lineas));
    $pagina = $c->pedir('/informes/?tipo=historial&filtrar=1&desde=2025-03-01&hasta=2025-03-31');
    afirmar($pagina['codigo'] === 200 && str_contains($pagina['cuerpo'], 'Sin resultados'), 'Debe mostrar estado vacío');
});

echo "\n== Importación de Excel ==\n";

prueba('15. La importación crea libros y ejemplares, y repetirla no duplica', function () {
    $ruta = RAIZ . '/database/import/base_de_datos.xlsx';
    if (!is_file($ruta)) {
        throw new RuntimeException('No está el Excel de prueba en database/import');
    }
    $antes = (int) BaseDatos::valor('SELECT count(*) FROM libros');
    $previo = ImportadorExcel::analizar($ruta);
    afirmar($previo['leidas'] > 0 && $previo['libros_a_crear'] > 0, 'El informe previo está vacío');
    $r = ImportadorExcel::importar($ruta);
    afirmar($r['libros'] === $previo['libros_a_crear'] && $r['ejemplares'] === $previo['ejemplares_a_crear'], 'Cifras distintas al informe previo');
    afirmar((int) BaseDatos::valor('SELECT count(*) FROM libros') === $antes + $r['libros'], 'Cantidad de libros incorrecta');
    $segunda = ImportadorExcel::importar($ruta);
    afirmar($segunda['libros'] === 0 && $segunda['ejemplares'] === 0, 'La segunda importación duplicó datos');
    afirmar(ImportadorExcel::rutaSegura('../../.env') === null, 'Acepta rutas fuera de la carpeta');
});

echo "\n== Resumen ==\n";
printf("  Correctas: %d   Fallidas: %d\n\n", $resultados['ok'], $resultados['fallo']);
exit($resultados['fallo'] > 0 ? 1 : 0);
