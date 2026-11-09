# Especificación de desarrollo — Sistema de Gestión de Biblioteca Escolar

## Instrucciones para Claude Code

Actúa como desarrollador PHP senior. Trabaja directamente en la carpeta del proyecto: inspecciona los archivos existentes, crea la aplicación completa, configura Docker, PostgreSQL y Git, implementa las funciones y ejecuta las pruebas.

**No te limites a explicar o proponer código. Crea los archivos reales y deja el sistema funcionando.**

El proyecto es académico y debe ser fácil de comprender y explicar por un estudiante que está aprendiendo desarrollo de software. Queremos una aplicación sencilla, bonita, segura y funcional. No necesitamos una arquitectura empresarial ni tecnologías innecesarias.

---

# 1. Contexto y objetivo

El proyecto se llama **Sistema de Gestión de Biblioteca Escolar**.

Su propósito es organizar la información de la biblioteca de una institución educativa:

- Mantener el inventario de libros.
- Registrar las personas que utilizan la biblioteca.
- Registrar préstamos y devoluciones.
- Consultar qué libros y ejemplares están disponibles.
- Consultar préstamos activos, vencidos e históricos.
- Generar informes semanales, mensuales, semestrales y anuales.

El resultado debe ser una aplicación web que pueda utilizar la persona encargada de la biblioteca desde un navegador.

## 1.1 Inventario inicial disponible

Se mencionaron dos archivos Excel:

- `nolfy_luz.md`: revisión preliminar de aproximadamente 134 registros con título.
- `base_de_datos.md`: revisión preliminar de aproximadamente 124 registros con título.

Los campos identificados son:

- Título.
- Autor.
- Editorial.
- Número de páginas.

La diferencia preliminar es de 10 registros. No asumir que uno de los archivos es el correcto ni eliminar duplicados automáticamente. Comparar los archivos si están disponibles y generar un informe de diferencias para que la persona responsable confirme cuál inventario utilizar.

Si Claude Code no encuentra los archivos, continuar el desarrollo sin inventar registros y dejar documentado dónde deben copiarse para realizar la importación.

---

# 2. Tecnologías y restricciones obligatorias

Utilizar únicamente tecnologías sencillas que puedan ejecutarse sin procesos de compilación.

| Área | Tecnología |
|---|---|
| Lenguaje backend | **PHP 8.5 puro, sin framework** |
| Base de datos | PostgreSQL |
| Conexión a PostgreSQL | PDO con extensión `pdo_pgsql` |
| Frontend | HTML5, CSS y JavaScript nativo |
| Diseño | Bootstrap 5 mediante CDN |
| Iconos | Bootstrap Icons mediante CDN |
| Servidor web | Apache |
| Contenedores | Docker y Docker Compose |
| Dependencias PHP | Composer solo si se necesita una librería externa |
| Control de versiones | Git |

## 2.1 No utilizar

- Laravel, Symfony, CodeIgniter ni ningún framework PHP.
- Astro, React, Vue, Angular ni otros frameworks frontend.
- Node.js, npm, Vite, Webpack ni herramientas de compilación.
- TypeScript, Sass o herramientas que requieran compilar archivos.
- Microservicios.
- Redis, RabbitMQ, Kubernetes o servicios que no sean necesarios.
- Una API separada o una SPA.

**La aplicación debe ejecutarse directamente desde archivos `.php`.** Bootstrap se puede cargar mediante CDN. El CSS propio debe ser CSS normal y el JavaScript debe ser JavaScript nativo.

Composer no es obligatorio para el funcionamiento básico. Se puede utilizar para instalar una librería puntual, por ejemplo PhpSpreadsheet para leer archivos Excel, pero no debe convertirse en una dependencia innecesaria.

Antes de instalar una librería, comprobar que sea compatible con PHP 8.5.

---

# 3. Convenciones de código e idioma

Toda la aplicación debe estar en español:

- Interfaz.
- Menús.
- Formularios.
- Mensajes.
- Validaciones.
- Documentación.
- Comentarios del código.

Convenciones:

