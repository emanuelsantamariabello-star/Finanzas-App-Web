# Recurrencias financieras avanzadas

## Subfase 1 - Intervalos configurables

Esta subfase permite repetir un evento financiero cada N periodos:

- Cada N días.
- Cada N semanas.
- Cada N meses.
- Cada N años.
- Cada N meses usando el último día del mes.

Ejemplos:

- Pago cada 15 días.
- Recordatorio cada 2 semanas.
- Cuota cada 3 meses.
- Renovación cada 2 años.
- Cierre bimestral el último día del mes correspondiente.

## Base de datos

No requiere migración nueva.

La implementación activa la columna existente `financial_events.recurrence_interval`, que ya estaba preparada como `SMALLINT UNSIGNED NOT NULL DEFAULT 1`.

El intervalo permitido por la aplicación está entre 1 y 999. Cuando un evento no tiene recurrencia, el backend conserva el valor `1` para mantener consistencia.

## Compatibilidad

- Los eventos creados anteriormente continúan utilizando intervalo `1`.
- Las ocurrencias confirmadas mantienen sus estados y vínculos.
- Los recordatorios usan las nuevas fechas calculadas sin cambios adicionales.
- La conversión de ocurrencias en ingresos o gastos continúa validando que la fecha pertenezca a la regla recurrente.

## Seguridad

- El intervalo se valida nuevamente en backend.
- No se aceptan decimales, valores negativos, cero ni valores superiores a 999.
- La fecha final de recurrencia continúa limitando la expansión de ocurrencias.
- La eliminación de un evento financiero requiere confirmación mediante un modal antes de enviar la solicitud protegida por CSRF.

## Interfaz de eliminación

La confirmación nativa del navegador fue reemplazada por un modal integrado al calendario. El mensaje identifica el evento y advierte que se eliminarán todas sus ocurrencias, manteniendo la eliminación definitiva únicamente dentro del formulario confirmado.

## Subfase 2 - Varias fechas mensuales

Una regla como "día 15 y último día de cada mes" se almacena mediante la tabla hija `financial_event_monthly_rules`, sin duplicar eventos ni guardar listas dentro de una columna.

La interfaz permite ingresar varios días separados por comas y agregar el último día. El backend valida, deduplica y expande las fechas según el intervalo mensual configurado.

La migración `database/migrations/2026_08_17_create_financial_event_monthly_rules.sql` conserva compatibilidad con `recurrence_day_of_month` y `recurrence_is_last_day`.

Los detalles técnicos, validaciones y pasos de despliegue están en `deploy/reglas_mensuales_multiples.md`.

Google Calendar permanece fuera de esta fase. Su integración se evaluará después de cerrar las cuentas financieras manuales y la auditoría responsive del núcleo local.
