-- Índices, restricciones adicionales y triggers. Seguro de repetir.

-- ---------- Restricciones de integridad ----------
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'libros_paginas_positivas') THEN
        ALTER TABLE libros ADD CONSTRAINT libros_paginas_positivas
            CHECK (paginas IS NULL OR paginas > 0);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'prestamos_limite_valido') THEN
        ALTER TABLE prestamos ADD CONSTRAINT prestamos_limite_valido
            CHECK (fecha_limite >= fecha_prestamo);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'prestamos_devolucion_valida') THEN
        ALTER TABLE prestamos ADD CONSTRAINT prestamos_devolucion_valida
            CHECK (fecha_devolucion IS NULL OR fecha_devolucion >= fecha_prestamo);
    END IF;
    -- Coherencia entre estado y fecha de devolución
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'prestamos_estado_coherente') THEN
        ALTER TABLE prestamos ADD CONSTRAINT prestamos_estado_coherente
            CHECK ((estado = 'activo' AND fecha_devolucion IS NULL)
                OR (estado = 'devuelto' AND fecha_devolucion IS NOT NULL));
    END IF;
END $$;

-- Un ejemplar no puede tener dos préstamos activos a la vez.
CREATE UNIQUE INDEX IF NOT EXISTS ux_prestamos_ejemplar_activo
    ON prestamos (ejemplar_id) WHERE estado = 'activo';

-- ---------- Índices de búsqueda ----------
CREATE INDEX IF NOT EXISTS ix_libros_titulo      ON libros (lower(titulo));
CREATE INDEX IF NOT EXISTS ix_libros_autor       ON libros (lower(autor));
CREATE INDEX IF NOT EXISTS ix_libros_categoria   ON libros (categoria);
CREATE INDEX IF NOT EXISTS ix_usuarios_apellidos ON usuarios (lower(apellidos), lower(nombres));
CREATE INDEX IF NOT EXISTS ix_ejemplares_libro   ON ejemplares (libro_id);
CREATE INDEX IF NOT EXISTS ix_ejemplares_estado  ON ejemplares (estado);
CREATE INDEX IF NOT EXISTS ix_prestamos_usuario  ON prestamos (usuario_id);
CREATE INDEX IF NOT EXISTS ix_prestamos_ejemplar ON prestamos (ejemplar_id);
CREATE INDEX IF NOT EXISTS ix_prestamos_fecha    ON prestamos (fecha_prestamo);
CREATE INDEX IF NOT EXISTS ix_prestamos_limite   ON prestamos (fecha_limite) WHERE estado = 'activo';
CREATE INDEX IF NOT EXISTS ix_intentos_login     ON intentos_login (correo, created_at);
CREATE INDEX IF NOT EXISTS ix_intentos_login_ip  ON intentos_login (ip, created_at);

-- ---------- updated_at automático ----------
CREATE OR REPLACE FUNCTION actualizar_updated_at() RETURNS trigger AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DO $$
DECLARE t text;
BEGIN
    FOREACH t IN ARRAY ARRAY['cuentas', 'usuarios', 'libros', 'ejemplares', 'prestamos'] LOOP
        IF NOT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'trg_' || t || '_updated_at') THEN
            EXECUTE format('CREATE TRIGGER %I BEFORE UPDATE ON %I
                            FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at()',
                           'trg_' || t || '_updated_at', t);
        END IF;
    END LOOP;
END $$;