- Usar `camelCase` para variables y funciones.
- Usar `PascalCase` para clases.
- Usar nombres de tablas y columnas PostgreSQL en `snake_case`, en minúsculas y sin comillas.
- Los comentarios deben estar en español y solo agregarse cuando sean útiles.
- Mantener funciones pequeñas y con una responsabilidad clara.
- Evitar código duplicado.
- Seguir buenas prácticas de PHP y PSR-12.
- Utilizar `declare(strict_types=1);` en los archivos PHP cuando sea conveniente.
- Separar la lógica de acceso a datos de las pantallas.
- No crear capas, interfaces o patrones complejos si no aportan valor.

---

# 4. Funcionalidades

## 4.1 Inicio de sesión

Crear una pantalla de login limpia y profesional.

Requisitos:

- Inicio y cierre de sesión.
- No permitir registro público.
- Crear el primer administrador mediante un script de instalación o comando PHP seguro.
- Almacenar contraseñas con `password_hash()`.
- Verificar contraseñas con `password_verify()`.
- Regenerar el ID de sesión después del inicio de sesión.
- Destruir correctamente la sesión al cerrar sesión.
- Usar tokens CSRF en los formularios.
- Limitar intentos de inicio de sesión.
- No revelar si el correo o usuario existe cuando el acceso falla.
- Proteger las páginas internas comprobando la sesión.
- No permitir el acceso de cuentas inactivas.
- No guardar contraseñas en texto plano.

## 4.2 Roles

Crear dos roles:

**Administrador**
- Acceso a todos los módulos.
- Puede crear y administrar las cuentas internas.
- Puede administrar libros, ejemplares, lectores, préstamos, devoluciones e informes.

**Bibliotecario**
- Puede administrar libros, ejemplares, lectores, préstamos, devoluciones e informes.
- No puede administrar las cuentas internas.

La validación de permisos debe realizarse en PHP en cada operación. Ocultar un botón en pantalla no es suficiente.

Importante: las cuentas que ingresan al sistema son diferentes de las personas que piden libros. No mezclar las cuentas de acceso con los lectores de la biblioteca.

## 4.3 Dashboard

Después de iniciar sesión, mostrar:

- Total de títulos.
- Total de ejemplares físicos.
- Ejemplares disponibles.
- Préstamos activos.
- Préstamos vencidos.
- Últimos préstamos registrados.

Incluir accesos directos a libros, lectores, préstamos e informes.

Los indicadores deben provenir de consultas reales a PostgreSQL. Si no hay registros, mostrar un mensaje de estado vacío.

## 4.4 CRUD de libros

Implementar:

- Listado de libros.
- Registro.
- Consulta de detalle.
- Edición.
- Desactivación y reactivación.
- Búsqueda por título o autor.
- Filtros y paginación.

Campos:

- Título (obligatorio).
- Autor (obligatorio).
- Editorial (opcional).
- Número de páginas (opcional, entero positivo).
- ISBN (opcional).
- Categoría (opcional).
- Descripción (opcional).
- Ubicación o sección (opcional).
- Estado activo/inactivo.

No borrar físicamente libros con historial relacionado. Utilizar desactivación.

## 4.5 Gestión de ejemplares

Un libro representa un título bibliográfico. Un ejemplar representa una copia física.

Ejemplo: si la biblioteca tiene tres copias de un mismo título, debe existir un libro y tres ejemplares asociados.

Campos:

- Libro asociado.
- Código de inventario único.
- Estado: disponible, dañado, perdido o inactivo.
- Observaciones.

Funciones:

- Crear ejemplares.
- Consultar ejemplares de un libro.
- Editar observaciones.
- Cambiar el estado.
- Consultar disponibilidad.

El estado prestado se debe determinar mediante la existencia de un préstamo activo. No guardar simultáneamente un estado prestado en el ejemplar que pueda contradecir los préstamos.

## 4.6 CRUD de lectores de la biblioteca

Son las personas que solicitan libros.

Campos:

- Identificación (obligatoria y única).
- Nombres (obligatorio).
- Apellidos (obligatorio).
- Tipo: estudiante, docente o administrativo.
- Grado o curso (opcional).
- Teléfono (opcional).
- Correo (opcional).
- Estado activo/inactivo.

Funciones:

- Registrar.
- Listar.
- Consultar.
- Editar.
- Desactivar y reactivar.
- Buscar por identificación o nombre.

