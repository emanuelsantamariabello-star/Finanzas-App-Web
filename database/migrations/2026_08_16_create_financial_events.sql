-- =====================================================
-- Finanzas App Web - Calendario financiero local
-- Crea eventos financieros y ocurrencias confirmadas.
-- =====================================================

CREATE TABLE IF NOT EXISTS financial_events (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
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
  user_id INT UNSIGNED NOT NULL,
  occurrence_date DATE NOT NULL,
  status ENUM('pendiente', 'completado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  income_id INT UNSIGNED NULL,
  expense_id INT UNSIGNED NULL,
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
