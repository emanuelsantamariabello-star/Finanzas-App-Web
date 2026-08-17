# Despliegue de Google Calendar en producción

## Alcance

Este despliegue incorpora:

- Calendario financiero local y responsive.
- Conexión OAuth independiente con Google Calendar.
- Tokens OAuth cifrados mediante AES-256-GCM.
- Sincronización manual idempotente de eventos activos.
- Renovación automática del access token.
- Soporte para eventos de día completo, con hora y recurrentes.
- Confirmación antes de sincronizar o desconectar.

No activa todavía sincronización automática ni lectura de cambios realizados directamente en Google Calendar.

## Respaldo obligatorio

Antes de subir archivos:

1. Descargar una copia completa de `public_html`.
2. Exportar la base de datos desde phpMyAdmin.
3. Conservar el `.env.php` actual de producción.

El paquete no contiene `.env.php` y no debe reemplazarse durante la extracción.

## Migraciones y orden

Ejecutar únicamente las migraciones que todavía no existan en producción y respetar este orden:

```text
1. database/migrations/2026_08_16_create_financial_events.sql
2. database/migrations/2026_08_17_create_financial_event_monthly_rules.sql
3. database/migrations/2026_08_17_create_financial_accounts.sql
4. database/migrations/2026_08_17_prepare_google_calendar.sql
```

La última migración crea:

- `external_integrations`
- `calendar_event_sync`

Todas usan `CREATE TABLE IF NOT EXISTS`; no eliminan tablas ni movimientos existentes.

## Variables de producción

Agregar al `.env.php` existente sin reemplazar credenciales actuales:

```php
$_ENV['GOOGLE_CALENDAR_CLIENT_ID'] = 'CLIENT_ID_REAL';
$_ENV['GOOGLE_CALENDAR_CLIENT_SECRET'] = 'CLIENT_SECRET_REAL';
$_ENV['GOOGLE_CALENDAR_REDIRECT_URI'] = 'https://finanzasappsan.com/routes/google_calendar_callback.php';
$_ENV['GOOGLE_CALENDAR_SCOPE'] = 'https://www.googleapis.com/auth/calendar.events';
$_ENV['OAUTH_TOKEN_ENCRYPTION_KEY'] = 'CLAVE_BASE64_DE_32_BYTES';
```

Generar una clave nueva y estable para producción:

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

La clave debe conservarse: cambiarla después obligará a reconectar todos los calendarios.

Verificar también:

```php
$_ENV['APP_URL'] = 'https://finanzasappsan.com';
$_ENV['APP_BASE_PATH'] = '';
$_ENV['APP_TIMEZONE'] = 'America/Bogota';
```

## Google Cloud

Confirmar que el cliente OAuth de aplicación web contiene esta redirección exacta:

```text
https://finanzasappsan.com/routes/google_calendar_callback.php
```

Google Calendar API debe permanecer habilitada y el alcance configurado debe ser `calendar.events`.

## Archivos y extracción

1. Subir el ZIP a `public_html`.
2. Extraerlo directamente dentro de `public_html`.
3. Activar la opción de sobrescribir archivos existentes.
4. Confirmar que se preservó `.env.php`.
5. Eliminar el ZIP del servidor después de validar.

El ZIP usa rutas internas con `/` para evitar archivos planos con nombres que contienen barras invertidas.

## Validación posterior

1. Login tradicional y con Google.
2. Dashboard, ingresos, gastos, cuentas y reportes.
3. Calendario en escritorio y celular.
4. Conectar Google Calendar.
5. Crear un evento financiero de prueba.
6. Ejecutar `Sincronizar eventos`.
7. Confirmar que aparece una sola vez en Google Calendar.
8. Repetir la sincronización y confirmar que se actualiza sin duplicarse.
9. Revisar `logs/errors.log`.

## Reversión

Si ocurre un error crítico:

1. Restaurar el respaldo de archivos.
2. Restaurar la base de datos solo si una migración no terminó correctamente.
3. No eliminar manualmente tokens ni tablas antes de conservar evidencia del error.
