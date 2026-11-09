<?php

declare(strict_types=1);

/**
 * Lectura de inventarios .xlsx sin librerías externas (un .xlsx es un ZIP con XML)
 * y creación de libros/ejemplares a partir de ellos.
 */
final class ImportadorExcel
{
    public const CARPETA = RAIZ . '/database/import';
    public const MAX_BYTES = 5 * 1024 * 1024;

    /** @return list<string> nombres de archivos .xlsx disponibles */
    public static function archivosDisponibles(): array
    {
        $lista = glob(self::CARPETA . '/*.xlsx') ?: [];
        $nombres = array_map('basename', $lista);
        sort($nombres);
        return $nombres;
    }

    /** Devuelve la ruta completa solo si el nombre corresponde a un archivo de la carpeta de importación. */
    public static function rutaSegura(string $nombre): ?string
    {
        if ($nombre === '' || $nombre !== basename($nombre) || !preg_match('/\.xlsx$/i', $nombre)) {
            return null;
        }
        $ruta = self::CARPETA . '/' . $nombre;
        return is_file($ruta) ? $ruta : null;
    }

    /**
     * Valida y guarda un archivo subido. Devuelve el nombre guardado.
     * @throws ErrorNegocio
     */
    public static function guardarSubida(array $archivo): string
    {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new ErrorNegocio('No se pudo recibir el archivo. Verifique que no supere 5 MB.');
        }
        if ($archivo['size'] > self::MAX_BYTES) {
            throw new ErrorNegocio('El archivo supera el tamaño máximo de 5 MB.');
        }
        if (!preg_match('/\.xlsx$/i', (string) $archivo['name'])) {
            throw new ErrorNegocio('Solo se aceptan archivos Excel con extensión .xlsx.');
        }
        $tmp = (string) $archivo['tmp_name'];
        if (!is_uploaded_file($tmp) || file_get_contents($tmp, false, null, 0, 4) !== "PK\x03\x04") {
            throw new ErrorNegocio('El archivo no es un libro de Excel (.xlsx) válido.');
        }
        self::leer($tmp); // lanza ErrorNegocio si el contenido no es un xlsx legible

