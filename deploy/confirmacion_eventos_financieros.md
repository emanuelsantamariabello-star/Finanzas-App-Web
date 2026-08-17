# Confirmación de eventos financieros

## Alcance

Esta fase conecta el calendario financiero local con los módulos actuales de ingresos y gastos sin modificar sus tablas ni incorporar servicios externos.

Incluye:

- Estado independiente por fecha para eventos únicos y recurrentes.
- Acciones para marcar una ocurrencia como pendiente, completada o cancelada.
- Registro de un ingreso esperado como ingreso real.
- Registro de pagos, cuotas, deudas, suscripciones y gastos programados como gasto real.
- Selección obligatoria del ingreso del cual se descontará un gasto.
- Vínculo de la ocurrencia con el ingreso o gasto generado.
- Bloqueo de conversiones duplicadas.
- Exclusión automática de ocurrencias completadas o canceladas de los recordatorios pendientes.

## Base de datos

No requiere una migración nueva.

La implementación reutiliza `financial_event_occurrences`, creada en la fase inicial del calendario, mediante los campos:

- `status`
- `income_id`
- `expense_id`
- `completed_at`

La clave única formada por `event_id` y `occurrence_date` impide que existan dos registros para la misma ocurrencia.

## Seguridad e integridad

- Todas las acciones requieren sesión autenticada y token CSRF.
- El evento y la ocurrencia se validan contra el usuario autenticado.
- El ingreso seleccionado para un gasto debe pertenecer al mismo usuario.
- La fecha enviada debe corresponder realmente a la regla del evento.
- La creación del movimiento y la actualización de la ocurrencia se ejecutan en una sola transacción.
- La ocurrencia se bloquea durante la conversión para evitar registros simultáneos duplicados.

## Comportamiento

Los eventos recurrentes continúan activos después de confirmar una fecha. Solamente la ocurrencia seleccionada cambia de estado.

Si el movimiento vinculado se elimina posteriormente, la clave foránea conserva la ocurrencia y establece el vínculo correspondiente en `NULL`, permitiendo que el usuario vuelva a revisarla.

## Archivos principales

- `app/helpers/financial_events.php`
- `views/calendar/index.php`
- `views/calendar/partials/occurrence_actions.php`
- `web.php`
- `public/css/styles.css`

## Fases posteriores

- Recurrencias avanzadas y múltiples fechas mensuales.
- Cuentas financieras manuales.
- Integración opcional y unidireccional con Google Calendar.

Google Calendar deberá mantenerse separado del inicio de sesión con Google y no forma parte de esta fase local.
