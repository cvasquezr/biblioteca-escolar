# Modelo de datos

```mermaid
erDiagram
    cuentas ||--o{ prestamos : registra
    usuarios ||--o{ prestamos : solicita
    ejemplares ||--o{ prestamos : "se presta en"
    libros ||--o{ ejemplares : tiene
    cuentas {
      bigint id PK
      varchar nombre
      varchar correo UK
      varchar password
      varchar rol
      boolean activo
      timestamptz ultimo_acceso
    }
    usuarios {
      bigint id PK
      varchar identificacion UK
      varchar nombres
      varchar apellidos
      varchar tipo_usuario
      varchar grado
      varchar telefono
      varchar correo
      boolean activo
    }
    libros {
      bigint id PK
      varchar titulo
      varchar autor
      varchar editorial
      int paginas
      varchar isbn
      varchar categoria
      text descripcion
      varchar ubicacion
      boolean activo
    }
    ejemplares {
      bigint id PK
      bigint libro_id FK
      varchar codigo_inventario UK
      varchar estado
      text observaciones
    }
    prestamos {
      bigint id PK
      bigint usuario_id FK
      bigint ejemplar_id FK
      bigint cuenta_id FK
      date fecha_prestamo
      date fecha_limite
      date fecha_devolucion
      varchar estado
      text observaciones
    }
```
Todas las tablas incluyen `created_at` y `updated_at` (este último se actualiza por trigger). Tabla auxiliar: `intentos_login` (limitación de intentos).

## Reglas en la base de datos
- `cuentas.rol` ∈ {administrador, bibliotecario}; `usuarios.tipo_usuario` ∈ {estudiante, docente, administrativo}.
- `ejemplares.estado` ∈ {disponible, danado, perdido, inactivo}. **No existe estado "prestado"**: se deduce de un préstamo activo.
- `libros.paginas > 0`; `fecha_limite >= fecha_prestamo`; `fecha_devolucion >= fecha_prestamo`; estado `activo` ⇔ sin fecha de devolución.
- Índice único parcial `ux_prestamos_ejemplar_activo (ejemplar_id) WHERE estado='activo'`.
- Todas las claves foráneas son `ON DELETE RESTRICT`: no se borra en cascada el historial.
- Extensión `unaccent` para buscar sin importar tildes.
