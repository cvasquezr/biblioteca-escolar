<?php

declare(strict_types=1);

/** Registro de devoluciones dentro de una transacción. */
final class DevolucionService
{
    /** Estado del ejemplar al regresar: 'disponible', 'danado' o 'perdido'. */
    public static function registrar(int $prestamoId, string $fechaDevolucion, string $estadoEjemplar, string $observaciones): void
    {
        if (!in_array($estadoEjemplar, ['disponible', 'danado', 'perdido'], true)) {
            throw new ErrorNegocio('Seleccione el estado en que regresó el ejemplar.');
        }
        if (!fechaValida($fechaDevolucion)) {
            throw new ErrorNegocio('La fecha de devolución no es válida.');
        }
        if ($fechaDevolucion > hoy()) {
            throw new ErrorNegocio('La fecha de devolución no puede ser futura.');
        }

        $pdo = BaseDatos::conexion();
        $pdo->beginTransaction();
        try {
            $p = BaseDatos::fila('SELECT id, ejemplar_id, estado, fecha_prestamo, observaciones FROM prestamos WHERE id = :id FOR UPDATE', ['id' => $prestamoId]);
            if ($p === null) {
                throw new ErrorNegocio('El préstamo no existe.');
            }
            if ($p['estado'] !== 'activo') {
                throw new ErrorNegocio('Este préstamo ya fue devuelto.');
            }
            if ($fechaDevolucion < $p['fecha_prestamo']) {
                throw new ErrorNegocio('La fecha de devolución no puede ser anterior a la del préstamo.');
            }

            $nota = $observaciones === '' ? $p['observaciones']
                : trim(($p['observaciones'] ? $p['observaciones'] . ' | ' : '') . 'Devolución: ' . $observaciones);
            BaseDatos::ejecutar(
                "UPDATE prestamos SET estado = 'devuelto', fecha_devolucion = :f, observaciones = :o WHERE id = :id",
                ['f' => $fechaDevolucion, 'o' => $nota, 'id' => $prestamoId]
            );

            // Si regresó dañado o perdido el ejemplar deja de estar disponible. Si regresó bien,
            // vuelve a estar disponible solo por no tener préstamo activo (su estado no cambia).
            if ($estadoEjemplar !== 'disponible') {
                BaseDatos::ejecutar(
                    'UPDATE ejemplares SET estado = :e WHERE id = :id',
                    ['e' => $estadoEjemplar, 'id' => $p['ejemplar_id']]
                );
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
