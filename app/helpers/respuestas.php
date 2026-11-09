<?php

declare(strict_types=1);

/** Redirige y termina. Solo rutas internas (empiezan por /). */
function redirigir(string $ruta): never
{
    if ($ruta === '' || $ruta[0] !== '/' || str_starts_with($ruta, '//')) {
        $ruta = '/';
    }
    header('Location: ' . $ruta);
    exit;
}

/* ---------- Mensajes flash ---------- */

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/** @return array<int, array{tipo:string, mensaje:string}> */
function tomarFlashes(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

/** Muestra una página de error sencilla y termina. */
function abortarPagina(int $codigo, string $mensaje, ?string $detalle = null): never
{
    if (!headers_sent()) {
        http_response_code($codigo);
    }
    $titulos = [403 => 'Acceso denegado', 404 => 'No encontrado', 419 => 'Formulario no válido', 500 => 'Error'];
    $titulo = $titulos[$codigo] ?? 'Error';
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . e($titulo) . '</title>'
        . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">'
        . '</head><body class="bg-light"><div class="container py-5 col-md-7 col-lg-5">'
        . '<div class="card shadow-sm"><div class="card-body text-center p-5">'
        . '<h1 class="h3 mb-3">' . e($titulo) . '</h1><p class="text-muted">' . e($mensaje) . '</p>';
    if ($detalle !== null) {
        echo '<p class="small text-danger">' . e($detalle) . '</p>';
    }
    echo '<a class="btn btn-primary" href="/">Volver al inicio</a></div></div></div></body></html>';
    exit;
}

/* ---------- Paginación ---------- */

/** @return array{pagina:int, porPagina:int, paginas:int, total:int, desplazamiento:int} */
function calcularPaginacion(int $total, int $porPagina = 15): array
{
    $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
    $paginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min($pagina, $paginas);
    return [
        'pagina' => $pagina,
        'porPagina' => $porPagina,
        'paginas' => $paginas,
        'total' => $total,
        'desplazamiento' => ($pagina - 1) * $porPagina,
    ];
}

/** URL de la página actual con parámetros GET modificados. */
function urlCon(array $cambios): string
{
    $parametros = array_merge($_GET, $cambios);
    $parametros = array_filter($parametros, static fn($v) => $v !== '' && $v !== null);
    $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return $ruta . ($parametros ? '?' . http_build_query($parametros) : '');
}

/* ---------- Formato ---------- */

function fecha(?string $f): string
{
    if ($f === null || $f === '') {
        return '—';
    }
    $d = DateTimeImmutable::createFromFormat('Y-m-d', substr($f, 0, 10));
    return $d ? $d->format('d/m/Y') : $f;
}

function fechaHora(?string $f): string
{
    if ($f === null || $f === '') {
        return '—';
    }
    try {
        return (new DateTimeImmutable($f))->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('d/m/Y H:i');
    } catch (Throwable) {
        return $f;
    }
}

/* ---------- Etiquetas de estado (texto + icono, no solo color) ---------- */

function insignia(string $texto, string $color, string $icono): string
{
    return '<span class="badge text-bg-' . e($color) . ' estado-insignia"><i class="bi bi-' . e($icono) . '"></i> '
        . e($texto) . '</span>';
}

function insigniaActivo(bool $activo): string
{
    return $activo ? insignia('Activo', 'success', 'check-circle') : insignia('Inactivo', 'secondary', 'slash-circle');
}

/** Estado efectivo de un ejemplar (incluye "prestado", que se deduce del préstamo). */
function insigniaEjemplar(string $estado): string
{
    return match ($estado) {
        'disponible' => insignia('Disponible', 'success', 'check-circle'),
        'prestado' => insignia('Prestado', 'primary', 'journal-arrow-up'),
        'danado' => insignia('Dañado', 'warning', 'exclamation-triangle'),
        'perdido' => insignia('Perdido', 'danger', 'question-circle'),
        'inactivo' => insignia('Inactivo', 'secondary', 'slash-circle'),
        default => insignia($estado, 'secondary', 'dash-circle'),
    };
}

/** @param array<string,mixed> $p fila de préstamo con estado y fecha_limite */
function insigniaPrestamo(array $p): string
{
    if ($p['estado'] === 'devuelto') {
        return insignia('Devuelto', 'success', 'check2-circle');
    }
    if ($p['fecha_limite'] < hoy()) {
        return insignia('Vencido', 'danger', 'alarm');
    }
    return insignia('Activo', 'primary', 'clock');
}

const TIPOS_USUARIO = ['estudiante' => 'Estudiante', 'docente' => 'Docente', 'administrativo' => 'Administrativo'];
const ESTADOS_EJEMPLAR = ['disponible' => 'Disponible', 'danado' => 'Dañado', 'perdido' => 'Perdido', 'inactivo' => 'Inactivo'];
const ROLES = ['administrador' => 'Administrador', 'bibliotecario' => 'Bibliotecario'];

/* ---------- Formularios ---------- */

/** HTML del mensaje de error de un campo (vacío si no hay error). */
function errorCampo(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? '<div class="invalid-feedback">' . e($errores[$campo]) . '</div>' : '';
}

function claseCampo(array $errores, string $campo, string $base = 'form-control'): string
{
    return $base . (isset($errores[$campo]) ? ' is-invalid' : '');
}
