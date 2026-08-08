-- =====================================================
-- Finanzas App Web - Identidades externas
-- Permite asociar proveedores como Google sin mezclar
-- identificadores externos dentro de la tabla users.
-- =====================================================

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
