-- Ejemplos para crear novedades del sistema.
-- Ejecutar solo el INSERT que necesites y ajustar fechas/mensajes antes de usarlo.
-- Para que una novedad aparezca en la campana debe cumplir:
-- 1) is_active = 1
-- 2) starts_at debe estar vacío o ser menor/igual a la fecha actual
-- 3) ends_at debe estar vacío o ser mayor/igual a la fecha actual

-- Ejemplo visible inmediatamente por 15 días.
INSERT INTO system_notifications (title, message, type, starts_at, ends_at, is_active)
VALUES (
  'Nueva versión disponible',
  'Estamos preparando mejoras para Finanzas App. Revisa las novedades dentro del sistema.',
  'info',
  NOW(),
  DATE_ADD(NOW(), INTERVAL 15 DAY),
  1
);

-- Ejemplo desactivado. No aparece hasta cambiar is_active a 1.
INSERT INTO system_notifications (title, message, type, starts_at, ends_at, is_active)
VALUES (
  'Mantenimiento programado',
  'El sistema puede presentar intermitencias durante una ventana de mantenimiento.',
  'warning',
  NOW(),
  DATE_ADD(NOW(), INTERVAL 3 DAY),
  0
);

-- Activar una novedad ya creada:
-- UPDATE system_notifications SET is_active = 1 WHERE id = 1;

-- Desactivar una novedad:
-- UPDATE system_notifications SET is_active = 0 WHERE id = 1;
