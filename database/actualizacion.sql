-- =====================================================================
-- LicoFast · Actualizacion para bases de datos YA creadas
-- Ejecutar SOLO si ya tenias la base "licofast" de la version anterior.
-- Agrega las columnas nuevas sin borrar tus datos.
-- =====================================================================
USE licofast;

DROP PROCEDURE IF EXISTS licofast_add_col;
DELIMITER //
CREATE PROCEDURE licofast_add_col(IN tabla VARCHAR(64), IN columna VARCHAR(64), IN definicion VARCHAR(255))
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tabla AND COLUMN_NAME = columna) THEN
    SET @sql = CONCAT('ALTER TABLE `', tabla, '` ADD COLUMN `', columna, '` ', definicion);
    PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END //
DELIMITER ;

CALL licofast_add_col('usuarios','telefono','VARCHAR(30) NULL');
CALL licofast_add_col('ventas','cajero_id','INT NULL');
CALL licofast_add_col('ventas','tipo_venta',"ENUM('mostrador','delivery') NOT NULL DEFAULT 'delivery'");
CALL licofast_add_col('ventas','monto_cobrado','DECIMAL(10,2) NOT NULL DEFAULT 0');
CALL licofast_add_col('ventas','estado_pago',"ENUM('pendiente','pagado','anulado') NOT NULL DEFAULT 'pendiente'");
CALL licofast_add_col('ventas','direccion_entrega','VARCHAR(255) NULL');
CALL licofast_add_col('ventas','referencia_entrega','VARCHAR(255) NULL');
CALL licofast_add_col('ventas','telefono_contacto','VARCHAR(30) NULL');
CALL licofast_add_col('ventas','observacion_entrega','VARCHAR(255) NULL');
CALL licofast_add_col('ventas','fecha_entrega','DATETIME NULL');
DROP PROCEDURE IF EXISTS licofast_add_col;

ALTER TABLE ventas MODIFY estado ENUM('pendiente','pagada','en_camino','entregada','no_entregado') NOT NULL DEFAULT 'pendiente';
UPDATE ventas SET estado_pago='pagado', monto_cobrado=total WHERE estado IN ('pagada','entregada') AND monto_cobrado=0;
