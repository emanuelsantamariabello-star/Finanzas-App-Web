-- =====================================================
-- Finanzas App Web - Reglas mensuales múltiples
-- Permite varios días y último día para un mismo evento.
-- month_day: 1-31 = día fijo, 0 = último día del mes.
-- =====================================================

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

INSERT IGNORE INTO financial_event_monthly_rules (event_id, month_day)
SELECT
  id,
  CASE
    WHEN recurrence_is_last_day = 1 THEN 0
    ELSE COALESCE(recurrence_day_of_month, DAY(event_date))
  END
FROM financial_events
WHERE recurrence_type = 'monthly';
