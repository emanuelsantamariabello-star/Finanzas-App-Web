-- =====================================================
-- Finanzas App Web - Cuentas financieras manuales
-- No modifica ingresos, gastos ni eventos financieros.
-- =====================================================

CREATE TABLE IF NOT EXISTS financial_accounts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  type ENUM('banco', 'billetera_digital', 'efectivo', 'ahorro', 'credito', 'otra') NOT NULL DEFAULT 'otra',
  institution VARCHAR(100) NULL,
  initial_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'COP',
  status ENUM('activa', 'inactiva') NOT NULL DEFAULT 'activa',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_financial_accounts_user_status (user_id, status),
  KEY idx_financial_accounts_user_name (user_id, name),
  CONSTRAINT fk_financial_accounts_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;
