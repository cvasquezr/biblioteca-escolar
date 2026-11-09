# Requerimientos

## Funcionales
- RF1 Inicio/cierre de sesión, sin registro público, límite de intentos, cuentas inactivas bloqueadas.
- RF2 Roles: *administrador* (todo, incluye cuentas) y *bibliotecario* (todo menos cuentas). Se valida en PHP en cada operación.
- RF3 Panel con totales de títulos, ejemplares, disponibles, préstamos activos y vencidos, y últimos préstamos.
- RF4 CRUD de libros con búsqueda, filtros, paginación y desactivación/reactivación (nunca borrado).
- RF5 Ejemplares: varias copias por libro, código único, estados disponible/dañado/perdido/inactivo; "prestado" se deduce del préstamo activo.
- RF6 CRUD de lectores (identificación única) con desactivación.
- RF7 Préstamos transaccionales con reglas (ejemplar disponible, lector activo, fechas válidas, sin doble préstamo).
- RF8 Devoluciones (una sola vez), con estado del libro al regresar.
- RF9 Informes: inventario, disponibles, prestados, dañados/perdidos, activos, vencidos, historial, semanales, mensuales, semestrales, anuales, más prestados; filtros por fecha y exportación CSV.
- RF10 Importación de inventario desde Excel con informe previo y confirmación; comparación de dos archivos.

## No funcionales
Seguridad (PDO preparado, CSRF, hash de contraseñas, cookies HttpOnly/SameSite, escape HTML, CSP), integridad (FK, CHECK, índice único parcial), interfaz en español, responsive, sin compilación, ejecutable con Docker.

## Decisiones tomadas
- Días de préstamo por defecto: 7 (editable por préstamo).
- Importación: filas con igual título+autor+editorial = ejemplares de un libro; libros con título+autor ya existentes se omiten; filas con "páginas" no numéricas se marcan para revisión y no se importan.
- Excel leído con `ZipArchive`+`SimpleXML` (sin Composer ni librerías).
- Un lector/libro no se puede desactivar si tiene préstamos activos; una cuenta no se desactiva a sí misma ni al último administrador.
- CSV con `;` y UTF-8 con BOM (para Excel en español).
- La cuenta técnica inactiva de los datos de demostración existe solo porque cada préstamo registra su cuenta.
