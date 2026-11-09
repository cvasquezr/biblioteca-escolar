# Pruebas

Se ejecutan con `docker compose exec app php tests/ejecutar.php`. Crean una base separada
(`biblioteca_prueba`) con los scripts 01 y 02, levantan un servidor PHP temporal (puerto 8099) y la
eliminan al terminar. No tocan la base real.

## Resultado real de la última ejecución: 25 correctas, 0 fallidas

| # | Caso | Resultado |
|---|---|---|
| 1 | No se accede al dashboard (ni a otras páginas privadas) sin sesión | OK |
| 2 | Login con credenciales correctas | OK |
| 3 | Login con credenciales incorrectas; mensaje idéntico exista o no el correo | OK |
| 4 | Una cuenta inactiva no puede ingresar | OK |
| 5 | Un bibliotecario recibe 403 en cuentas (GET y POST) y no modifica nada | OK |
| 5b | POST sin token CSRF rechazado (419) | OK |
| 5c | Cierre de sesión destruye la sesión | OK |
| 5d | Límite de intentos de login (bloquea incluso con la clave correcta) | OK |
| 5e | Contraseñas guardadas con hash | OK |
| 6 | Crear, consultar, editar, desactivar y reactivar libro; validaciones | OK |
| 6b | Crear libro por la interfaz web y buscarlo sin tildes/mayúsculas | OK |
| 7 | No se repite la identificación de un lector | OK |
| 8 | Varios ejemplares por título, consecutivo y código duplicado | OK |
| 9 | Prestar un ejemplar disponible | OK |
| 10 | No prestar un ejemplar con préstamo activo | OK |
| 10b | La base impide dos préstamos activos (índice único parcial) | OK |
| 11 | No prestar ejemplar dañado, perdido o inactivo | OK |
| 11b | Reglas de fechas (límite anterior, préstamo futuro) y lector inactivo | OK |
| 12 | Devolución; el ejemplar vuelve a estar disponible | OK |
| 13 | No devolver dos veces | OK |
| 13b | Devolver dañado deja el ejemplar no disponible | OK |
| 13c | No desactivar lector/libro con préstamos activos | OK |
| 14 | Informes respetan filtros de fechas (incluye límites y agrupación mensual/anual) | OK |
| 14b | Exportación CSV respeta filtros; estado vacío en la pantalla | OK |
| 15 | Importación de Excel crea libros/ejemplares y repetirla no duplica | OK |

El resultado completo está en `tests/ultimo_resultado.txt`.

## Verificaciones manuales/adicionales realizadas
- Scripts SQL 00–03 ejecutados en base vacía y re-ejecutados (00, 01, 02 son repetibles).
- PHP en el contenedor: 8.5.12. Todas las pantallas responden 200 con sesión de administrador.
- Comparación de los Excel reales: `base_de_datos.xlsx` (124 filas) está contenido en `nolfy_luz.xlsx` (134 filas); difieren 10 títulos que solo están en `nolfy_luz`.

## No probado
- Revisión visual en navegador a distintos anchos (la automatización del navegador falló por otra extensión); se vio solo la pantalla de login.
- Concurrencia real de dos usuarios simultáneos (se prueba la restricción de la base, no dos sesiones en paralelo).
