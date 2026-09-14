DELIMITER $$
CREATE PROCEDURE scratch_sembrar_gastos()
BEGIN
  DECLARE i INT DEFAULT 0;
  WHILE i < 150 DO
    INSERT INTO gastos (sede_id, anio_presupuestal_id, dependencia, linea_id, motor_id, proyecto_id, objeto_proyecto_paa, actividad, rubro_texto, insumo, cantidad, costo_unitario, valor_total, meses, estado, dependencia_destino, rol_destinatario_id)
    VALUES (1, 2, 'DIRECTIVAS', 1, 1, 1, '', CONCAT('Actividad masiva ', i), 'Rubro test', CONCAT('Insumo ', i), 1, 1000, 1000, '1', 'enviado', 'OFICINA DE PLANEACIÓN', 1);
    SET i = i + 1;
  END WHILE;
END$$
DELIMITER ;
CALL scratch_sembrar_gastos();
DROP PROCEDURE scratch_sembrar_gastos;
