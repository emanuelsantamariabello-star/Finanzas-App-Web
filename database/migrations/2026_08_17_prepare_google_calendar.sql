-- =====================================================
-- Finanzas App Web - Preparacion para Google Calendar
-- No activa OAuth ni sincroniza eventos por si sola.
-- =====================================================

CREATE TABLE IF NOT EXISTS external_integrations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
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
