-- Datos FICTICIOS de demostración. No contienen personas reales.
-- Ejecutar solo en una base vacía (si se repite, falla por duplicados y no cambia nada).
-- Los préstamos usan fechas relativas a hoy para que siempre haya activos y vencidos.

BEGIN;

-- Cuenta técnica INACTIVA, solo para poder registrar préstamos de ejemplo.
-- El valor de password no es un hash válido: esta cuenta no puede iniciar sesión.
INSERT INTO cuentas (nombre, correo, password, rol, activo)
VALUES ('Cuenta de demostración', 'demo@ejemplo.invalid', '!sin-acceso', 'bibliotecario', FALSE);

INSERT INTO libros (titulo, autor, editorial, paginas, isbn, categoria, descripcion, ubicacion) VALUES
('Cien años de soledad (demo)',   'Autor Ficticio Uno',    'Editorial Demo',   471, NULL, 'Novela',   'Libro de ejemplo para pruebas.', 'Estante A1'),
('Introducción a la Biología',    'Autora Ficticia Dos',   'Ediciones Prueba', 320, NULL, 'Ciencias', 'Texto escolar de ejemplo.',      'Estante B2'),
('Álgebra para todos',            'Autor Ficticio Tres',   'Editorial Demo',   280, NULL, 'Matemáticas', NULL,                      'Estante C1'),
('Cuentos para recordar',         'Autora Ficticia Cuatro','Ediciones Prueba', 120, NULL, 'Cuento',   NULL,                           'Estante A2'),
('Historia de Colombia ilustrada','Autor Ficticio Cinco',  'Editorial Demo',   410, NULL, 'Historia', NULL,                           'Estante D1'),
('Poemas del camino',             'Autora Ficticia Seis',  NULL,               96,  NULL, 'Poesía',   NULL,                           'Estante A3');

-- Ejemplares: códigos EJ-<libro>-<consecutivo>
INSERT INTO ejemplares (libro_id, codigo_inventario, estado, observaciones)
SELECT l.id, 'EJ-' || lpad(l.id::text, 5, '0') || '-' || lpad(n::text, 2, '0'), 'disponible', NULL
FROM libros l
CROSS JOIN generate_series(1, 3) AS n
WHERE l.titulo IN ('Cien años de soledad (demo)', 'Introducción a la Biología', 'Álgebra para todos')
UNION ALL
SELECT l.id, 'EJ-' || lpad(l.id::text, 5, '0') || '-' || lpad(n::text, 2, '0'), 'disponible', NULL
FROM libros l
CROSS JOIN generate_series(1, 2) AS n
WHERE l.titulo IN ('Cuentos para recordar', 'Historia de Colombia ilustrada', 'Poemas del camino');

-- Un ejemplar dañado y uno perdido
UPDATE ejemplares SET estado = 'danado',  observaciones = 'Hojas sueltas'
WHERE codigo_inventario = (SELECT min(codigo_inventario) FROM ejemplares WHERE libro_id = (SELECT id FROM libros WHERE titulo = 'Poemas del camino'));
UPDATE ejemplares SET estado = 'perdido', observaciones = 'No apareció en el inventario'
WHERE codigo_inventario = (SELECT max(codigo_inventario) FROM ejemplares WHERE libro_id = (SELECT id FROM libros WHERE titulo = 'Historia de Colombia ilustrada'));

INSERT INTO usuarios (identificacion, nombres, apellidos, tipo_usuario, grado, telefono, correo) VALUES
('DEMO-1001', 'Lector',  'Ficticio Uno',    'estudiante',     '10-A', '3000000001', 'lector1@ejemplo.invalid'),
('DEMO-1002', 'Lectora', 'Ficticia Dos',    'estudiante',     '11-B', NULL,         NULL),
('DEMO-1003', 'Docente', 'Ficticio Tres',   'docente',        NULL,   '3000000003', 'docente3@ejemplo.invalid'),
('DEMO-1004', 'Persona', 'Administrativa',  'administrativo', NULL,   NULL,         NULL);

-- Préstamos de ejemplo (fechas relativas a hoy)
INSERT INTO prestamos (usuario_id, ejemplar_id, cuenta_id, fecha_prestamo, fecha_limite, fecha_devolucion, estado, observaciones)
SELECT u.id, e.id, c.id, CURRENT_DATE - 30, CURRENT_DATE - 23, CURRENT_DATE - 25, 'devuelto', 'Devuelto a tiempo'
FROM usuarios u, ejemplares e, cuentas c
WHERE u.identificacion = 'DEMO-1001' AND e.codigo_inventario LIKE 'EJ-%-01' AND c.correo = 'demo@ejemplo.invalid'
  AND e.libro_id = (SELECT id FROM libros WHERE titulo = 'Álgebra para todos');

INSERT INTO prestamos (usuario_id, ejemplar_id, cuenta_id, fecha_prestamo, fecha_limite)
SELECT u.id, e.id, c.id, CURRENT_DATE - 15, CURRENT_DATE - 8      -- vencido
FROM usuarios u, ejemplares e, cuentas c
WHERE u.identificacion = 'DEMO-1002' AND c.correo = 'demo@ejemplo.invalid'
  AND e.codigo_inventario LIKE 'EJ-%-01'
  AND e.libro_id = (SELECT id FROM libros WHERE titulo = 'Cien años de soledad (demo)');

INSERT INTO prestamos (usuario_id, ejemplar_id, cuenta_id, fecha_prestamo, fecha_limite)
SELECT u.id, e.id, c.id, CURRENT_DATE - 2, CURRENT_DATE + 5       -- vigente
FROM usuarios u, ejemplares e, cuentas c
WHERE u.identificacion = 'DEMO-1003' AND c.correo = 'demo@ejemplo.invalid'
  AND e.codigo_inventario LIKE 'EJ-%-01'
  AND e.libro_id = (SELECT id FROM libros WHERE titulo = 'Introducción a la Biología');

COMMIT;
