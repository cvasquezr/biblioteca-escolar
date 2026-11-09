# Manual de usuario

**Ingresar:** abra http://localhost:8080 y escriba su correo y contraseña. Tras 5 intentos fallidos se bloquea 15 minutos. Cambie su contraseña en el menú de su nombre → *Cambiar mi contraseña*.

**Libros:** *Libros → Nuevo libro*. Título y autor son obligatorios. En el detalle del libro agregue ejemplares (cantidad y, opcionalmente, un código). Desactivar un libro lo oculta para nuevos préstamos pero conserva su historial (se pide confirmación).

**Ejemplares:** estados disponible, dañado, perdido o inactivo. "Prestado" aparece solo cuando hay un préstamo activo.

**Lectores:** *Lectores → Nuevo lector*. La identificación no se puede repetir. Un lector con préstamos activos no se puede desactivar.

**Prestar:** *Nuevo préstamo*; escriba y elija el lector y el ejemplar de la lista, revise las fechas (7 días por defecto) y registre.

**Devolver:** *Devoluciones*; busque el préstamo, elija la fecha y el estado del libro (buen estado, dañado o perdido) y pulse *Devolver*.

**Consultas:** *Préstamos activos* (resalta los vencidos) e *Historial* (filtros por estado y fechas).

**Informes:** elija el informe y el rango de fechas, pulse *Generar* y, si desea, *Exportar CSV* (se abre con Excel).

**Importar inventario (Excel):**
1. Los archivos `.xlsx` están en `database/import/` (o súbalos en la pantalla).
2. Use *Comparar dos inventarios* para ver las diferencias; la persona responsable decide cuál usar.
3. Elija un archivo y revise el **informe previo** (filas leídas, libros nuevos, ya existentes, filas a revisar).
4. Marque la confirmación e importe. Repetir la importación no duplica libros.

**Cuentas (solo administrador):** *Cuentas del sistema* para crear bibliotecarios, cambiar roles, restablecer contraseñas y desactivar cuentas.
