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
        $zip = new ZipArchive();

        if ($zip->open($rutaArchivo) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo. Verifica que sea un .xlsx válido.');
        }

        $cadenasCompartidas = self::leerCadenasCompartidas($zip);

        $hojaXml = $zip->getFromName('xl/worksheets/sheet1.xml');

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

        ksort($filas);

        return array_values($filas);
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
