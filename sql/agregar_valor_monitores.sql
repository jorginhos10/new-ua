-- Valor monetario de una solicitud de Monitores (antes inexistente): numero_de_monitores * 2 *
-- SMMLV del año presupuestal de la solicitud (ver variables_macroeconomicas, nombre='SMMLV').
-- Nullable a propósito: las filas creadas antes de este cambio quedan en NULL y se recalculan al
-- vuelo (ver SolicitudMonitor::aplicarRespaldoValor()) hasta que alguien las edite y guarden ya
-- con el valor persistido. Ver sql/CONTROL_CAMBIOS_PENDIENTES.md.
ALTER TABLE solicitudes_monitores ADD COLUMN valor DECIMAL(14,2) NULL AFTER monitores_semestre2;