No eliminar lectores que tengan historial de préstamos.

## 4.7 Préstamos

Cada préstamo corresponde a un ejemplar físico y a un lector.

Datos:

- Lector.
- Ejemplar.
- Fecha de préstamo.
- Fecha límite de devolución.
- Fecha real de devolución (vacía mientras esté activo).
- Estado: activo o devuelto.
- Observaciones.
- Usuario interno que registró el préstamo.

Reglas:

- Solo prestar ejemplares disponibles.
- El lector debe estar activo.
- El ejemplar debe estar activo y en condiciones de préstamo.
- No permitir dos préstamos activos del mismo ejemplar.
- La fecha límite no puede ser anterior a la fecha del préstamo.
- Al registrar el préstamo, el ejemplar deja de estar disponible.
- Conservar el historial.
- Un préstamo está vencido cuando sigue activo y su fecha límite es anterior a la fecha actual.

La operación debe utilizar una transacción de PostgreSQL para evitar que dos personas presten simultáneamente el mismo ejemplar.

## 4.8 Devoluciones

- Buscar préstamos activos.
- Mostrar lector, libro, código de ejemplar y fechas.
- Registrar fecha de devolución.
- Cambiar el préstamo a devuelto.
- El ejemplar vuelve a estar disponible si no está dañado, perdido o inactivo.
- No permitir devolver dos veces el mismo préstamo.
- Conservar el historial.

## 4.9 Informes

Crear informes con filtros por fechas:

- Inventario general.
- Ejemplares disponibles.
- Ejemplares prestados.
- Ejemplares dañados o perdidos.
- Préstamos activos.
- Préstamos vencidos.
- Historial de préstamos.
- Préstamos semanales.
- Préstamos mensuales.
- Préstamos semestrales.
- Préstamos anuales.
- Libros más prestados.

Permitir exportar los resultados a CSV. La exportación a Excel puede quedar como mejora opcional.

Los filtros y cálculos deben realizarse en PostgreSQL, no cargando todos los registros en memoria PHP.

---

# 5. Modelo de base de datos

Crear una base de datos normalizada, sencilla y con relaciones claras.

## 5.1 Tabla `cuentas`

Cuentas internas que pueden iniciar sesión.

Campos sugeridos:

- `id`
- `nombre`
- `correo` (único)
- `password`
- `rol` (`administrador` o `bibliotecario`)
- `activo`
- `ultimo_acceso` (opcional)
- `created_at`
- `updated_at`

## 5.2 Tabla `usuarios`

Lectores de la biblioteca.

- `id`
- `identificacion` (única)
- `nombres`
- `apellidos`
- `tipo_usuario`
- `grado` (nullable)
- `telefono` (nullable)
- `correo` (nullable)
- `activo`
- `created_at`
- `updated_at`

## 5.3 Tabla `libros`

- `id`
- `titulo`
- `autor`
- `editorial` (nullable)
- `paginas` (nullable)
- `isbn` (nullable)
- `categoria` (nullable)
- `descripcion` (nullable)
- `ubicacion` (nullable)
- `activo`
- `created_at`
- `updated_at`

## 5.4 Tabla `ejemplares`

- `id`
- `libro_id` (FK a libros)
- `codigo_inventario` (único)
- `estado` (`disponible`, `danado`, `perdido`, `inactivo`)
- `observaciones` (nullable)
- `created_at`
- `updated_at`

## 5.5 Tabla `prestamos`

- `id`
- `usuario_id` (FK a usuarios)
- `ejemplar_id` (FK a ejemplares)
- `cuenta_id` (FK a cuentas, quien registra)
- `fecha_prestamo`
- `fecha_limite`
- `fecha_devolucion` (nullable)
- `estado` (`activo`, `devuelto`)
- `observaciones` (nullable)
- `created_at`
- `updated_at`

## 5.6 Integridad de datos

- Llaves primarias y foráneas.
- Identificación única por lector.
- Correo único por cuenta.
- Código de inventario único.
- Índices para búsquedas por título, autor, identificación, código y fechas.
- Número de páginas positivo si se registra.
- Fecha límite igual o posterior a fecha de préstamo.
- Fecha de devolución igual o posterior a fecha de préstamo.
- No permitir dos préstamos activos del mismo ejemplar. Utilizar un índice único parcial de PostgreSQL sobre `ejemplar_id` cuando `estado = 'activo'`.
- No borrar en cascada préstamos históricos.
- Utilizar restricciones `RESTRICT` cuando corresponda y desactivación lógica.
- Utilizar transacciones en préstamos y devoluciones.