        $base = preg_replace('/[^A-Za-z0-9_\-]+/', '_', pathinfo((string) $archivo['name'], PATHINFO_FILENAME)) ?: 'inventario';
        $nombre = substr($base, 0, 60) . '_' . date('Ymd_His') . '.xlsx';
        if (!move_uploaded_file($tmp, self::CARPETA . '/' . $nombre)) {
            throw new ErrorNegocio('No se pudo guardar el archivo en el servidor.');
        }
        return $nombre;
    }

    /**
     * Lee la primera hoja y devuelve las filas como listas de texto.
     * @return list<list<string>>
     * @throws ErrorNegocio
     */
    public static function leer(string $ruta): array
    {
        $zip = new ZipArchive();
        if ($zip->open($ruta, ZipArchive::RDONLY) !== true) {
            throw new ErrorNegocio('No se pudo abrir el archivo Excel.');
        }
        try {
            $hoja = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($hoja === false) {
                // Buscar la primera hoja disponible
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $n = (string) $zip->getNameIndex($i);
                    if (preg_match('#^xl/worksheets/[^/]+\.xml$#', $n)) {
                        $hoja = $zip->getFromIndex($i);
                        break;
                    }
                }
            }
            if ($hoja === false || $hoja === null) {
                throw new ErrorNegocio('El archivo Excel no contiene hojas legibles.');
            }
            $compartidas = self::cadenasCompartidas($zip->getFromName('xl/sharedStrings.xml') ?: '');
        } finally {
            $zip->close();
        }

        $previo = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($hoja, 'SimpleXMLElement', LIBXML_NONET);
        libxml_use_internal_errors($previo);
        if ($xml === false) {
            throw new ErrorNegocio('El contenido de la hoja de Excel no es válido.');
        }

        $filas = [];
        foreach ($xml->sheetData->row ?? [] as $fila) {
            $numero = (int) $fila['r'];
            $celdas = [];
            foreach ($fila->c as $c) {
                $col = self::indiceColumna((string) $c['r']);
                $tipo = (string) $c['t'];
                if ($tipo === 's') {
                    $valor = $compartidas[(int) $c->v] ?? '';
                } elseif ($tipo === 'inlineStr') {
                    $valor = (string) ($c->is->t ?? '');
                } else {
                    $valor = (string) $c->v;
                    if (is_numeric($valor) && str_contains($valor, '.') && (float) $valor == (int) (float) $valor) {
                        $valor = (string) (int) (float) $valor; // 190.0 -> 190
                    }
                }
                $celdas[$col] = trim($valor);
            }
            $max = $celdas ? max(array_keys($celdas)) : -1;
            $lista = [];
            for ($i = 0; $i <= $max; $i++) {
                $lista[] = $celdas[$i] ?? '';
            }
            $filas[$numero] = $lista;
        }
        return $filas;
    }

    private static function cadenasCompartidas(string $contenido): array
    {
        if ($contenido === '') {
            return [];
        }
        $previo = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contenido, 'SimpleXMLElement', LIBXML_NONET);
        libxml_use_internal_errors($previo);
        if ($xml === false) {
            return [];
        }
        $lista = [];
        foreach ($xml->si as $si) {
            $texto = '';
            if (isset($si->t)) {
                $texto = (string) $si->t;
            } else {
                foreach ($si->r as $r) {
                    $texto .= (string) $r->t;
                }
            }
            $lista[] = $texto;
        }
        return $lista;
    }

    private static function indiceColumna(string $referencia): int
    {
        preg_match('/^[A-Z]+/', strtoupper($referencia), $m);
        $indice = 0;
        foreach (str_split($m[0] ?? 'A') as $letra) {
            $indice = $indice * 26 + (ord($letra) - 64);
        }
        return $indice - 1;
    }

    /** Clave para comparar textos: sin tildes, minúsculas y sin espacios repetidos. */
    public static function normalizar(string $texto): string
    {
        $t = mb_strtolower(trim($texto), 'UTF-8');
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        return preg_replace('/[^a-z0-9]+/', ' ', $t) ?? $t;
    }

    /**
     * Interpreta las filas del Excel: detecta la fila de encabezados y clasifica cada registro.
     *
     * @param list<list<string>> $filas
     * @return array{registros:list<array>, leidas:int, vacias:int, incompletas:list<array>, encabezado:int}
     * @throws ErrorNegocio
     */
    public static function interpretar(array $filas): array
    {
        $encabezado = null;
        $mapa = [];
        foreach ($filas as $numero => $celdas) {
            $normal = array_map([self::class, 'normalizar'], $celdas);
            $t = array_search('titulo', $normal, true);
            if ($t !== false) {
                $encabezado = $numero;
                $mapa['titulo'] = $t;
                $mapa['autor'] = array_search('autor', $normal, true);
                $mapa['editorial'] = array_search('editorial', $normal, true);
                foreach (['pag', 'paginas', 'numero de paginas'] as $alias) {
                    $p = array_search($alias, $normal, true);
                    if ($p !== false) {
                        $mapa['paginas'] = $p;
                        break;
                    }
                }
                break;
            }
        }
        if ($encabezado === null || ($mapa['autor'] ?? false) === false) {
            throw new ErrorNegocio('No se encontraron las columnas "Titulo" y "Autor" en el archivo.');
        }

        $registros = [];
        $incompletas = [];
        $vacias = 0;
        $leidas = 0;
        foreach ($filas as $numero => $celdas) {
            if ($numero <= $encabezado) {
                continue;
            }
            $tomar = static fn(string $campo): string => isset($mapa[$campo]) && $mapa[$campo] !== false
                ? ($celdas[$mapa[$campo]] ?? '') : '';
            $r = [
                'fila' => $numero,
                'titulo' => $tomar('titulo'),
                'autor' => $tomar('autor'),
                'editorial' => $tomar('editorial'),
                'paginas' => $tomar('paginas'),
            ];
            if ($r['titulo'] === '' && $r['autor'] === '' && $r['editorial'] === '' && $r['paginas'] === '') {
                $vacias++;
                continue;
            }
            $leidas++;
            $motivos = [];
            if ($r['titulo'] === '') {
                $motivos[] = 'falta el título';
            }
            if ($r['autor'] === '') {
                $motivos[] = 'falta el autor';
            }
            if ($r['paginas'] !== '' && (!ctype_digit($r['paginas']) || (int) $r['paginas'] < 1)) {
                $motivos[] = 'páginas no válidas ("' . $r['paginas'] . '")';
            }
            if (longitud($r['titulo']) > 255 || longitud($r['autor']) > 255 || longitud($r['editorial']) > 150) {
                $motivos[] = 'texto demasiado largo';
            }
            if ($motivos) {
                $incompletas[] = $r + ['motivo' => implode(', ', $motivos)];
                continue;
            }
            $registros[] = $r;
        }
        return ['registros' => $registros, 'leidas' => $leidas, 'vacias' => $vacias, 'incompletas' => $incompletas, 'encabezado' => $encabezado];
    }

    /**
     * Informe previo a la importación.
     * Filas con igual título + autor + editorial se agrupan como EJEMPLARES de un mismo libro.
     * Los libros cuyo título + autor ya existen en la base se omiten (así la importación es repetible).
     *
     * @return array<string,mixed>
     */
    public static function analizar(string $ruta): array
    {
        $info = self::interpretar(self::leer($ruta));

        $existentes = [];
        foreach (BaseDatos::filas('SELECT titulo, autor FROM libros') as $l) {
            $existentes[self::normalizar($l['titulo']) . '|' . self::normalizar($l['autor'])] = true;
        }

        $grupos = [];
        foreach ($info['registros'] as $r) {
            $clave = self::normalizar($r['titulo']) . '|' . self::normalizar($r['autor']) . '|' . self::normalizar($r['editorial']);
            if (!isset($grupos[$clave])) {
                $grupos[$clave] = $r + ['ejemplares' => 0, 'filas' => [], 'paginas_distintas' => false];
            }
            $grupos[$clave]['ejemplares']++;
            $grupos[$clave]['filas'][] = $r['fila'];
            if ($r['paginas'] !== $grupos[$clave]['paginas']) {
                $grupos[$clave]['paginas_distintas'] = true;
            }
        }

        $nuevos = [];
        $yaExisten = [];
        $repetidos = [];
        foreach ($grupos as $g) {
            $claveLibro = self::normalizar($g['titulo']) . '|' . self::normalizar($g['autor']);
            if (isset($existentes[$claveLibro])) {
                $yaExisten[] = $g;
                continue;
            }
            // Mismo título y autor con otra editorial: se importan como libros separados, pero se avisa
            $nuevos[] = $g;
            if ($g['ejemplares'] > 1 || $g['paginas_distintas']) {
                $repetidos[] = $g;
            }
        }
        $totalEjemplares = array_sum(array_column($nuevos, 'ejemplares'));

        return $info + [
            'nuevos' => $nuevos,
            'ya_existen' => $yaExisten,
            'agrupados' => $repetidos,
            'libros_a_crear' => count($nuevos),
            'ejemplares_a_crear' => $totalEjemplares,
        ];
    }

    /**
     * Inserta libros y ejemplares en una sola transacción.
     * @return array{libros:int, ejemplares:int}
     */
    public static function importar(string $ruta): array
    {
        $pdo = BaseDatos::conexion();
        $pdo->beginTransaction();
        try {
            // Bloqueo para que dos importaciones simultáneas no dupliquen libros
            $pdo->exec('LOCK TABLE libros IN SHARE ROW EXCLUSIVE MODE');
            $analisis = self::analizar($ruta);
            $libros = 0;
            $ejemplares = 0;
            foreach ($analisis['nuevos'] as $g) {
                $idLibro = Libro::crear([
                    'titulo' => $g['titulo'], 'autor' => $g['autor'], 'editorial' => $g['editorial'],
                    'paginas' => $g['paginas'], 'isbn' => '', 'categoria' => '', 'descripcion' => '', 'ubicacion' => '',
                ]);
                Ejemplar::crear($idLibro, $g['ejemplares'], null, 'Importado desde ' . basename($ruta));
                $libros++;
                $ejemplares += $g['ejemplares'];
            }
            $pdo->commit();
            return ['libros' => $libros, 'ejemplares' => $ejemplares];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Compara dos inventarios por título + autor.
     * @return array<string,mixed>
     */
    public static function comparar(string $rutaA, string $rutaB): array
    {
        $a = self::resumenPorLibro(self::interpretar(self::leer($rutaA)));
        $b = self::resumenPorLibro(self::interpretar(self::leer($rutaB)));
        $soloA = [];
        $soloB = [];
        $diferencias = [];
        foreach ($a['libros'] as $clave => $fa) {
            if (!isset($b['libros'][$clave])) {
                $soloA[] = $fa;
                continue;
            }
            $fb = $b['libros'][$clave];
            $motivos = [];
            if ($fa['cantidad'] !== $fb['cantidad']) {
                $motivos[] = "cantidad de filas {$fa['cantidad']} vs {$fb['cantidad']}";
            }
            if (self::normalizar($fa['editorial']) !== self::normalizar($fb['editorial'])) {
                $motivos[] = "editorial \"{$fa['editorial']}\" vs \"{$fb['editorial']}\"";
            }
            if ($fa['paginas'] !== $fb['paginas']) {
                $motivos[] = "páginas \"{$fa['paginas']}\" vs \"{$fb['paginas']}\"";
            }
            if ($motivos) {
                $diferencias[] = ['titulo' => $fa['titulo'], 'autor' => $fa['autor'], 'motivo' => implode('; ', $motivos)];
            }
        }
        foreach ($b['libros'] as $clave => $fb) {
            if (!isset($a['libros'][$clave])) {
                $soloB[] = $fb;
            }
        }
        return ['a' => $a, 'b' => $b, 'solo_a' => $soloA, 'solo_b' => $soloB, 'diferencias' => $diferencias];
    }

    private static function resumenPorLibro(array $info): array
    {
        $libros = [];
        foreach ($info['registros'] as $r) {
            $clave = self::normalizar($r['titulo']) . '|' . self::normalizar($r['autor']);
            if (!isset($libros[$clave])) {
                $libros[$clave] = $r + ['cantidad' => 0];
            }
            $libros[$clave]['cantidad']++;
        }
        return ['libros' => $libros, 'leidas' => $info['leidas'], 'incompletas' => count($info['incompletas']), 'filas_validas' => count($info['registros'])];
    }
}
