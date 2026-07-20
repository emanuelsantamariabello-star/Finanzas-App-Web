-- Agrega la columna usada por el coach financiero y reportes.
-- En produccion actual ya existe; este script queda para instalaciones nuevas o entornos desactualizados.

SET @column_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'expenses'
    AND COLUMN_NAME = 'reflection_type'
);

SET @sql := IF(
  @column_exists = 0,
  "ALTER TABLE expenses ADD COLUMN reflection_type ENUM('necesario', 'gusto') NOT NULL DEFAULT 'necesario' AFTER note",
  "SELECT 'expenses.reflection_type already exists' AS message"
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