Crear un diagrama entidad-relación sencillo en `docs/MODELO_DATOS.md`, preferiblemente con Mermaid.

---

# 6. Scripts SQL obligatorios

Generar scripts SQL independientes de PHP. Deben poder ejecutarse fácilmente desde el contenedor PostgreSQL con `psql`.

Estructura:

```text
database/
├── sql/
│   ├── 00_crear_base_datos.sql
│   ├── 01_crear_tablas.sql
│   ├── 02_indices_y_restricciones.sql
│   ├── 03_datos_demostracion.sql
│   └── README_SQL.md
└── import/
```

Requisitos:

- `00_crear_base_datos.sql`: crear la base `biblioteca_escolar`. Explicar que se ejecuta conectado a `postgres`.
- `01_crear_tablas.sql`: crear todas las tablas y relaciones.
- `02_indices_y_restricciones.sql`: crear índices y restricciones que no estén en el script anterior.
- `03_datos_demostracion.sql`: datos ficticios para probar el sistema.
- `README_SQL.md`: explicar el orden de ejecución y los comandos.

No incluir contraseñas en texto plano en los scripts SQL. Crear la cuenta inicial mediante un pequeño script PHP que utilice `password_hash()`.

Los scripts deben probarse en una base vacía. No incluir comandos destructivos como `DROP TABLE` o `TRUNCATE` en los scripts normales. Mantener los scripts SQL coherentes con la estructura de la aplicación.

Ejemplo de ejecución (ajustar usuario y nombre de servicio a los definidos en Docker Compose):

```powershell
docker compose exec -T db psql -U biblioteca -d postgres -f /scripts/00_crear_base_datos.sql
docker compose exec -T db psql -U biblioteca -d biblioteca_escolar -f /scripts/01_crear_tablas.sql
docker compose exec -T db psql -U biblioteca -d biblioteca_escolar -f /scripts/02_indices_y_restricciones.sql
docker compose exec -T db psql -U biblioteca -d biblioteca_escolar -f /scripts/03_datos_demostracion.sql
```

Montar `./database/sql` en el servicio PostgreSQL como `/scripts` de solo lectura. Si `POSTGRES_DB` crea la base automáticamente, explicar cuándo se puede omitir el primer script.

---

# 7. Diseño UX/UI

La aplicación debe ser agradable, moderna y fácil de usar para una persona que no sea técnica.

## Estilo

- Diseño claro, luminoso y limpio.
- Fondo gris muy suave o blanco.
- Tarjetas blancas.
- Azul como color principal.
- Bordes redondeados moderados.
- Sombras discretas.
- Tipografía legible.
- Iconos Bootstrap Icons.
- Buen contraste.
- Sin exceso de colores, animaciones o elementos decorativos.
- No usar tema oscuro como predeterminado.

## Estructura

- Menú lateral en escritorio.
- Menú adaptable en móvil.
- Barra superior con nombre del usuario y cierre de sesión.
- Área principal con título de sección.
- Dashboard con indicadores.
- Formularios organizados.
- Tablas con búsqueda, filtros y paginación.
- Confirmación antes de desactivar registros.
- Mensajes de éxito y error en español.
- Estados vacíos que expliquen qué hacer.
- Etiquetas de estado con texto e iconos, no solo colores.

## Pantallas mínimas

1. Login.
2. Dashboard.
3. Listado de libros.
4. Formulario crear/editar libro.
5. Detalle del libro y sus ejemplares.
6. Listado de lectores.
7. Formulario crear/editar lector.
8. Listado de ejemplares.
9. Registrar préstamo.
10. Préstamos activos.
11. Registrar devolución.
12. Historial de préstamos.
13. Informes.
14. Administración de cuentas.
15. Importación de inventario, si se implementa.

No mostrar botones que no funcionen.

---

# 8. Estructura de carpetas PHP

Mantener una estructura sencilla, sin imitar la complejidad de un framework.

