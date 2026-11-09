# Arquitectura

```
app/            Lógica PHP (fuera del alcance de Apache)
  configuracion/  BaseDatos (PDO), ErrorNegocio
  helpers/        entorno, seguridad (CSRF, e()), respuestas (flash, paginación, insignias), autenticacion
  modelos/        Libro, Ejemplar, Usuario (lectores), Cuenta, Prestamo, Panel  -> consultas SQL
  servicios/      PrestamoService, DevolucionService, Informe, ImportadorExcel -> reglas de negocio
public/         Único directorio que sirve Apache (DocumentRoot)
  css/ js/ libros/ ejemplares/ usuarios/ prestamos/ informes/ importacion/ administracion/
vistas/         Plantillas: layouts (cabecera/pie) y componentes (paginación, estado vacío)
database/       sql/ (scripts) e import/ (Excel)
docker/         Configuración de Apache y PHP
scripts/        crear_administrador.php, instalar.ps1
tests/          ejecutar.php
docs/
```

Cada página de `public/` hace tres cosas: (1) incluye `app/inicio.php` y comprueba sesión/rol, (2) procesa el formulario usando un modelo o servicio, (3) muestra HTML con la plantilla.
Los POST validan CSRF; los cambios de estado usan formularios POST con confirmación.
Los préstamos y devoluciones usan transacciones con `SELECT ... FOR UPDATE`; además un índice único parcial garantiza un solo préstamo activo por ejemplar.
`app/`, `vistas/`, `database/`, `scripts/` y `.env` no son accesibles desde el navegador porque el DocumentRoot es `public/`.
