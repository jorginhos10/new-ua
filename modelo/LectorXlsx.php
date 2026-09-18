<?php

/**
 * Lee la primera hoja de un archivo .xlsx subido (formato OOXML), usando solo ZipArchive y
 * SimpleXML (sin librerías externas). Soporta tanto texto en "sharedStrings.xml" (lo habitual
 * al guardar desde Excel/LibreOffice) como texto inline.
 */
class LectorXlsx
{
    /**
     * Lee las propiedades personalizadas del documento (docProps/custom.xml) — donde
     * GeneradorXlsx::descargar() incrusta la firma de la plataforma y quién descargó el
     * archivo. Devuelve un array vacío si el archivo no tiene esas propiedades (ej. no fue
     * generado por esta plataforma, o es un .xlsx cualquiera).
     *
     * @return array<string, string> Nombre de propiedad => valor.
     */
    public static function leerMetadatos(string $rutaArchivo): array
    {
        $zip = new ZipArchive();

        if ($zip->open($rutaArchivo) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo. Verifica que sea un .xlsx válido.');
        }

        $xml = $zip->getFromName('docProps/custom.xml');
        $zip->close();

        if ($xml === false) {
            return [];
        }

        $documento = simplexml_load_string($xml);

        if ($documento === false) {
            return [];
        }

        $espacioNombres = $documento->getNamespaces(true);
        $vt = $espacioNombres['vt'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes';

        $metadatos = [];
        foreach ($documento->property as $propiedad) {
            $nombre = (string) $propiedad['name'];
            $valores = $propiedad->children($vt);
            $valor = '';
            foreach ($valores as $hijo) {
                $valor = (string) $hijo;
                break;
            }
            $metadatos[$nombre] = $valor;
        }

        return $metadatos;
    }

    /**
     * @return array<int, array<int, string>> Filas (0-indexadas), cada una como lista de
     *         valores de celda en texto plano, en orden de columna (A, B, C, ...).
     */
    public static function leerPrimeraHoja(string $rutaArchivo): array
    {
        return self::leerHoja($rutaArchivo, 1);
    }

    /**
     * Igual que leerPrimeraHoja(), pero para cualquier hoja del libro por su número de orden
     * (1 = la primera). OJO: asume que la hoja N vive en "xl/worksheets/sheetN.xml" — eso es
     * cierto para un archivo recién generado por GeneradorXlsx, pero si el usuario lo abrió y
     * volvió a guardar en Excel/LibreOffice antes de subirlo, el programa es libre de renumerar
     * esas partes internas (el nombre del archivo interno ya no tiene por qué corresponder con el
     * orden de las pestañas). Para leer una hoja de un archivo que pudo haber sido reguardado, usa
     * leerHojaPorNombre() en su lugar, que resuelve la hoja por su NOMBRE real (el de la pestaña),
     * que Excel sí conserva.
     *
     * @return array<int, array<int, string>>
     */
    public static function leerHoja(string $rutaArchivo, int $numeroHoja): array
    {
        return self::leerHojaPorRuta($rutaArchivo, 'xl/worksheets/sheet' . $numeroHoja . '.xml');
    }

    /**
     * Lee una hoja por su NOMBRE (el de la pestaña en Excel), resolviéndolo primero contra
     * xl/workbook.xml (nombre de hoja => r:id) y xl/_rels/workbook.xml.rels (r:id => archivo físico
     * real) en vez de asumir una convención de nombres como "sheet1.xml", "sheet2.xml" — esa
     * convención solo es válida para un archivo recién descargado; en cuanto el usuario lo abre y
     * lo vuelve a guardar en Excel/LibreOffice, el programa reescribe el paquete completo y es
     * libre de renumerar esas partes internas sin conservar el orden original. Sin esto, importar()
     * podía terminar leyendo la hoja "Gastos" pensando que era "Ingresos" (o viceversa) en archivos
     * reguardados, con el síntoma de "0 ingresos importados" aunque la hoja sí tuviera datos.
     *
     * @return array<int, array<int, string>>
     */
    public static function leerHojaPorNombre(string $rutaArchivo, string $nombreHoja): array
    {
        $zip = new ZipArchive();

        if ($zip->open($rutaArchivo) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo. Verifica que sea un .xlsx válido.');
        }

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $zip->close();

        if ($workbookXml === false || $relsXml === false) {
            throw new RuntimeException('El archivo no tiene una estructura de libro válida.');
        }

        $workbookDoc = simplexml_load_string($workbookXml);
        $relsDoc = simplexml_load_string($relsXml);

        if ($workbookDoc === false || $relsDoc === false) {
            throw new RuntimeException('No se pudo leer la estructura del libro.');
        }

        $espacioR = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $rId = null;

        foreach ($workbookDoc->sheets->sheet as $hoja) {
            if ((string) $hoja['name'] === $nombreHoja) {
                $atributosR = $hoja->attributes($espacioR);
                $rId = (string) $atributosR['id'];
                break;
            }
        }

        if ($rId === null) {
            throw new RuntimeException("El archivo no contiene una hoja llamada \"$nombreHoja\".");
        }

        $destino = null;

        foreach ($relsDoc->Relationship as $relacion) {
            if ((string) $relacion['Id'] === $rId) {
                $destino = (string) $relacion['Target'];
                break;
            }
        }

        if ($destino === null) {
            throw new RuntimeException("No se pudo resolver la hoja \"$nombreHoja\" dentro del archivo.");
        }

        // Target en workbook.xml.rels es relativo a "xl/" (ej. "worksheets/sheet3.xml").
        return self::leerHojaPorRuta($rutaArchivo, 'xl/' . ltrim($destino, '/'));
    }

    /**
     * @return array<int, array<int, string>>
     */
    private static function leerHojaPorRuta(string $rutaArchivo, string $rutaHojaEnZip): array
    {
        $zip = new ZipArchive();

        if ($zip->open($rutaArchivo) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo. Verifica que sea un .xlsx válido.');
        }

        $cadenasCompartidas = self::leerCadenasCompartidas($zip);

        $hojaXml = $zip->getFromName($rutaHojaEnZip);

        if ($hojaXml === false) {
            $zip->close();
            throw new RuntimeException('El archivo no contiene una hoja de datos válida.');
        }

        $zip->close();

        $documento = simplexml_load_string($hojaXml);

        if ($documento === false) {
            throw new RuntimeException('No se pudo leer el contenido de la hoja.');
        }

        $filas = [];

        foreach ($documento->sheetData->row as $filaXml) {
            $numeroFila = (int) $filaXml['r'];
            $valoresFila = [];

            foreach ($filaXml->c as $celdaXml) {
                $referencia = (string) $celdaXml['r'];
                $indiceColumna = self::indiceColumna($referencia);
                $tipo = (string) $celdaXml['t'];

                $valor = self::valorCelda($celdaXml, $tipo, $cadenasCompartidas);
                $valoresFila[$indiceColumna] = $valor;
            }

            if (empty($valoresFila)) {
                continue;
            }

            $maxIndice = max(array_keys($valoresFila));
            $filaCompleta = [];
            for ($i = 0; $i <= $maxIndice; $i++) {
                $filaCompleta[$i] = $valoresFila[$i] ?? '';
            }

            $filas[$numeroFila - 1] = $filaCompleta;
        }

        if (empty($filas)) {
            return [];
        }

        // OJO: no usar array_values() aquí. Filas que no tienen ninguna celda (ej. la fila 6, en
        // blanco entre el bloque de validación y el encabezado real de la tabla) nunca se agregan
        // arriba, dejando huecos en $filas — si se reindexara con array_values(), esos huecos se
        // "comerían" un puesto y CORRERÍAN una fila hacia arriba todo lo que viene después (típicamente
        // la fila 8, la primera fila de datos reales que reemplaza el ejemplo, terminaba leyéndose
        // como si fuera la fila 9, y la fila 8 real desaparecía sin ningún error — la causa de
        // "0 ingresos importados" reportada al probar la plantilla). Para que el índice siga
        // significando siempre "número de fila − 1" (que es lo que asume importar() al calcular a
        // qué fila de Excel corresponde cada elemento), se rellenan los huecos con filas vacías en
        // vez de reindexar.
        $filaMaxima = max(array_keys($filas));
        $filasCompletas = [];
        for ($i = 0; $i <= $filaMaxima; $i++) {
            $filasCompletas[] = $filas[$i] ?? [];
        }

        return $filasCompletas;
    }

    private static function valorCelda(SimpleXMLElement $celdaXml, string $tipo, array $cadenasCompartidas): string
    {
        if ($tipo === 'inlineStr') {
            return (string) ($celdaXml->is->t ?? '');
        }

        if ($tipo === 's') {
            $indice = (int) $celdaXml->v;

            return $cadenasCompartidas[$indice] ?? '';
        }

        return (string) $celdaXml->v;
    }

    private static function leerCadenasCompartidas(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $documento = simplexml_load_string($xml);

        if ($documento === false) {
            return [];
        }

        $cadenas = [];
        foreach ($documento->si as $si) {
            if (isset($si->t)) {
                $cadenas[] = (string) $si->t;
            } else {
                // Texto con formato mixto (varios <r><t>...): concatena los fragmentos.
                $texto = '';
                foreach ($si->r as $fragmento) {
                    $texto .= (string) $fragmento->t;
                }
                $cadenas[] = $texto;
            }
        }

        return $cadenas;
    }

    /**
     * Convierte una referencia de celda (ej. "C7") en un índice de columna 0-based (ej. 2).
     */
    private static function indiceColumna(string $referencia): int
    {
        $letras = preg_replace('/[0-9]/', '', $referencia);
        $indice = 0;

        foreach (str_split($letras) as $letra) {
            $indice = $indice * 26 + (ord($letra) - 64);
        }

        return $indice - 1;
    }
}