Propuesta:

```text
biblioteca-escolar/
├── app/
│   ├── configuracion/
│   │   └── base_datos.php
│   ├── helpers/
│   │   ├── autenticacion.php
│   │   ├── seguridad.php
│   │   └── respuestas.php
│   ├── modelos/
│   │   ├── Libro.php
│   │   ├── Ejemplar.php
│   │   ├── Usuario.php
│   │   └── Prestamo.php
│   └── servicios/
│       ├── PrestamoService.php
│       └── DevolucionService.php
├── public/
│   ├── css/
│   │   └── estilos.css
│   ├── js/
│   │   └── aplicacion.js
│   ├── imagenes/
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── libros/
│   ├── ejemplares/
│   ├── usuarios/
│   ├── prestamos/
│   ├── informes/
│   └── administracion/
├── vistas/
│   ├── componentes/
│   └── layouts/
├── database/
│   ├── sql/
│   └── import/
├── docker/
│   └── apache/
├── docs/
├── scripts/
│   └── crear_administrador.php
├── tests/
├── .env.example
├── .gitignore
├── Dockerfile
├── compose.yaml
└── README.md
```

Puedes ajustar la estructura si encuentras una forma más sencilla, pero mantén separados:

- Archivos públicos que Apache puede servir.
- Lógica PHP.
- Configuración y credenciales.
- Vistas.
- Scripts SQL.
- Documentación.

El `DocumentRoot` de Apache debe apuntar a `public/`. No exponer `app/`, `vistas/`, `database/`, `scripts/` ni `.env` desde el navegador.

No es obligatorio crear una clase por cada operación. Evita sobrearquitectura.

---

# 9. Docker

El usuario ya tiene Docker Desktop instalado en Windows y Git instalado.

Crear:

- `Dockerfile`
- `compose.yaml`
- Configuración Apache si se necesita.
- `.dockerignore`
- `.env.example`

## Servicios

### `app`

- PHP 8.5 con Apache.
- Extensiones `pdo`, `pdo_pgsql`, `mbstring`, `intl`, `zip` y otras solo si son necesarias.
- DocumentRoot apuntando a `/var/www/html/public`.
- Habilitar `mod_rewrite` solo si se utiliza.
- Configurar zona horaria `America/Bogota`.

### `db`

- PostgreSQL 18 o una versión estable compatible.
- Volumen persistente.
- Healthcheck.
- Usuario y contraseña definidos mediante variables de entorno.
- No publicar el puerto de PostgreSQL al host salvo que sea necesario para administración.

La aplicación debe conectarse a PostgreSQL utilizando el nombre del servicio Docker, por ejemplo `db`, no `localhost`.

No incluir Node.js ni un servicio de compilación.

## Variables de entorno

Crear `.env.example` con:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_TIMEZONE=America/Bogota

DB_HOST=db
DB_PORT=5432
DB_DATABASE=biblioteca_escolar
DB_USERNAME=biblioteca
DB_PASSWORD=cambiar_esta_clave
```

Crear un mecanismo sencillo para leer variables de entorno desde PHP. No subir el `.env` real a Git.

## Comandos que deben documentarse

```powershell
# Construir e iniciar
docker compose up -d --build

# Ver contenedores
docker compose ps

# Ver logs
docker compose logs -f

# Entrar al contenedor PHP
docker compose exec app bash

# Ejecutar scripts SQL
docker compose exec db psql -U biblioteca -d biblioteca_escolar

# Detener sin borrar datos
docker compose down
```

Advertir que `docker compose down -v` elimina el volumen y puede borrar la base de datos. No ejecutar ese comando automáticamente.

La URL local debe ser `http://localhost:8080`, salvo que se configure otro puerto.

---

# 10. Seguridad

Aunque sea un proyecto académico, implementar seguridad real:

