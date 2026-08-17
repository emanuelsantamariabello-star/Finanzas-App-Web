-- =====================================================
-- Finanzas App Web - Despliegue consolidado
-- Calendario local, cuentas y Google Calendar
-- Seguro para ejecutar desde phpMyAdmin.
-- =====================================================

CREATE TABLE IF NOT EXISTS financial_events (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  event_type ENUM('pago', 'ingreso_esperado', 'gasto_programado', 'cuota', 'deuda', 'suscripcion', 'recordatorio', 'otro') NOT NULL DEFAULT 'otro',
  amount DECIMAL(12,2) NULL,
  event_date DATE NOT NULL,
  event_time TIME NULL,
  description TEXT NULL,
  status ENUM('pendiente', 'completado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  recurrence_type ENUM('none', 'daily', 'weekly', 'monthly', 'yearly') NOT NULL DEFAULT 'none',
  recurrence_interval SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  recurrence_day_of_month TINYINT UNSIGNED NULL,
  recurrence_is_last_day TINYINT(1) NOT NULL DEFAULT 0,
  recurrence_ends_at DATE NULL,
  reminder_days_before SMALLINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_financial_events_user_date (user_id, event_date),
  KEY idx_financial_events_user_status_date (user_id, status, event_date),
  KEY idx_financial_events_user_type_date (user_id, event_type, event_date),
  CONSTRAINT fk_financial_events_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS financial_event_monthly_rules (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id INT UNSIGNED NOT NULL,
  month_day TINYINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_financial_event_month_day (event_id, month_day),
  KEY idx_financial_monthly_rules_event (event_id),
  CONSTRAINT fk_financial_monthly_rules_event
    FOREIGN KEY (event_id) REFERENCES financial_events(id)
    ON DELETE CASCADE,
  CONSTRAINT chk_financial_month_day
    CHECK (month_day BETWEEN 0 AND 31)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS financial_event_occurrences (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id INT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  occurrence_date DATE NOT NULL,
  status ENUM('pendiente', 'completado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  income_id INT NULL,
  expense_id INT NULL,
  completed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_financial_event_occurrence (event_id, occurrence_date),
  KEY idx_financial_occurrences_user_date (user_id, occurrence_date),
  KEY idx_financial_occurrences_income (income_id),
  KEY idx_financial_occurrences_expense (expense_id),
  CONSTRAINT fk_financial_occurrences_event
    FOREIGN KEY (event_id) REFERENCES financial_events(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_financial_occurrences_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_financial_occurrences_income
    FOREIGN KEY (income_id) REFERENCES incomes(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_financial_occurrences_expense
    FOREIGN KEY (expense_id) REFERENCES expenses(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO financial_event_monthly_rules (event_id, month_day)
SELECT
  id,
  CASE
    WHEN recurrence_is_last_day = 1 THEN 0
    ELSE COALESCE(recurrence_day_of_month, DAY(event_date))
  END
FROM financial_events
WHERE recurrence_type = 'monthly';

CREATE TABLE IF NOT EXISTS financial_accounts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
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

CREATE TABLE IF NOT EXISTS external_integrations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  provider VARCHAR(50) NOT NULL,
  provider_account_id VARCHAR(255) NULL,
  provider_account_email VARCHAR(255) NULL,
  access_token_encrypted TEXT NULL,
  refresh_token_encrypted TEXT NULL,
  token_expires_at DATETIME NULL,
  scopes TEXT NULL,
  status ENUM('pendiente', 'conectada', 'revocada', 'error') NOT NULL DEFAULT 'pendiente',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_external_integration_user_provider (user_id, provider),
  KEY idx_external_integrations_status (provider, status),
  CONSTRAINT fk_external_integrations_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS calendar_event_sync (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  integration_id INT UNSIGNED NOT NULL,
  event_id INT UNSIGNED NOT NULL,
  provider_calendar_id VARCHAR(255) NOT NULL DEFAULT 'primary',
  provider_event_id VARCHAR(255) NULL,
  provider_etag VARCHAR(255) NULL,
  sync_status ENUM('pendiente', 'sincronizado', 'error', 'eliminado') NOT NULL DEFAULT 'pendiente',
  last_synced_at DATETIME NULL,
  last_error TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_calendar_sync_integration_event (integration_id, event_id),
  KEY idx_calendar_sync_event (event_id),
  KEY idx_calendar_sync_status (sync_status, last_synced_at),
  CONSTRAINT fk_calendar_sync_integration
    FOREIGN KEY (integration_id) REFERENCES external_integrations(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_calendar_sync_event
    FOREIGN KEY (event_id) REFERENCES financial_events(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;
