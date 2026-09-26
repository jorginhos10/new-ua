<?php

/**
 * Genera una plantilla .xlsx (formato OOXML mínimo, solo con ZipArchive) con una hoja "Datos"
 * (formateada como tabla de Excel real, con paleta de colores azules) para diligenciar y una
 * hoja oculta "Listas" que alimenta desplegables (validación de datos) en las columnas indicadas.
 * No depende de ninguna librería externa.
 *
 * Además, incrusta metadatos (propiedades personalizadas del documento) que identifican que el
 * archivo fue generado por esta plataforma y quién lo descargó — LectorXlsx::leerMetadatos()
 * los lee de vuelta al importar, para rechazar archivos que no vengan de aquí.
 */
class GeneradorXlsx
{
    /** Firma fija que identifica un archivo como generado por esta plataforma. */
    public const FIRMA_PLATAFORMA = 'SPPI-UniAtlantico';

    /**
     * @param string[] $encabezados Encabezados de columna, en orden (A, B, C, ...).
     * @param array<int, string> $columnasConLista Índice de columna (0 = A) => nombre de la
     *        columna en $listas que alimenta su desplegable.
     * @param array<string, string[]> $listas Nombre de lista => valores permitidos.
     * @param array<int, string> $filaEjemplo Valores de una fila de ejemplo (mismo orden que $encabezados).
     * @param array{plantilla: string, usuario_id: int, usuario_nombre: string} $metadatos
     *        'plantilla' identifica el módulo (ej. 'gastos') para que la plantilla de un módulo
     *        no pueda importarse en otro; 'usuario_id'/'usuario_nombre' identifican a quién la
     *        descargó, para trazabilidad.
     * @param array{techo: float, columnaCantidad: int, columnaValorUnitario: int, columnaValorTotal: int}|null $validacionTecho
     *        Si se indica, agrega una fila 1 con Techo/Total/Disponible (el Total suma, con
     *        fórmula, la columna 'columnaValorTotal'; esa misma columna se llena en cada fila de
     *        datos con la fórmula Cantidad×Costo unitario) y corre la tabla una fila hacia abajo
     *        (encabezado en la fila 2, datos desde la fila 3). "Disponible" se colorea en rojo si
     *        queda negativo.
     */
    public static function descargar(
        string $nombreArchivo,
        array $encabezados,
        array $columnasConLista,
        array $listas,
        array $filaEjemplo,
        array $metadatos,
        ?array $validacionTecho = null
    ): void {
        $columnas = count($encabezados);

        $celdaTexto = static function (string $referencia, string $valor, ?int $estilo = null): string {
            $texto = htmlspecialchars($valor, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $atributoEstilo = $estilo !== null ? ' s="' . $estilo . '"' : '';

            return '<c r="' . $referencia . '"' . $atributoEstilo . ' t="inlineStr"><is><t xml:space="preserve">' . $texto . '</t></is></c>';
        };

        $celdaNumero = static function (string $referencia, float $valor, ?int $estilo = null): string {
            $atributoEstilo = $estilo !== null ? ' s="' . $estilo . '"' : '';

            return '<c r="' . $referencia . '"' . $atributoEstilo . '><v>' . $valor . '</v></c>';
        };

        $celdaFormula = static function (string $referencia, string $formula, ?int $estilo = null): string {
            $atributoEstilo = $estilo !== null ? ' s="' . $estilo . '"' : '';

            return '<c r="' . $referencia . '"' . $atributoEstilo . '><f>' . htmlspecialchars($formula, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</f></c>';
        };

        $filaEncabezado = $validacionTecho !== null ? 2 : 1;
        $numeroFilaEjemplo = $filaEncabezado + 1;
        $ultimaFilaDatos = $numeroFilaEjemplo + 199;

        // --- Hoja 1: Datos ---
        $filasXml = '';
        $conditionalFormattingXml = '';

        if ($validacionTecho !== null) {
            $letraValorTotal = self::columnaLetra($validacionTecho['columnaValorTotal']);
            $rangoValorTotal = '$' . $letraValorTotal . '$' . $numeroFilaEjemplo . ':$' . $letraValorTotal . '$' . $ultimaFilaDatos;

            $filasXml .= '<row r="1">'
                . $celdaTexto('A1', 'Techo', 1)
                . $celdaNumero('B1', $validacionTecho['techo'], 3)
                . $celdaTexto('C1', 'Total', 1)
                . $celdaFormula('D1', 'SUM(' . $rangoValorTotal . ')', 3)
                . $celdaTexto('E1', 'Disponible', 1)
                . $celdaFormula('F1', 'B1-D1', 3)
                . '</row>';

            $conditionalFormattingXml = '<conditionalFormatting sqref="F1">'
                . '<cfRule type="cellIs" dxfId="1" priority="1" operator="lessThan"><formula>0</formula></cfRule>'
                . '<cfRule type="cellIs" dxfId="0" priority="2" operator="greaterThanOrEqual"><formula>0</formula></cfRule>'
                . '</conditionalFormatting>';
        }

        $filasXml .= '<row r="' . $filaEncabezado . '">';
        for ($col = 0; $col < $columnas; $col++) {
            $filasXml .= $celdaTexto(self::columnaLetra($col) . $filaEncabezado, $encabezados[$col], 1);
        }
        $filasXml .= '</row>';

        if ($validacionTecho !== null) {
            // La columna de Valor total se llena con fórmula en las 200 filas de datos (no solo la
            // de ejemplo), igual que en las plantillas de Autogestión, para que calcule apenas se
            // diligencian Cantidad y Costo unitario.
            $letraCantidad = self::columnaLetra($validacionTecho['columnaCantidad']);
            $letraValorUnitario = self::columnaLetra($validacionTecho['columnaValorUnitario']);

            // Todas las filas de datos se ven iguales (sin bandas de color por fila): en vez de eso,
            // el fondo distingue el TIPO de columna, para que quien diligencia sepa de un vistazo
            // qué debe llenar sí o sí (blanco, las marcadas con *), qué es opcional (gris) y qué se
            // calcula solo (azul, fórmula Cantidad×Costo unitario) — se escribe cada celda aunque
            // esté vacía para que el color se vea también ahí.
            for ($fila = $numeroFilaEjemplo; $fila <= $ultimaFilaDatos; $fila++) {
                $filaXml = '<row r="' . $fila . '">';
                for ($col = 0; $col < $columnas; $col++) {
                    $esCalculada = $col === $validacionTecho['columnaValorTotal'];
                    $esObligatoria = !$esCalculada && str_contains($encabezados[$col], '*');
                    $estiloColumna = $esCalculada ? 5 : ($esObligatoria ? 2 : 4);

                    if ($esCalculada) {
                        $filaXml .= $celdaFormula(self::columnaLetra($col) . $fila, $letraCantidad . $fila . '*' . $letraValorUnitario . $fila, $estiloColumna);
                        continue;
                    }

                    $valorTexto = $fila === $numeroFilaEjemplo ? (string) ($filaEjemplo[$col] ?? '') : '';
                    $filaXml .= $celdaTexto(self::columnaLetra($col) . $fila, $valorTexto, $estiloColumna);
                }
                $filasXml .= $filaXml . '</row>';
            }
        } elseif (!empty($filaEjemplo)) {
            $filasXml .= '<row r="' . $numeroFilaEjemplo . '">';
            for ($col = 0; $col < $columnas; $col++) {
                $filasXml .= $celdaTexto(self::columnaLetra($col) . $numeroFilaEjemplo, (string) ($filaEjemplo[$col] ?? ''));
            }
            $filasXml .= '</row>';
        }

        $validaciones = '';
        foreach ($columnasConLista as $indiceColumna => $nombreLista) {
            $valores = $listas[$nombreLista] ?? [];
            $cantidadValores = count($valores);

            if ($cantidadValores === 0) {
                continue;
            }

            $letra = self::columnaLetraLista($nombreLista, $listas);
            $rango = 'Listas!$' . $letra . '$2:$' . $letra . '$' . ($cantidadValores + 1);
            $colLetra = self::columnaLetra($indiceColumna);

            $validaciones .= '<dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="1" errorTitle="Valor no válido" error="Selecciona un valor de la lista." sqref="' . $colLetra . $numeroFilaEjemplo . ':' . $colLetra . $ultimaFilaDatos . '">'
                . '<formula1>' . htmlspecialchars($rango, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</formula1>'
                . '</dataValidation>';
        }

        $ultimaColumnaLetra = self::columnaLetra($columnas - 1);
        $rangoTabla = 'A' . $filaEncabezado . ':' . $ultimaColumnaLetra . $ultimaFilaDatos;

        $sheet1Xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetData>' . $filasXml . '</sheetData>'
            . $conditionalFormattingXml
            . ($validaciones !== '' ? '<dataValidations count="' . count($columnasConLista) . '">' . $validaciones . '</dataValidations>' : '')
            . '<tableParts count="1"><tablePart r:id="rId1"/></tableParts>'
            . '</worksheet>';

        // --- Hoja 2: Listas (oculta) ---
        $nombresListas = array_keys($listas);
        $filasListasXml = '';
        $maxFilas = 0;
        foreach ($listas as $valores) {
            $maxFilas = max($maxFilas, count($valores));
        }

        $filaEncabezadoListas = '<row r="1">';
        foreach ($nombresListas as $indice => $nombreLista) {
            $filaEncabezadoListas .= $celdaTexto(self::columnaLetra($indice) . '1', $nombreLista);
        }
        $filaEncabezadoListas .= '</row>';
        $filasListasXml .= $filaEncabezadoListas;

        for ($fila = 0; $fila < $maxFilas; $fila++) {
            $numeroFila = $fila + 2;
            $filaXml = '<row r="' . $numeroFila . '">';
            foreach ($nombresListas as $indice => $nombreLista) {
                $valor = $listas[$nombreLista][$fila] ?? null;
                if ($valor !== null) {
                    $filaXml .= $celdaTexto(self::columnaLetra($indice) . $numeroFila, (string) $valor);
                }
            }
            $filaXml .= '</row>';
            $filasListasXml .= $filaXml;
        }

        $sheet2Xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $filasListasXml . '</sheetData>'
            . '</worksheet>';

        // --- Hoja 3: Metadatos (oculta) — las mismas propiedades de docProps/custom.xml, pero
        // en celdas visibles al desocultar la hoja, para poder confirmarlas sin ir a "Propiedades
        // avanzadas" del archivo. LectorXlsx sigue validando desde docProps/custom.xml (fuente de
        // verdad); esta hoja es solo para inspección humana. ---
        $filasMetadatos = [
            ['Campo', 'Valor'],
            ['SPPI_Origen', self::FIRMA_PLATAFORMA],
            ['SPPI_Plantilla', $metadatos['plantilla']],
            ['SPPI_UsuarioId', (string) $metadatos['usuario_id']],
            ['SPPI_UsuarioNombre', $metadatos['usuario_nombre']],
            ['SPPI_GeneradoEn', date('c')],
        ];

        $filasMetadatosXml = '';
        foreach ($filasMetadatos as $indiceFila => $filaMetadato) {
            $numeroFila = $indiceFila + 1;
            $estiloFila = $indiceFila === 0 ? 1 : null;
            $filasMetadatosXml .= '<row r="' . $numeroFila . '">'
                . $celdaTexto('A' . $numeroFila, $filaMetadato[0], $estiloFila)
                . $celdaTexto('B' . $numeroFila, $filaMetadato[1], $estiloFila)
                . '</row>';
        }

        $sheet3Xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $filasMetadatosXml . '</sheetData>'
            . '</worksheet>';

        // --- Tabla de Excel real (paleta azul TableStyleMedium2) sobre la hoja de Datos ---
        $tableXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<table xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" id="1" name="TablaDatos" displayName="TablaDatos" ref="' . $rangoTabla . '" totalsRowShown="0">'
            . '<autoFilter ref="' . $rangoTabla . '"/>'
            . '<tableColumns count="' . $columnas . '">'
            . implode('', array_map(static function (int $indice) use ($encabezados): string {
                $nombre = htmlspecialchars($encabezados[$indice], ENT_QUOTES | ENT_XML1, 'UTF-8');
                return '<tableColumn id="' . ($indice + 1) . '" name="' . $nombre . '"/>';
            }, range(0, $columnas - 1)))
            . '</tableColumns>'
            . '<tableStyleInfo name="TableStyleMedium2" showFirstColumn="0" showLastColumn="0" showRowStripes="0" showColumnStripes="0"/>'
            . '</table>';

        $sheet1Rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/table" Target="../tables/table1.xml"/>'
            . '</Relationships>';

        // --- Estilos: encabezado en negrilla blanca sobre fondo azul (paleta azul) ---
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;$&quot; #,##0.00"/></numFmts>'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="6">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF2F2F2"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDCE6F1"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="6">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="0" xfId="0" applyFill="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="4" borderId="0" xfId="0" applyFill="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="5" borderId="0" xfId="0" applyNumberFormat="1" applyFill="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '<dxfs count="2">'
            . '<dxf><font><color rgb="FF006100"/></font><fill><patternFill><bgColor rgb="FFC6EFCE"/></patternFill></fill></dxf>'
            . '<dxf><font><color rgb="FF9C0006"/></font><fill><patternFill><bgColor rgb="FFFFC7CE"/></patternFill></fill></dxf>'
            . '</dxfs>'
            . '</styleSheet>';

        // --- Metadatos: propiedades personalizadas que identifican plataforma + usuario ---
        $propiedad = static function (int $pid, string $nombre, string $valor): string {
            $texto = htmlspecialchars($valor, ENT_QUOTES | ENT_XML1, 'UTF-8');
            return '<property fmtid="{D5CDD505-2E9C-101B-9397-08002B2CF9AE}" pid="' . $pid . '" name="' . htmlspecialchars($nombre, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"><vt:lpwstr>' . $texto . '</vt:lpwstr></property>';
        };

        $customXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/custom-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . $propiedad(2, 'SPPI_Origen', self::FIRMA_PLATAFORMA)
            . $propiedad(3, 'SPPI_Plantilla', $metadatos['plantilla'])
            . $propiedad(4, 'SPPI_UsuarioId', (string) $metadatos['usuario_id'])
            . $propiedad(5, 'SPPI_UsuarioNombre', $metadatos['usuario_nombre'])
            . $propiedad(6, 'SPPI_GeneradoEn', date('c'))
            . '</Properties>';

        $coreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>' . htmlspecialchars($metadatos['usuario_nombre'], ENT_QUOTES | ENT_XML1, 'UTF-8') . '</dc:creator>'
            . '<cp:lastModifiedBy>' . htmlspecialchars($metadatos['usuario_nombre'], ENT_QUOTES | ENT_XML1, 'UTF-8') . '</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:created>'
            . '</cp:coreProperties>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/tables/table1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.table+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/custom.xml" ContentType="application/vnd.openxmlformats-officedocument.custom-properties+xml"/>'
            . '</Types>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/custom-properties" Target="docProps/custom.xml"/>'
            . '</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>'
            . '<sheet name="Datos" sheetId="1" r:id="rId1"/>'
            . '<sheet name="Listas" sheetId="2" r:id="rId2" state="hidden"/>'
            . '<sheet name="Metadatos" sheetId="3" r:id="rId4" state="hidden"/>'
            . '</sheets></workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>'
            . '</Relationships>';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');

        $archivoTemporal = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($archivoTemporal, ZipArchive::OVERWRITE);
        $zip->addEmptyDir('_rels');
        $zip->addEmptyDir('docProps');
        $zip->addEmptyDir('xl');
        $zip->addEmptyDir('xl/_rels');
        $zip->addEmptyDir('xl/worksheets');
        $zip->addEmptyDir('xl/worksheets/_rels');
        $zip->addEmptyDir('xl/tables');
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rootRels);
        $zip->addFromString('docProps/core.xml', $coreXml);
        $zip->addFromString('docProps/custom.xml', $customXml);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/styles.xml', $stylesXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1Xml);
        $zip->addFromString('xl/worksheets/sheet2.xml', $sheet2Xml);
        $zip->addFromString('xl/worksheets/sheet3.xml', $sheet3Xml);
        $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $sheet1Rels);
        $zip->addFromString('xl/tables/table1.xml', $tableXml);
        $zip->close();

        readfile($archivoTemporal);
        unlink($archivoTemporal);
    }

    /**
     * Genera y descarga un .xlsx de solo lectura con una o más hojas de datos simples: cada hoja
     * es una tabla con fila de encabezados en negrilla y filas de datos, sin validaciones, tabla
     * de Excel ni metadatos de plantilla — sirve para exportar el contenido tal cual, no para
     * volver a importarlo (a diferencia de descargar(), usado por "Exportar plantilla").
     *
     * @param array<int, array{nombre: string, encabezados: string[], filas: array<int, array<int, string>>, filasPrevias?: array<int, array<int, string>>}> $hojas
     *        'filasPrevias' (opcional) son filas libres antes del encabezado (ej. dependencia y
     *        techo), que corren la tabla (encabezado + datos) hacia abajo esa misma cantidad de filas.
     */
    public static function descargarHojas(string $nombreArchivo, array $hojas): void
    {
        $celdaTexto = static function (string $referencia, string $valor, ?int $estilo = null): string {
            $texto = htmlspecialchars($valor, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $atributoEstilo = $estilo !== null ? ' s="' . $estilo . '"' : '';

            return '<c r="' . $referencia . '"' . $atributoEstilo . ' t="inlineStr"><is><t xml:space="preserve">' . $texto . '</t></is></c>';
        };

        // Una celda puede ser texto plano (como siempre) o ['valor' => ..., 'estilo' => N] para
        // resaltarla: 1 = encabezado, 2 = valor cambiado (naranja), 3 = fila de total (negrita, gris,
        // borde superior), 4 = negrita simple. Así los llamadores existentes no cambian nada.
        $celda = static function (string $referencia, $valor, ?int $estiloPorDefecto = null) use ($celdaTexto): string {
            if (is_array($valor)) {
                return $celdaTexto($referencia, (string) ($valor['valor'] ?? ''), $valor['estilo'] ?? $estiloPorDefecto);
            }

            return $celdaTexto($referencia, (string) $valor, $estiloPorDefecto);
        };

        $hojas = array_values($hojas);
        $sheetsXml = [];
        $sheetsTags = '';
        $workbookRelsTags = '';

        foreach ($hojas as $indice => $hoja) {
            $numeroHoja = $indice + 1;
            $columnas = count($hoja['encabezados']);
            $filasPrevias = $hoja['filasPrevias'] ?? [];

            $filasXml = '';
            $numeroFila = 1;

            foreach ($filasPrevias as $filaPrevia) {
                $filaXml = '<row r="' . $numeroFila . '">';
                foreach (array_values($filaPrevia) as $col => $valor) {
                    $filaXml .= $celda(self::columnaLetra($col) . $numeroFila, $valor);
                }
                $filaXml .= '</row>';
                $filasXml .= $filaXml;
                $numeroFila++;
            }

            $filaXml = '<row r="' . $numeroFila . '">';
            for ($col = 0; $col < $columnas; $col++) {
                $filaXml .= $celdaTexto(self::columnaLetra($col) . $numeroFila, $hoja['encabezados'][$col], 1);
            }
            $filaXml .= '</row>';
            $filasXml .= $filaXml;
            $numeroFila++;

            foreach ($hoja['filas'] as $fila) {
                $filaXml = '<row r="' . $numeroFila . '">';
                for ($col = 0; $col < $columnas; $col++) {
                    $filaXml .= $celda(self::columnaLetra($col) . $numeroFila, $fila[$col] ?? '');
                }
                $filaXml .= '</row>';
                $filasXml .= $filaXml;
                $numeroFila++;
            }

            $sheetsXml[] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<sheetData>' . $filasXml . '</sheetData>'
                . '</worksheet>';

            $nombreHojaSeguro = htmlspecialchars(mb_substr($hoja['nombre'], 0, 31), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $sheetsTags .= '<sheet name="' . $nombreHojaSeguro . '" sheetId="' . $numeroHoja . '" r:id="rId' . $numeroHoja . '"/>';
            $workbookRelsTags .= '<Relationship Id="rId' . $numeroHoja . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $numeroHoja . '.xml"/>';
        }

        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFC96500"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="5">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFE3B3"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF0F0F2"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left/><right/><top style="medium"><color rgb="FF1D1D1F"/></top><bottom/><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="5">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . implode('', array_map(
                static fn (int $numero): string => '<Override PartName="/xl/worksheets/sheet' . $numero . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>',
                range(1, count($hojas))
            ))
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetsTags . '</sheets></workbook>';

        $idRelacionEstilos = count($hojas) + 1;
        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $workbookRelsTags
            . '<Relationship Id="rId' . $idRelacionEstilos . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');

        $archivoTemporal = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($archivoTemporal, ZipArchive::OVERWRITE);
        $zip->addEmptyDir('_rels');
        $zip->addEmptyDir('xl');
        $zip->addEmptyDir('xl/_rels');
        $zip->addEmptyDir('xl/worksheets');
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rootRels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/styles.xml', $stylesXml);
        foreach ($sheetsXml as $indice => $sheetXml) {
            $zip->addFromString('xl/worksheets/sheet' . ($indice + 1) . '.xml', $sheetXml);
        }
        $zip->close();

        readfile($archivoTemporal);
        unlink($archivoTemporal);
    }

    /**
     * Genera y descarga una plantilla .xlsx de dos hojas de datos ("Ingresos" y "Gastos") para los
     * módulos de Autogestión: cada hoja es una tabla real de Excel con desplegables (la de Gastos
     * incluye la columna Categoría: Excedentes/Gastos/Inversiones) y una columna "Valor total"
     * calculada por fórmula (Cantidad × Valor/Costo unitario) en cada fila. Además, en las
     * primeras filas de AMBAS hojas se incrusta un bloque de validación: el % configurado se lee,
     * bloqueado (no editable, hoja protegida), desde la hoja oculta "Listas"; a partir de él se
     * calcula el "Valor esperado" (% × total de Ingresos, en formato moneda) y el "Valor ejecutado"
     * (SUMAR.SI sobre la categoría en Gastos); este último se colorea automáticamente (verde/rojo,
     * formato condicional) según si respeta o supera el valor esperado. Reutiliza el mismo
     * mecanismo de metadatos/firma de plataforma que descargar().
     *
     * @param array{encabezados: string[], columnasConLista: array<int,string>, filaEjemplo: string[], columnaCantidad: int, columnaValorUnitario: int, columnaValorTotal: int} $hojaIngresos
     * @param array{encabezados: string[], columnasConLista: array<int,string>, filaEjemplo: string[], columnaCantidad: int, columnaValorUnitario: int, columnaValorTotal: int, columnaCategoria: int} $hojaGastos
     * @param array<int, array{etiqueta: string, porcentaje: ?float}> $validacionPorcentajes Filas del bloque de validación, en el orden en que deben aparecer (ej. Excedentes, Gastos, Inversiones). Solo se usa para las etiquetas/orden de fila cuando $porcentajesPorItem es null.
     * @param array<string, string[]> $listasComunes Nombre de lista => valores, para la hoja "Listas" (Años, Sedes, Dependencias, etc.), compartida por ambas hojas.
     * @param array{plantilla: string, usuario_id: int, usuario_nombre: string} $metadatos
     * @param array<int, array{nombre: string, excedentes: ?float, costos: ?float, inversiones: ?float}>|null $porcentajesPorItem
     *        Si se indica (Extensión y Postgrado, donde el % se configura por ítem de autogestión):
     *        la hoja oculta "Porcentaje" se genera como tabla de búsqueda (una fila por ítem, con su
     *        % de Excedentes/Gastos/Inversiones) y se agrega un desplegable ($C$1, en ambas hojas
     *        visibles) para elegir el ítem cuyo % se valida — el bloque de validación usa
     *        INDEX/MATCH sobre esa tabla y SUMIF/SUMIFS filtrando por ese ítem. Si es null (Unisalud,
     *        Convenios/SinExcedentes, donde el % es único por módulo), se mantiene el comportamiento
     *        anterior: una sola fila por categoría, sin desplegable ni filtro por ítem.
     */
    public static function descargarPlantillaAutogestion(
        string $nombreArchivo,
        array $hojaIngresos,
        array $hojaGastos,
        array $validacionPorcentajes,
        array $listasComunes,
        array $metadatos,
        ?array $porcentajesPorItem = null
    ): void {
        $celdaTexto = static function (string $referencia, string $valor, ?int $estilo = null): string {
            $texto = htmlspecialchars($valor, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $atributoEstilo = $estilo !== null ? ' s="' . $estilo . '"' : '';

            return '<c r="' . $referencia . '"' . $atributoEstilo . ' t="inlineStr"><is><t xml:space="preserve">' . $texto . '</t></is></c>';
        };

        $celdaNumero = static function (string $referencia, float $valor, ?int $estilo = null): string {
            $atributoEstilo = $estilo !== null ? ' s="' . $estilo . '"' : '';

            return '<c r="' . $referencia . '"' . $atributoEstilo . '><v>' . $valor . '</v></c>';
        };

        $celdaFormula = static function (string $referencia, string $formula, ?int $estilo = null): string {
            $atributoEstilo = $estilo !== null ? ' s="' . $estilo . '"' : '';

            return '<c r="' . $referencia . '"' . $atributoEstilo . '><f>' . htmlspecialchars($formula, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</f></c>';
        };

        $numeroCategorias = count($validacionPorcentajes);
        // 1 fila título + 1 fila encabezado del bloque + n categorías + 1 fila en blanco, luego el
        // encabezado real de la tabla.
        $filaEncabezado = 4 + $numeroCategorias;
        $filaEjemplo = $filaEncabezado + 1;
        $ultimaFilaDatos = $filaEncabezado + 199;

        $letraCategoriaGastos = self::columnaLetra($hojaGastos['columnaCategoria']);
        $letraValorTotalGastos = self::columnaLetra($hojaGastos['columnaValorTotal']);
        $letraValorTotalIngresos = self::columnaLetra($hojaIngresos['columnaValorTotal']);
        $rangoCategoriaGastos = 'Gastos!$' . $letraCategoriaGastos . '$' . $filaEjemplo . ':$' . $letraCategoriaGastos . '$' . $ultimaFilaDatos;
        $rangoValorTotalGastos = 'Gastos!$' . $letraValorTotalGastos . '$' . $filaEjemplo . ':$' . $letraValorTotalGastos . '$' . $ultimaFilaDatos;
        $rangoValorTotalIngresos = 'Ingresos!$' . $letraValorTotalIngresos . '$' . $filaEjemplo . ':$' . $letraValorTotalIngresos . '$' . $ultimaFilaDatos;

        // Columna (dentro de cada hoja de datos) que lleva el "Ítem de autogestión" de cada fila —
        // se usa para filtrar el bloque de validación por ítem cuando $porcentajesPorItem viene dado.
        $indiceItemIngresos = array_search('Items', $hojaIngresos['columnasConLista'], true);
        $indiceItemGastos = array_search('Items', $hojaGastos['columnasConLista'], true);
        $rangoItemIngresos = $indiceItemIngresos !== false
            ? 'Ingresos!$' . self::columnaLetra($indiceItemIngresos) . '$' . $filaEjemplo . ':$' . self::columnaLetra($indiceItemIngresos) . '$' . $ultimaFilaDatos
            : null;
        $rangoItemGastos = $indiceItemGastos !== false
            ? 'Gastos!$' . self::columnaLetra($indiceItemGastos) . '$' . $filaEjemplo . ':$' . self::columnaLetra($indiceItemGastos) . '$' . $ultimaFilaDatos
            : null;

        $mapaColumnaPorcentajePorItem = ['Excedentes' => 'B', 'Gastos' => 'C', 'Inversiones' => 'D'];

        // --- Hoja "Porcentaje" (oculta): es la ÚNICA hoja protegida del libro. Guarda el % de cada
        // categoría en una celda real (no fórmula) — Ingresos y Gastos solo la LEEN por referencia
        // directa de celda, así que el % nunca aparece como un valor editable en esas dos hojas, que
        // quedan totalmente sin proteger (se pueden redimensionar columnas, etc.).
        //
        // Cuando $porcentajesPorItem viene dado (Extensión/Postgrado), esta hoja es una tabla de
        // búsqueda con una fila por ítem de autogestión (se reconstruye desde la base de datos en
        // cada exportación, así que siempre refleja los ítems/% vigentes al momento de descargar la
        // plantilla) en vez de una sola fila por categoría — porque el % ya no es único por módulo. ---
        if ($porcentajesPorItem !== null) {
            $celdaPorcentajeItem = static function (string $columna, int $fila, ?float $valor) use ($celdaNumero, $celdaTexto): string {
                return $valor !== null ? $celdaNumero($columna . $fila, $valor, 2) : $celdaTexto($columna . $fila, 'N/A', 4);
            };

            $filasPorcentajeXml = '<row r="1">'
                . $celdaTexto('A1', 'Ítem de autogestión', 1)
                . $celdaTexto('B1', 'Excedentes %', 1)
                . $celdaTexto('C1', 'Gastos %', 1)
                . $celdaTexto('D1', 'Inversiones %', 1)
                . '</row>';

            foreach (array_values($porcentajesPorItem) as $indice => $item) {
                $numeroFilaPorcentaje = 2 + $indice;

                $filasPorcentajeXml .= '<row r="' . $numeroFilaPorcentaje . '">'
                    . $celdaTexto('A' . $numeroFilaPorcentaje, $item['nombre'], 4)
                    . $celdaPorcentajeItem('B', $numeroFilaPorcentaje, $item['excedentes'])
                    . $celdaPorcentajeItem('C', $numeroFilaPorcentaje, $item['costos'])
                    . $celdaPorcentajeItem('D', $numeroFilaPorcentaje, $item['inversiones'])
                    . '</row>';
            }

            $ultimaFilaPorcentaje = 1 + max(1, count($porcentajesPorItem));
        } else {
            $filasPorcentajeXml = '<row r="1">' . $celdaTexto('A1', 'Categoría', 1) . $celdaTexto('B1', '% Configurado', 1) . '</row>';
            foreach (array_values($validacionPorcentajes) as $indice => $fila) {
                $numeroFilaPorcentaje = 2 + $indice;
                $porcentaje = $fila['porcentaje'];

                $filasPorcentajeXml .= '<row r="' . $numeroFilaPorcentaje . '">'
                    . $celdaTexto('A' . $numeroFilaPorcentaje, $fila['etiqueta'], 4)
                    . ($porcentaje !== null ? $celdaNumero('B' . $numeroFilaPorcentaje, $porcentaje, 2) : $celdaTexto('B' . $numeroFilaPorcentaje, 'N/A', 4))
                    . '</row>';
            }
        }

        $sheetPorcentajeXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $filasPorcentajeXml . '</sheetData>'
            . '<sheetProtection sheet="1" selectLockedCells="0"/>'
            . '</worksheet>';

        // --- Bloque de validación (idéntico en ambas hojas): título + (si aplica) desplegable de
        // ítem a validar + encabezado + una fila por categoría. "% Configurado" (B) siempre termina
        // leyendo la hoja oculta "Porcentaje" (la única protegida); las hojas de Ingresos y Gastos no
        // llevan sheetProtection, así que ninguna de sus celdas queda bloqueada — solo se muestran con
        // formato de % o moneda para que se lean bien. ---
        if ($porcentajesPorItem !== null) {
            $letraListaItems = self::columnaLetraLista('Items', $listasComunes);
            $cantidadItemsLista = count($listasComunes['Items'] ?? []);
            $rangoListaItems = 'Listas!$' . $letraListaItems . '$2:$' . $letraListaItems . '$' . ($cantidadItemsLista + 1);
            $itemPorDefecto = $listasComunes['Items'][0] ?? '';

            $bloqueValidacionXml = '<row r="1">'
                . $celdaTexto('A1', 'Validación de cumplimiento de porcentajes por ítem (el % se administra en la hoja oculta "Porcentaje")', 1)
                . $celdaTexto('B1', 'Ítem a validar:', 1)
                . $celdaTexto('C1', $itemPorDefecto)
                . '</row>';

            $validacionItemSelector = '<dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="1" errorTitle="Valor no válido" error="Selecciona un valor de la lista." sqref="C1">'
                . '<formula1>' . htmlspecialchars($rangoListaItems, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</formula1>'
                . '</dataValidation>';
        } else {
            $bloqueValidacionXml = '<row r="1">' . $celdaTexto('A1', 'Validación de cumplimiento de porcentajes (el % se administra en la hoja oculta "Porcentaje")', 1) . '</row>';
            $validacionItemSelector = '';
        }

        $bloqueValidacionXml .= '<row r="2">'
            . $celdaTexto('A2', 'Categoría', 1)
            . $celdaTexto('B2', '% Configurado', 1)
            . $celdaTexto('C2', 'Total ingresos', 1)
            . $celdaTexto('D2', 'Valor esperado', 1)
            . $celdaTexto('E2', 'Valor ejecutado', 1)
            . '</row>';

        foreach (array_values($validacionPorcentajes) as $indice => $fila) {
            $numeroFila = 3 + $indice;
            $numeroFilaPorcentaje = 2 + $indice;
            $etiqueta = $fila['etiqueta'];

            if ($porcentajesPorItem !== null && isset($mapaColumnaPorcentajePorItem[$etiqueta])) {
                $columnaLookup = $mapaColumnaPorcentajePorItem[$etiqueta];
                $formulaPorcentaje = 'IFERROR(INDEX(Porcentaje!$' . $columnaLookup . '$2:$' . $columnaLookup . '$' . $ultimaFilaPorcentaje
                    . ',MATCH($C$1,Porcentaje!$A$2:$A$' . $ultimaFilaPorcentaje . ',0)),"N/A")';
                $formulaTotalIngresos = $rangoItemIngresos !== null
                    ? 'SUMIF(' . $rangoItemIngresos . ',$C$1,' . $rangoValorTotalIngresos . ')'
                    : 'SUM(' . $rangoValorTotalIngresos . ')';
                $formulaEjecutado = $rangoItemGastos !== null
                    ? 'SUMIFS(' . $rangoValorTotalGastos . ',' . $rangoCategoriaGastos . ',A' . $numeroFila . ',' . $rangoItemGastos . ',$C$1)'
                    : 'SUMIF(' . $rangoCategoriaGastos . ',A' . $numeroFila . ',' . $rangoValorTotalGastos . ')';
            } else {
                $formulaPorcentaje = 'Porcentaje!$B$' . $numeroFilaPorcentaje;
                $formulaTotalIngresos = 'SUM(' . $rangoValorTotalIngresos . ')';
                $formulaEjecutado = 'SUMIF(' . $rangoCategoriaGastos . ',A' . $numeroFila . ',' . $rangoValorTotalGastos . ')';
            }

            $bloqueValidacionXml .= '<row r="' . $numeroFila . '">'
                . $celdaTexto('A' . $numeroFila, $etiqueta)
                . $celdaFormula('B' . $numeroFila, $formulaPorcentaje, 2)
                . $celdaFormula('C' . $numeroFila, $formulaTotalIngresos, 3)
                . $celdaFormula('D' . $numeroFila, 'IFERROR(B' . $numeroFila . '/100*C' . $numeroFila . ',"N/A")', 3)
                . $celdaFormula('E' . $numeroFila, $formulaEjecutado, 3)
                . '</row>';
        }

        // "Valor ejecutado" (E) se colorea en verde si respeta el "Valor esperado" (D) de su misma
        // fila, o en rojo si lo supera — así se valida por color, no por una columna de texto.
        $conditionalFormattingXml = '<conditionalFormatting sqref="E3:E' . (2 + $numeroCategorias) . '">'
            . '<cfRule type="cellIs" dxfId="1" priority="1" operator="greaterThan"><formula>D3</formula></cfRule>'
            . '<cfRule type="cellIs" dxfId="0" priority="2" operator="lessThanOrEqual"><formula>D3</formula></cfRule>'
            . '</conditionalFormatting>';

        // --- Construye una hoja de datos (Ingresos o Gastos): bloque de validación + tabla real.
        // Ninguna de las dos lleva sheetProtection, así que ninguna celda queda bloqueada (se puede
        // redimensionar columnas, dar formato, etc.) — el % configurado, que sí debe quedar de solo
        // lectura, vive únicamente en la hoja oculta y protegida "Porcentaje". ---
        $construirHoja = function (array $config, int $idTabla, string $nombreTabla) use ($celdaTexto, $celdaFormula, $bloqueValidacionXml, $conditionalFormattingXml, $filaEncabezado, $filaEjemplo, $ultimaFilaDatos, $listasComunes, $validacionItemSelector): array {
            $columnas = count($config['encabezados']);

            $filasXml = $bloqueValidacionXml;

            $filasXml .= '<row r="' . $filaEncabezado . '">';
            for ($col = 0; $col < $columnas; $col++) {
                $filasXml .= $celdaTexto(self::columnaLetra($col) . $filaEncabezado, $config['encabezados'][$col], 1);
            }
            $filasXml .= '</row>';

            $letraCantidad = self::columnaLetra($config['columnaCantidad']);
            $letraValorUnitario = self::columnaLetra($config['columnaValorUnitario']);

            for ($fila = $filaEjemplo; $fila <= $ultimaFilaDatos; $fila++) {
                $filaXml = '<row r="' . $fila . '">';
                for ($col = 0; $col < $columnas; $col++) {
                    if ($col === $config['columnaValorTotal']) {
                        $filaXml .= $celdaFormula(self::columnaLetra($col) . $fila, $letraCantidad . $fila . '*' . $letraValorUnitario . $fila, 3);
                        continue;
                    }

                    if ($fila === $filaEjemplo && (string) ($config['filaEjemplo'][$col] ?? '') !== '') {
                        $filaXml .= $celdaTexto(self::columnaLetra($col) . $fila, (string) $config['filaEjemplo'][$col]);
                    }
                }
                $filasXml .= $filaXml . '</row>';
            }

            $validaciones = $validacionItemSelector;
            $cantidadListas = $validacionItemSelector !== '' ? 1 : 0;
            foreach ($config['columnasConLista'] as $indiceColumna => $nombreLista) {
                $valores = $listasComunes[$nombreLista] ?? [];
                $cantidadValores = count($valores);

                if ($cantidadValores === 0) {
                    continue;
                }

                $cantidadListas++;
                $letra = self::columnaLetraLista($nombreLista, $listasComunes);
                $rango = 'Listas!$' . $letra . '$2:$' . $letra . '$' . ($cantidadValores + 1);
                $colLetra = self::columnaLetra($indiceColumna);

                $validaciones .= '<dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="1" errorTitle="Valor no válido" error="Selecciona un valor de la lista." sqref="' . $colLetra . $filaEjemplo . ':' . $colLetra . $ultimaFilaDatos . '">'
                    . '<formula1>' . htmlspecialchars($rango, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</formula1>'
                    . '</dataValidation>';
            }

            $ultimaColumnaLetra = self::columnaLetra($columnas - 1);
            $rangoTabla = 'A' . $filaEncabezado . ':' . $ultimaColumnaLetra . $ultimaFilaDatos;

            $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheetData>' . $filasXml . '</sheetData>'
                . $conditionalFormattingXml
                . ($validaciones !== '' ? '<dataValidations count="' . $cantidadListas . '">' . $validaciones . '</dataValidations>' : '')
                . '<tableParts count="1"><tablePart r:id="rId1"/></tableParts>'
                . '</worksheet>';

            $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/table" Target="../tables/table' . $idTabla . '.xml"/>'
                . '</Relationships>';

            $tableXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<table xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" id="' . $idTabla . '" name="' . $nombreTabla . '" displayName="' . $nombreTabla . '" ref="' . $rangoTabla . '" totalsRowShown="0">'
                . '<autoFilter ref="' . $rangoTabla . '"/>'
                . '<tableColumns count="' . $columnas . '">'
                . implode('', array_map(static function (int $indice) use ($config): string {
                    $nombre = htmlspecialchars($config['encabezados'][$indice], ENT_QUOTES | ENT_XML1, 'UTF-8');
                    return '<tableColumn id="' . ($indice + 1) . '" name="' . $nombre . '"/>';
                }, range(0, $columnas - 1)))
                . '</tableColumns>'
                . '<tableStyleInfo name="TableStyleMedium2" showFirstColumn="0" showLastColumn="0" showRowStripes="1" showColumnStripes="0"/>'
                . '</table>';

            return ['sheet' => $sheetXml, 'rels' => $relsXml, 'table' => $tableXml];
        };

        $hojaIngresosXml = $construirHoja($hojaIngresos, 1, 'TablaIngresos');
        $hojaGastosXml = $construirHoja($hojaGastos, 2, 'TablaGastos');

        // --- Hoja "Listas" (oculta), compartida por ambas hojas de datos ---
        $nombresListas = array_keys($listasComunes);
        $filasListasXml = '<row r="1">';
        foreach ($nombresListas as $indice => $nombreLista) {
            $filasListasXml .= $celdaTexto(self::columnaLetra($indice) . '1', $nombreLista);
        }
        $filasListasXml .= '</row>';

        $maxFilasListas = 0;
        foreach ($listasComunes as $valores) {
            $maxFilasListas = max($maxFilasListas, count($valores));
        }

        for ($fila = 0; $fila < $maxFilasListas; $fila++) {
            $numeroFila = $fila + 2;
            $filaXml = '<row r="' . $numeroFila . '">';
            foreach ($nombresListas as $indice => $nombreLista) {
                $valor = $listasComunes[$nombreLista][$fila] ?? null;
                if ($valor !== null) {
                    $filaXml .= $celdaTexto(self::columnaLetra($indice) . $numeroFila, (string) $valor);
                }
            }
            $filaXml .= '</row>';
            $filasListasXml .= $filaXml;
        }

        $sheet3Xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $filasListasXml . '</sheetData>'
            . '</worksheet>';

        // --- Hoja "Metadatos" (oculta) ---
        $filasMetadatos = [
            ['Campo', 'Valor'],
            ['SPPI_Origen', self::FIRMA_PLATAFORMA],
            ['SPPI_Plantilla', $metadatos['plantilla']],
            ['SPPI_UsuarioId', (string) $metadatos['usuario_id']],
            ['SPPI_UsuarioNombre', $metadatos['usuario_nombre']],
            ['SPPI_GeneradoEn', date('c')],
        ];

        $filasMetadatosXml = '';
        foreach ($filasMetadatos as $indiceFila => $filaMetadato) {
            $numeroFila = $indiceFila + 1;
            $estiloFila = $indiceFila === 0 ? 1 : null;
            $filasMetadatosXml .= '<row r="' . $numeroFila . '">'
                . $celdaTexto('A' . $numeroFila, $filaMetadato[0], $estiloFila)
                . $celdaTexto('B' . $numeroFila, $filaMetadato[1], $estiloFila)
                . '</row>';
        }

        $sheet5Xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $filasMetadatosXml . '</sheetData>'
            . '</worksheet>';

        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="2">'
            . '<numFmt numFmtId="164" formatCode="&quot;$&quot; #,##0.00"/>'
            . '<numFmt numFmtId="165" formatCode="0.00&quot;%&quot;"/>'
            . '</numFmts>'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="5">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyProtection="1"><protection locked="0"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '<dxfs count="2">'
            . '<dxf><font><color rgb="FF006100"/></font><fill><patternFill><bgColor rgb="FFC6EFCE"/></patternFill></fill></dxf>'
            . '<dxf><font><color rgb="FF9C0006"/></font><fill><patternFill><bgColor rgb="FFFFC7CE"/></patternFill></fill></dxf>'
            . '</dxfs>'
            . '</styleSheet>';

        $propiedad = static function (int $pid, string $nombre, string $valor): string {
            $texto = htmlspecialchars($valor, ENT_QUOTES | ENT_XML1, 'UTF-8');
            return '<property fmtid="{D5CDD505-2E9C-101B-9397-08002B2CF9AE}" pid="' . $pid . '" name="' . htmlspecialchars($nombre, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"><vt:lpwstr>' . $texto . '</vt:lpwstr></property>';
        };

        $customXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/custom-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . $propiedad(2, 'SPPI_Origen', self::FIRMA_PLATAFORMA)
            . $propiedad(3, 'SPPI_Plantilla', $metadatos['plantilla'])
            . $propiedad(4, 'SPPI_UsuarioId', (string) $metadatos['usuario_id'])
            . $propiedad(5, 'SPPI_UsuarioNombre', $metadatos['usuario_nombre'])
            . $propiedad(6, 'SPPI_GeneradoEn', date('c'))
            . '</Properties>';

        $coreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>' . htmlspecialchars($metadatos['usuario_nombre'], ENT_QUOTES | ENT_XML1, 'UTF-8') . '</dc:creator>'
            . '<cp:lastModifiedBy>' . htmlspecialchars($metadatos['usuario_nombre'], ENT_QUOTES | ENT_XML1, 'UTF-8') . '</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:created>'
            . '</cp:coreProperties>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet4.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet5.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/tables/table1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.table+xml"/>'
            . '<Override PartName="/xl/tables/table2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.table+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/custom.xml" ContentType="application/vnd.openxmlformats-officedocument.custom-properties+xml"/>'
            . '</Types>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/custom-properties" Target="docProps/custom.xml"/>'
            . '</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>'
            . '<sheet name="Ingresos" sheetId="1" r:id="rId1"/>'
            . '<sheet name="Gastos" sheetId="2" r:id="rId2"/>'
            . '<sheet name="Listas" sheetId="3" r:id="rId3" state="hidden"/>'
            . '<sheet name="Porcentaje" sheetId="4" r:id="rId4" state="hidden"/>'
            . '<sheet name="Metadatos" sheetId="5" r:id="rId6" state="hidden"/>'
            . '</sheets>'
            . '<calcPr calcId="0" fullCalcOnLoad="1"/>'
            . '</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>'
            . '<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet4.xml"/>'
            . '<Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId6" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet5.xml"/>'
            . '</Relationships>';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');

        $archivoTemporal = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($archivoTemporal, ZipArchive::OVERWRITE);
        $zip->addEmptyDir('_rels');
        $zip->addEmptyDir('docProps');
        $zip->addEmptyDir('xl');
        $zip->addEmptyDir('xl/_rels');
        $zip->addEmptyDir('xl/worksheets');
        $zip->addEmptyDir('xl/worksheets/_rels');
        $zip->addEmptyDir('xl/tables');
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rootRels);
        $zip->addFromString('docProps/core.xml', $coreXml);
        $zip->addFromString('docProps/custom.xml', $customXml);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/styles.xml', $stylesXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $hojaIngresosXml['sheet']);
        $zip->addFromString('xl/worksheets/sheet2.xml', $hojaGastosXml['sheet']);
        $zip->addFromString('xl/worksheets/sheet3.xml', $sheet3Xml);
        $zip->addFromString('xl/worksheets/sheet4.xml', $sheetPorcentajeXml);
        $zip->addFromString('xl/worksheets/sheet5.xml', $sheet5Xml);
        $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $hojaIngresosXml['rels']);
        $zip->addFromString('xl/worksheets/_rels/sheet2.xml.rels', $hojaGastosXml['rels']);
        $zip->addFromString('xl/tables/table1.xml', $hojaIngresosXml['table']);
        $zip->addFromString('xl/tables/table2.xml', $hojaGastosXml['table']);
        $zip->close();

        readfile($archivoTemporal);
        unlink($archivoTemporal);
    }

    private static function columnaLetraLista(string $nombreLista, array $listas): string
    {
        $indice = array_search($nombreLista, array_keys($listas), true);

        return self::columnaLetra($indice !== false ? $indice : 0);
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
}
