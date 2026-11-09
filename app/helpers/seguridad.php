<?php

declare(strict_types=1);

/** Escapa texto para mostrarlo en HTML. */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function enviarCabecerasSeguridad(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; "
        . "style-src 'self'; "
        . "script-src 'self'; "
        . "font-src 'self'; "
        . "img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
    header('Cache-Control: no-store');
}

function esHttps(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function iniciarSesionSegura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('BIBLIOSESION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => esHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

/* ---------- CSRF ---------- */

function tokenCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function campoCsrf(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(tokenCsrf()) . '">';
}

/** Detiene la petición si el token CSRF es inválido. Llamar al procesar un POST. */
function validarCsrf(): void
{
    $enviado = $_POST['_csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(tokenCsrf(), $enviado)) {
        abortarPagina(419, 'La sesión del formulario expiró o no es válida. Recargue la página e intente de nuevo.');
    }
}

/* ---------- Entrada de datos ---------- */

/** Texto del POST sin espacios sobrantes. */
function post(string $clave): string
{
    $valor = $_POST[$clave] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

/** Texto del GET sin espacios sobrantes. */
function consulta(string $clave): string
{
    $valor = $_GET[$clave] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

/** Convierte '' en null para guardar columnas opcionales. */
function nuloSiVacio(string $valor): ?string
{
    return $valor === '' ? null : $valor;
}

function esPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Valida una fecha AAAA-MM-DD real. */
function fechaValida(string $fecha): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha;
}

function hoy(): string
{
    return date('Y-m-d');
}

function longitud(string $texto): int
{
    return mb_strlen($texto, 'UTF-8');
}

/**
 * IP del cliente. Detrás de un proxy de confianza (TRUST_PROXY=true, por ejemplo en Render)
 * se usa la última IP de X-Forwarded-For, que es la que añadió el proxy y no puede falsificar el cliente.
 */
function ipCliente(): string
{
    if (entorno('TRUST_PROXY', 'false') === 'true' && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $partes = array_map('trim', explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']));
        $ip = end($partes);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
