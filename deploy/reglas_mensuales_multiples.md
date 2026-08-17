# Reglas mensuales múltiples

## Alcance

Esta fase amplía las recurrencias mensuales del calendario local para que un mismo evento financiero pueda aparecer en varios días del mes y, opcionalmente, también en el último día.

Ejemplos compatibles:

- Día 15 y último día de cada mes.
- Días 1, 10 y 20.
- Día 31, ajustado automáticamente al último día disponible en meses más cortos.
- Varias fechas cada 2 o más meses usando `recurrence_interval`.

## Base de datos

Aplicar en cada ambiente:

`database/migrations/2026_08_17_create_financial_event_monthly_rules.sql`

La tabla `financial_event_monthly_rules` almacena una regla por día:

- `month_day` entre 1 y 31 representa un día fijo.
- `month_day = 0` representa el último día del mes.
- La clave única `(event_id, month_day)` evita reglas duplicadas.
- La relación `ON DELETE CASCADE` elimina las reglas cuando se elimina el evento.

La migración convierte automáticamente la configuración mensual anterior en una regla equivalente. Las columnas existentes se conservan para compatibilidad.

## Validaciones

- El backend solo acepta enteros entre 1 y 31.
- Se eliminan días repetidos antes de guardar.
- Si no se indica ningún día ni último día, se utiliza el día de la fecha inicial.
- Cuando varios días producen la misma fecha en un mes corto, la ocurrencia se muestra una sola vez.
- La creación y actualización del evento y sus reglas se ejecutan dentro de una transacción.

## Responsive

Los campos mensuales utilizan la cuadrícula responsive existente:

- En escritorio se distribuyen en columnas.
- En celular ocupan todo el ancho disponible.
- Los controles mensuales se muestran automáticamente solo al seleccionar recurrencia mensual.
- El calendario mantiene su cambio automático de cuadrícula a lista bajo `767.98px`.

## Orden de implementación

Después de validar esta fase local:

1. Cuentas financieras manuales.
2. Auditoría funcional y responsive final del núcleo local.
3. Preparación OAuth independiente para Google Calendar.
4. Sincronización unidireccional FinanzasApp hacia Google Calendar.

Google Calendar no forma parte de esta migración y el calendario local continúa funcionando sin servicios externos.
