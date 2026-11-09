<?php

declare(strict_types=1);

/** Registro de préstamos con todas las reglas de negocio, dentro de una transacción. */
final class PrestamoService
{
    public const DIAS_POR_DEFECTO = 7;

    /**
     * @param array{usuario_id:int, ejemplar_id:int, fecha_prestamo:string, fecha_limite:string, observaciones:string} $d
     * @return int id del préstamo creado
     * @throws ErrorNegocio si alguna regla no se cumple
     */
    public static function registrar(array $d, int $cuentaId): int
    {
        self::validarFechas($d['fecha_prestamo'], $d['fecha_limite']);

        $pdo = BaseDatos::conexion();
        $pdo->beginTransaction();
        try {
            // FOR UPDATE: si dos personas prestan el mismo ejemplar, la segunda espera a la primera
            $ejemplar = BaseDatos::fila(
                'SELECT e.id, e.estado, l.activo AS libro_activo, l.titulo
                 FROM ejemplares e JOIN libros l ON l.id = e.libro_id
                 WHERE e.id = :id FOR UPDATE OF e',
                ['id' => $d['ejemplar_id']]
            );
            if ($ejemplar === null) {
                throw new ErrorNegocio('Seleccione un ejemplar válido.');
            }
            if ($ejemplar['estado'] !== 'disponible') {
                throw new ErrorNegocio('El ejemplar no está en condiciones de préstamo (estado: '
                    . (ESTADOS_EJEMPLAR[$ejemplar['estado']] ?? $ejemplar['estado']) . ').');
            }
            if (!$ejemplar['libro_activo']) {
                throw new ErrorNegocio('El libro de este ejemplar está desactivado.');
            }

            $lector = BaseDatos::fila('SELECT id, activo FROM usuarios WHERE id = :id FOR SHARE', ['id' => $d['usuario_id']]);
            if ($lector === null) {
                throw new ErrorNegocio('Seleccione un lector válido.');
            }
            if (!$lector['activo']) {
                throw new ErrorNegocio('El lector está inactivo y no puede recibir préstamos.');
            }

            $yaPrestado = BaseDatos::valor(
                "SELECT 1 FROM prestamos WHERE ejemplar_id = :id AND estado = 'activo'",
                ['id' => $d['ejemplar_id']]
            );
            if ($yaPrestado !== null) {
                throw new ErrorNegocio('El ejemplar ya tiene un préstamo activo.');
            }

            try {
                $id = (int) BaseDatos::valor(
                    'INSERT INTO prestamos (usuario_id, ejemplar_id, cuenta_id, fecha_prestamo, fecha_limite, observaciones)
                     VALUES (:u, :e, :c, :fp, :fl, :o) RETURNING id',
                    [
                        'u' => $d['usuario_id'], 'e' => $d['ejemplar_id'], 'c' => $cuentaId,
                        'fp' => $d['fecha_prestamo'], 'fl' => $d['fecha_limite'],
                        'o' => nuloSiVacio($d['observaciones']),
                    ]
                );
            } catch (PDOException $ex) {
                // Respaldo: el índice único parcial también impide el doble préstamo
                if ($ex->getCode() === '23505') {
                    throw new ErrorNegocio('El ejemplar ya tiene un préstamo activo.');
                }
                throw $ex;
            }
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function validarFechas(string $prestamo, string $limite): void
    {
        if (!fechaValida($prestamo) || !fechaValida($limite)) {
            throw new ErrorNegocio('Las fechas no son válidas.');
        }
        if ($prestamo > hoy()) {
            throw new ErrorNegocio('La fecha del préstamo no puede ser futura.');
        }
        if ($limite < $prestamo) {
            throw new ErrorNegocio('La fecha límite no puede ser anterior a la fecha del préstamo.');
        }
    }
}
