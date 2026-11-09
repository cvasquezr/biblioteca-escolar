-- Tablas del Sistema de Gestión de Biblioteca Escolar.
-- Ejecutar conectado a biblioteca_escolar. Es seguro repetirlo (IF NOT EXISTS).

CREATE EXTENSION IF NOT EXISTS unaccent;

-- Cuentas internas que pueden iniciar sesión (NO son los lectores).
CREATE TABLE IF NOT EXISTS cuentas (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre        VARCHAR(120) NOT NULL,
    correo        VARCHAR(160) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,           -- hash de password_hash()
    rol           VARCHAR(20)  NOT NULL CHECK (rol IN ('administrador', 'bibliotecario')),
    activo        BOOLEAN      NOT NULL DEFAULT TRUE,
    ultimo_acceso TIMESTAMPTZ,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- Lectores: personas que piden libros prestados.
CREATE TABLE IF NOT EXISTS usuarios (
    id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    identificacion VARCHAR(30)  NOT NULL UNIQUE,
    nombres        VARCHAR(100) NOT NULL,
    apellidos      VARCHAR(100) NOT NULL,
    tipo_usuario   VARCHAR(20)  NOT NULL CHECK (tipo_usuario IN ('estudiante', 'docente', 'administrativo')),
    grado          VARCHAR(30),
    telefono       VARCHAR(30),
    correo         VARCHAR(160),
    activo         BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at     TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at     TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- Libros: un título bibliográfico.
CREATE TABLE IF NOT EXISTS libros (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    titulo      VARCHAR(255) NOT NULL,
    autor       VARCHAR(255) NOT NULL,
    editorial   VARCHAR(150),
    paginas     INTEGER,
    isbn        VARCHAR(20),
    categoria   VARCHAR(80),
    descripcion TEXT,
    ubicacion   VARCHAR(80),
    activo      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- Ejemplares: copias físicas de un libro.
-- "Prestado" no se guarda aquí: se deduce de la existencia de un préstamo activo.
CREATE TABLE IF NOT EXISTS ejemplares (
    id                BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libro_id          BIGINT      NOT NULL REFERENCES libros (id) ON DELETE RESTRICT,
    codigo_inventario VARCHAR(40) NOT NULL UNIQUE,
    estado            VARCHAR(20) NOT NULL DEFAULT 'disponible'
                      CHECK (estado IN ('disponible', 'danado', 'perdido', 'inactivo')),
    observaciones     TEXT,
    created_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at        TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Préstamos: historial completo, nunca se borra en cascada.
CREATE TABLE IF NOT EXISTS prestamos (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    usuario_id       BIGINT      NOT NULL REFERENCES usuarios (id)   ON DELETE RESTRICT,
    ejemplar_id      BIGINT      NOT NULL REFERENCES ejemplares (id) ON DELETE RESTRICT,
    cuenta_id        BIGINT      NOT NULL REFERENCES cuentas (id)    ON DELETE RESTRICT,
    fecha_prestamo   DATE        NOT NULL DEFAULT CURRENT_DATE,
    fecha_limite     DATE        NOT NULL,
    fecha_devolucion DATE,
    estado           VARCHAR(20) NOT NULL DEFAULT 'activo' CHECK (estado IN ('activo', 'devuelto')),
    observaciones    TEXT,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Intentos fallidos de inicio de sesión (limitación de intentos).
CREATE TABLE IF NOT EXISTS intentos_login (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    correo     VARCHAR(160) NOT NULL,
    ip         VARCHAR(45)  NOT NULL,
    created_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);
