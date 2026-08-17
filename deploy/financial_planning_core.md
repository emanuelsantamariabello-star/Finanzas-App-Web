# Financial Planning Core - Calendario local

## Alcance de esta fase

Esta fase agrega el calendario financiero local de Finanzas App Web sin integrar servicios externos.

Incluye:

- Vista mensual de calendario.
- Navegación mensual responsiva con acceso directo de regreso al dashboard.
- Creación, edición y eliminación de eventos financieros.
- Tipos de evento: pago, ingreso esperado, gasto programado, cuota, deuda, suscripción, recordatorio y otro.
- Recurrencias diarias, semanales, mensuales y anuales con intervalos configurables.
- Soporte para eventos mensuales en el último día del mes.
- Estados almacenados: pendiente, completado y cancelado.
- Estado vencido derivado dinámicamente cuando el evento pendiente ya pasó.
- Recordatorios derivados para el panel de notificaciones.
- Estados independientes para cada ocurrencia recurrente.
- Conversión de ingresos esperados en ingresos reales.
- Conversión de pagos y gastos programados en gastos asociados a un ingreso existente.
- Vínculo entre la ocurrencia y el movimiento generado.

No incluye todavía:

- Cuentas financieras manuales.
- Google Calendar.
- Integraciones bancarias.

## Migración requerida

Ejecutar en producción:

```sql
database/migrations/2026_08_16_create_financial_events.sql
```

La migración crea:

- `financial_events`
- `financial_event_occurrences`

La tabla `financial_event_occurrences` queda preparada para una fase posterior donde una ocurrencia pueda relacionarse con `incomes` o `expenses`.

## Variable de entorno recomendada

Agregar o verificar en `.env.php`:

```php
$_ENV['APP_TIMEZONE'] = 'America/Bogota';
```

La aplicación usa este timezone para cálculos de calendario, vencimientos y recordatorios.

## Rutas y archivos principales

- Vista: `views/calendar/index.php`
- Helper: `app/helpers/financial_events.php`
- Migración: `database/migrations/2026_08_16_create_financial_events.sql`
- Navegación: `views/layouts/header.php`
- Acciones POST: `web.php`
- Acciones por ocurrencia: `views/calendar/partials/occurrence_actions.php`
- Configuración de recurrencia: `views/calendar/partials/form_fields.php`
- Estilos: `public/css/styles.css`

## Seguridad

- Todas las acciones POST usan CSRF.
- Los eventos se filtran por `user_id`.
- La edición y eliminación validan propiedad del evento.
- Los datos visibles pasan por `e()` para reducir riesgo XSS.
- El calendario interno no usa tokens externos.

## Integraciones futuras

Google Calendar debe implementarse como integración OAuth independiente de `auth_identities`.

Las cuentas financieras manuales se implementan mediante `financial_accounts` sin modificar todavía ingresos ni gastos. La futura relación con movimientos deberá ser opcional y compatible con los registros existentes.

La tabla `auth_identities` se mantiene solo para identidad e inicio de sesión con Google.

Para integración futura se recomienda agregar una tabla separada tipo `external_integrations` y otra tabla de sincronización tipo `calendar_sync`.
