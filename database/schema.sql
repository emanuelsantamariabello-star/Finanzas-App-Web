-- =====================================================
-- Finanzas App - Esquema de base de datos
-- Derivado del codigo existente para documentar la BD.
-- =====================================================

CREATE DATABASE IF NOT EXISTS finanzas_app
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE finanzas_app;

-- =========================
-- Tabla: users
-- =========================
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  occupation VARCHAR(100) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_users_email (email),
  UNIQUE KEY uniq_users_username (username)
) ENGINE=InnoDB;

-- =========================
-- Tabla: auth_identities
-- =========================
CREATE TABLE IF NOT EXISTS auth_identities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  provider VARCHAR(50) NOT NULL,
  provider_user_id VARCHAR(255) NOT NULL,
  provider_email VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_auth_provider_user (provider, provider_user_id),
  UNIQUE KEY uniq_auth_user_provider (user_id, provider),
  KEY idx_auth_provider_email (provider, provider_email),
  CONSTRAINT fk_auth_identities_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- Tabla: incomes
-- =========================
CREATE TABLE IF NOT EXISTS incomes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  type VARCHAR(50) NOT NULL,
  income_date DATE NOT NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_incomes_user_date (user_id, income_date),
  CONSTRAINT fk_incomes_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- Tabla: expenses
-- =========================
CREATE TABLE IF NOT EXISTS expenses (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  income_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  expense_date DATE NOT NULL,
  note VARCHAR(255) NULL,
  reflection_type ENUM('necesario', 'gusto') NOT NULL DEFAULT 'necesario',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_expenses_income_date (income_id, expense_date),
  CONSTRAINT fk_expenses_income
    FOREIGN KEY (income_id) REFERENCES incomes(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- Tabla: financial_accounts
-- =========================
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

-- =========================
-- Tabla: system_notifications
-- =========================
CREATE TABLE IF NOT EXISTS system_notifications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(120) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_system_notifications_active_dates (is_active, starts_at, ends_at)
) ENGINE=InnoDB;

-- =========================
-- Tabla: financial_events
-- =========================
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

-- =========================
-- Tabla: financial_event_monthly_rules
-- =========================
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

-- =========================
-- Tabla: financial_event_occurrences
-- =========================
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
