<?php

declare(strict_types=1);

/** Cuentas internas que inician sesión (distintas de los lectores). */
final class Cuenta
{
    public static function obtener(int $id): ?array
    {
        return BaseDatos::fila('SELECT * FROM cuentas WHERE id = :id', ['id' => $id]);
    }

    public static function obtenerPorCorreo(string $correo): ?array
    {
        return BaseDatos::fila('SELECT * FROM cuentas WHERE lower(correo) = lower(:c)', ['c' => $correo]);
    }

    public static function listar(): array
    {
        return BaseDatos::filas('SELECT id, nombre, correo, rol, activo, ultimo_acceso FROM cuentas ORDER BY activo DESC, nombre');
    }

    /** @return array<string,string> errores por campo */
    public static function validar(array $d, ?int $idActual, bool $passwordObligatoria): array
    {
        $e = [];
        if ($d['nombre'] === '' || longitud($d['nombre']) > 120) {
            $e['nombre'] = 'El nombre es obligatorio (máximo 120 caracteres).';
        }
        if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL) || longitud($d['correo']) > 160) {
            $e['correo'] = 'Ingrese un correo válido.';
        } else {
            $otra = self::obtenerPorCorreo($d['correo']);
            if ($otra !== null && (int) $otra['id'] !== $idActual) {
                $e['correo'] = 'Ya existe una cuenta con ese correo.';
            }
        }
        if (!isset(ROLES[$d['rol']])) {
            $e['rol'] = 'Seleccione un rol válido.';
        }
        if ($passwordObligatoria || $d['password'] !== '') {
            $e += self::validarPassword($d['password']);
        }
        return $e;
    }

    /** @return array<string,string> */
    public static function validarPassword(string $password): array
    {
        if (strlen($password) < 8) {
            return ['password' => 'La contraseña debe tener al menos 8 caracteres.'];
        }
        if (strlen($password) > 72) {
            return ['password' => 'La contraseña no puede superar 72 caracteres.'];
        }
        return [];
    }

    public static function crear(array $d): int
    {
        return (int) BaseDatos::valor(
            'INSERT INTO cuentas (nombre, correo, password, rol, activo)
             VALUES (:n, lower(:c), :p, :r, TRUE) RETURNING id',
            ['n' => $d['nombre'], 'c' => $d['correo'], 'p' => password_hash($d['password'], PASSWORD_DEFAULT), 'r' => $d['rol']]
        );
    }

    /**
     * Actualiza nombre, correo y rol. Protege contra dejar el sistema sin administradores.
     */
    public static function actualizar(int $id, array $d): void
    {
        $actual = self::obtener($id);
        if ($actual === null) {
            throw new ErrorNegocio('La cuenta no existe.');
        }
        if ($actual['rol'] === 'administrador' && $d['rol'] !== 'administrador' && self::esUltimoAdministrador($id)) {
            throw new ErrorNegocio('No se puede quitar el rol al único administrador activo.');
        }
        BaseDatos::ejecutar(
            'UPDATE cuentas SET nombre = :n, correo = lower(:c), rol = :r WHERE id = :id',
            ['n' => $d['nombre'], 'c' => $d['correo'], 'r' => $d['rol'], 'id' => $id]
        );
        if (($d['password'] ?? '') !== '') {
            self::cambiarPassword($id, $d['password']);
        }
    }

    public static function cambiarPassword(int $id, string $password): void
    {
        BaseDatos::ejecutar(
            'UPDATE cuentas SET password = :p WHERE id = :id',
            ['p' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]
        );
    }

    public static function cambiarEstado(int $id, bool $activo, int $idQuienActua): void
    {
        if (!$activo) {
            if ($id === $idQuienActua) {
                throw new ErrorNegocio('No puede desactivar su propia cuenta.');
            }
            $cuenta = self::obtener($id);
            if ($cuenta !== null && $cuenta['rol'] === 'administrador' && self::esUltimoAdministrador($id)) {
                throw new ErrorNegocio('No se puede desactivar al único administrador activo.');
            }
        }
        BaseDatos::ejecutar('UPDATE cuentas SET activo = :a WHERE id = :id', ['a' => $activo, 'id' => $id]);
    }

    public static function registrarAcceso(int $id): void
    {
        BaseDatos::ejecutar('UPDATE cuentas SET ultimo_acceso = now() WHERE id = :id', ['id' => $id]);
    }

    private static function esUltimoAdministrador(int $id): bool
    {
        $otros = BaseDatos::valor(
            "SELECT count(*) FROM cuentas WHERE rol = 'administrador' AND activo AND id <> :id",
            ['id' => $id]
        );
        return (int) $otros === 0;
    }
}
