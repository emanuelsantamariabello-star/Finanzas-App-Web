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

## Subfase siguiente - Varias fechas mensuales

Una regla como "día 15 y último día de cada mes" no debe almacenarse duplicando eventos ni agregando una lista de días en texto.

La opción recomendada es una tabla hija para reglas mensuales asociadas al evento. Esta tabla permitiría guardar varias reglas ordenadas y mantener un único evento financiero como origen.

La siguiente subfase deberá definir y migrar esa tabla, adaptar el generador de ocurrencias y conservar compatibilidad con `recurrence_day_of_month` y `recurrence_is_last_day`.

Google Calendar permanece fuera de esta fase. Su integración se evaluará cuando el calendario local y todas sus reglas recurrentes estén estabilizados.
