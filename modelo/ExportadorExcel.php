<?php

/**
 * Genera un archivo .xlsx real (formato OOXML mínimo) sin depender de ninguna librería externa,
 * usando solo ZipArchive (ya incluida en PHP). Una sola hoja, celdas de texto (inlineStr) o
 * numéricas según el tipo PHP del valor.
 */
class ExportadorExcel
{
    public static function descargar(string $nombreArchivo, array $encabezados, array $filas): void
    {
        $columnas = count($encabezados);

        $construirFila = function (int $numeroFila, array $valores) use ($columnas): string {
            $celdas = '';

            for ($indiceColumna = 0; $indiceColumna < $columnas; $indiceColumna++) {
                $referencia = self::columnaLetra($indiceColumna) . $numeroFila;
                $valor = $valores[$indiceColumna] ?? '';

                if (is_int($valor) || is_float($valor)) {
                    $celdas .= '<c r="' . $referencia . '"><v>' . self::formatearNumero($valor) . '</v></c>';
                } else {
                    $texto = htmlspecialchars((string) $valor, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $celdas .= '<c r="' . $referencia . '" t="inlineStr"><is><t xml:space="preserve">' . $texto . '</t></is></c>';
                }
            }

            return '<row r="' . $numeroFila . '">' . $celdas . '</row>';
        };

        $numeroFila = 1;
        $filasXml = $construirFila($numeroFila++, $encabezados);

        foreach ($filas as $fila) {
            $filasXml .= $construirFila($numeroFila++, $fila);
        }

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $filasXml . '</sheetData></worksheet>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Consolidado" sheetId="1" r:id="rId1"/></sheets></workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>';

        $rutaTemporal = tempnam(sys_get_temp_dir(), 'xlsx');

        $zip = new ZipArchive();
        $zip->open($rutaTemporal, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rootRels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Content-Length: ' . filesize($rutaTemporal));
        readfile($rutaTemporal);
        unlink($rutaTemporal);
    }

    private static function columnaLetra(int $indice): string
    {
        $letra = '';
        $indice++;

        while ($indice > 0) {
            $resto = ($indice - 1) % 26;
            $letra = chr(65 + $resto) . $letra;
            $indice = intdiv($indice - 1, 26);
        }

        return $letra;
    }

    private static function formatearNumero($valor): string
    {
        return rtrim(rtrim(number_format((float) $valor, 4, '.', ''), '0'), '.');
    }
}
