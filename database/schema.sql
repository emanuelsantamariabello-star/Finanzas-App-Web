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
