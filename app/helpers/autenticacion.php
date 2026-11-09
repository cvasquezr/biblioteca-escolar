<?php

declare(strict_types=1);

const MAX_INTENTOS_CORREO = 5;
const MAX_INTENTOS_IP = 20;
const MINUTOS_BLOQUEO = 15;
const MINUTOS_INACTIVIDAD = 120;

/**
 * Cuenta que tiene la sesión abierta, leída de la base de datos en cada petición
 * (así una cuenta desactivada pierde el acceso de inmediato).
 *
 * @return array<string,mixed>|null
 */
function cuentaActual(): ?array
{
    static $cuenta = false;
    if ($cuenta !== false) {
        return $cuenta;
    }
    $cuenta = null;
    $id = $_SESSION['cuenta_id'] ?? null;
    if ($id === null) {
        return null;
    }
    if (time() - (int) ($_SESSION['ultima_actividad'] ?? 0) > MINUTOS_INACTIVIDAD * 60) {
        cerrarSesion();
        return null;
    }
    $fila = Cuenta::obtener((int) $id);
    if ($fila === null || !$fila['activo']) {
        cerrarSesion();
        return null;
    }
    $_SESSION['ultima_actividad'] = time();
    $cuenta = $fila;
    return $cuenta;
}

/** Exige sesión iniciada; si no, envía al login. */
function requerirLogin(): array
{
    $cuenta = cuentaActual();
    if ($cuenta === null) {
        // cuentaActual puede haber destruido la sesión; se reinicia para poder dejar un aviso
        iniciarSesionSegura();
        redirigir('/login.php');
    }
    return $cuenta;
}

/** Exige uno de los roles indicados; si no, responde 403. */
function requerirRol(string ...$roles): array
{
    $cuenta = requerirLogin();
    if (!in_array($cuenta['rol'], $roles, true)) {
        abortarPagina(403, 'No tiene permiso para realizar esta acción.');
    }
    return $cuenta;
}

function esAdministrador(): bool
{
    return (cuentaActual()['rol'] ?? '') === 'administrador';
}

/* ---------- Inicio y cierre de sesión ---------- */

/**
 * Intenta iniciar sesión. Devuelve null si fue correcto o un mensaje de error genérico.
 */
function intentarLogin(string $correo, string $password): ?string
{
    $correo = mb_strtolower(trim($correo), 'UTF-8');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (intentosFallidosRecientes($correo, $ip)) {
        return 'Demasiados intentos fallidos. Espere ' . MINUTOS_BLOQUEO . ' minutos e intente de nuevo.';
    }

    $cuenta = $correo !== '' ? Cuenta::obtenerPorCorreo($correo) : null;
    // Se verifica siempre un hash, exista o no la cuenta, para no revelar cuál correo existe.
    $hash = $cuenta['password'] ?? password_hash('relleno-anti-enumeracion', PASSWORD_DEFAULT);
    $correcta = password_verify($password, $hash);

    if ($cuenta === null || !$correcta || !$cuenta['activo']) {
        registrarIntentoFallido($correo, $ip);
        return 'Correo o contraseña incorrectos.';
    }

    if (password_needs_rehash($cuenta['password'], PASSWORD_DEFAULT)) {
        Cuenta::cambiarPassword((int) $cuenta['id'], $password);
    }
    BaseDatos::conexion()->prepare('DELETE FROM intentos_login WHERE correo = :c')->execute(['c' => $correo]);
    Cuenta::registrarAcceso((int) $cuenta['id']);

    session_regenerate_id(true);           // evita fijación de sesión
    $_SESSION['cuenta_id'] = (int) $cuenta['id'];
    $_SESSION['ultima_actividad'] = time();
    unset($_SESSION['csrf']);
    return null;
}

function cerrarSesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $p['path'],
            'secure' => $p['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function intentosFallidosRecientes(string $correo, string $ip): bool
{
    $pdo = BaseDatos::conexion();
    $sql = "SELECT
              count(*) FILTER (WHERE correo = :c) AS por_correo,
              count(*) FILTER (WHERE ip = :ip)    AS por_ip
            FROM intentos_login
            WHERE created_at > now() - make_interval(mins => :m)";
    $st = $pdo->prepare($sql);
    $st->execute(['c' => $correo, 'ip' => $ip, 'm' => MINUTOS_BLOQUEO]);
    $r = $st->fetch();
    return (int) $r['por_correo'] >= MAX_INTENTOS_CORREO || (int) $r['por_ip'] >= MAX_INTENTOS_IP;
}

function registrarIntentoFallido(string $correo, string $ip): void
{
    $pdo = BaseDatos::conexion();
    $pdo->prepare('INSERT INTO intentos_login (correo, ip) VALUES (:c, :ip)')
        ->execute(['c' => mb_substr($correo, 0, 160), 'ip' => $ip]);
    // Limpieza de registros antiguos
    $pdo->exec("DELETE FROM intentos_login WHERE created_at < now() - interval '1 day'");
}