- Usar PDO y consultas preparadas para todos los datos ingresados por usuarios.
- No concatenar valores de formularios en consultas SQL.
- Validar los datos en el servidor.
- Escapar las salidas HTML con `htmlspecialchars()`.
- Usar `password_hash()` y `password_verify()`.
- Implementar tokens CSRF.
- Regenerar el ID de sesión al iniciar sesión.
- Configurar cookies de sesión `HttpOnly` y `SameSite`; usar `Secure` cuando haya HTTPS.
- Comprobar autenticación en cada página privada.
- Comprobar permisos en cada operación.
- Limitar intentos de login.
- No mostrar errores internos ni consultas SQL al usuario.
- Guardar errores técnicos en logs sin incluir contraseñas ni datos personales innecesarios.
- Validar tipo, tamaño y contenido de archivos Excel subidos.
- No permitir que archivos subidos se ejecuten.
- Utilizar transacciones para préstamos, devoluciones e importaciones.
- Crear instrucciones para hacer copias de seguridad y restaurarlas.
- No usar datos reales de estudiantes en datos de demostración.

No exponer `.env`, archivos de configuración, scripts SQL ni código privado mediante Apache.

---

# 11. Importación de Excel

La importación es útil porque existe un inventario inicial, pero no debe bloquear la entrega de las funciones principales.

Si los Excel están disponibles:

1. Revisar las columnas y cantidad de filas.
2. Comparar los dos archivos.
3. Detectar filas vacías, datos incompletos y posibles duplicados.
4. Crear un informe previo a la importación.
5. Permitir confirmar antes de insertar.
6. Evitar duplicar títulos al ejecutar la importación más de una vez.
7. No asumir que dos filas con el mismo título son necesariamente duplicadas: podrían representar ejemplares diferentes.
8. Informar cuántas filas se leyeron, cuántas se importarían y cuántas requieren revisión.

Se puede usar PhpSpreadsheet mediante Composer para leer `.xlsx`, solo si es compatible con PHP 8.5. No instalar una herramienta que requiera Node ni compilación.

Si no se dispone de los Excel, dejar la importación documentada como pendiente y continuar con el sistema.

---

# 12. Git

Git ya está instalado.

- Verificar si la carpeta ya es un repositorio.
- Revisar `git status` antes de cambiar archivos.
- No borrar ni sobrescribir cambios existentes.
- Si no existe repositorio, ejecutar `git init`.
- Crear `.gitignore` para PHP, Docker y Windows.
- Ignorar `.env`, logs, archivos temporales, backups y archivos privados.
- No ignorar scripts SQL, documentación ni archivos de configuración de ejemplo.
- No subir los Excel reales a un repositorio público sin autorización.
- Crear commits pequeños y descriptivos en español.

Ejemplos:

```text
Inicializa estructura del proyecto
Configura Docker y PostgreSQL
Implementa inicio de sesión y permisos
Implementa gestión de libros y ejemplares
Implementa gestión de lectores
Implementa préstamos y devoluciones
Agrega informes de biblioteca
Documenta instalación y uso
```

No inventar una URL remota. Si ya existe un remoto, mostrarlo antes de hacer `push`. No publicar el repositorio sin autorización explícita.

---

# 13. Pruebas

Crear pruebas sencillas, sin incorporar frameworks de pruebas pesados si no hacen falta. Se pueden utilizar scripts PHP de prueba y una base PostgreSQL separada.

Comprobar al menos:

1. No se puede acceder al dashboard sin iniciar sesión.
2. El login funciona con credenciales correctas.
3. El login rechaza credenciales incorrectas.
4. Una cuenta inactiva no puede ingresar.
5. Un bibliotecario no puede administrar cuentas.
6. Se puede crear, editar, consultar y desactivar un libro.
7. No se puede registrar dos veces la misma identificación.
8. Se pueden crear varios ejemplares para un título.
9. Se puede prestar un ejemplar disponible.
10. No se puede prestar un ejemplar que ya tiene un préstamo activo.
11. No se puede prestar un ejemplar dañado o perdido.
12. Se puede registrar una devolución.
13. No se puede devolver dos veces el mismo préstamo.
14. Los informes respetan los filtros de fechas.

No afirmar que las pruebas pasaron si no se ejecutaron.

---

# 14. Documentación que debe generarse

Crear estos archivos:

- `README.md`: descripción, requisitos, instalación, comandos y acceso.
- `docs/REQUERIMIENTOS.md`: requerimientos funcionales y no funcionales.
- `docs/ARQUITECTURA.md`: estructura de carpetas y explicación sencilla.
- `docs/MODELO_DATOS.md`: tablas, campos, relaciones y diagrama.
- `docs/MANUAL_USUARIO.md`: instrucciones para utilizar el sistema.
- `docs/MANUAL_TECNICO.md`: Docker, PHP, PostgreSQL, configuración y Git.
- `docs/PRUEBAS.md`: casos de prueba y resultados reales.
- `database/sql/README_SQL.md`: ejecución de scripts SQL.

La documentación debe ser sencilla, en español y apropiada para un proyecto académico.

---

# 15. Entregables

Al finalizar deben existir:

1. Aplicación web PHP funcional.
2. Login y cierre de sesión.
3. Roles de administrador y bibliotecario.
4. Dashboard.
5. CRUD de libros.
6. Gestión de ejemplares.
7. CRUD de lectores.
8. Préstamos.
9. Devoluciones.
10. Historial.
11. Informes por periodos.
12. Importación del inventario, si los archivos están disponibles.
13. Base de datos PostgreSQL.
14. Scripts SQL independientes.
15. Dockerfile y Docker Compose.
16. Diseño responsive con Bootstrap.
17. Pruebas básicas.
18. Documentación.
19. Repositorio Git local organizado.

---

# 16. Criterios de aceptación

- [ ] `docker compose up -d --build` inicia la aplicación y PostgreSQL.
- [ ] La aplicación abre en `http://localhost:8080`.
- [ ] PHP dentro del contenedor es versión 8.5.x.
- [ ] Apache sirve únicamente la carpeta `public/`.
- [ ] PHP se conecta correctamente a PostgreSQL.
- [ ] Los scripts SQL funcionan desde una base vacía.
- [ ] El usuario puede iniciar y cerrar sesión.
- [ ] Los permisos se validan en el backend.
- [ ] Los CRUD funcionan.
- [ ] No se puede prestar dos veces el mismo ejemplar.
- [ ] Las devoluciones actualizan la disponibilidad.
- [ ] Los informes consultan datos reales.
- [ ] La interfaz es responsive y está en español.
- [ ] No hay botones que no hagan nada.
- [ ] No hay credenciales reales en Git.
- [ ] La documentación explica cómo instalar y utilizar el sistema.

---

# 17. Orden de trabajo para Claude Code

## Paso 1: inspeccionar

- Revisar la carpeta actual.
- Revisar si existe código o repositorio Git.
- Revisar Docker.
- Buscar los archivos Excel.
- No borrar ni sobrescribir información existente.

## Paso 2: preparar el entorno

- Crear Dockerfile PHP 8.5 + Apache.
- Crear Compose con PostgreSQL.
- Crear `.env.example`.
- Crear la conexión PDO.
- Confirmar que PHP y PostgreSQL se comunican.

## Paso 3: crear la base de datos

- Crear scripts SQL.
- Crear las tablas y relaciones.
- Crear datos demostrativos.
- Probar los scripts en una base vacía.

## Paso 4: implementar la aplicación

- Crear login y permisos.
- Crear layout Bootstrap.
- Crear dashboard.
- Crear CRUD de libros y ejemplares.
- Crear CRUD de lectores.
- Crear préstamos y devoluciones.
- Crear informes.
- Crear importación Excel si los archivos están disponibles.

## Paso 5: validar y entregar

- Revisar seguridad.
- Ejecutar pruebas.
- Corregir errores.
- Revisar diseño en escritorio y móvil.
- Completar documentación.
- Revisar Git y crear commits.
- Entregar URL local y comandos para levantar el sistema.

## Reglas finales

- No te detengas después de entregar un plan: implementa.
- No uses frameworks ni herramientas de compilación.
- No instales Node.js ni npm.
- No agregues dependencias innecesarias.
- No simules funciones que no están implementadas.
- No inventes datos institucionales.
- Si falta una decisión menor, elige la opción más sencilla y documenta la decisión.
- Si una acción puede borrar datos existentes, detente y pide autorización.
- No borres volúmenes Docker ni bases de datos.
- No publiques el repositorio ni hagas push sin autorización.
- Informa qué se implementó, qué pruebas se ejecutaron y qué queda pendiente.

**Comienza inspeccionando el directorio y luego desarrolla el sistema completo con estas especificaciones.**
