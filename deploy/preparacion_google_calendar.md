# Preparación técnica para Google Calendar

## Alcance de esta fase

Esta fase prepara la persistencia, configuración y protección de credenciales necesarias para integrar Google Calendar posteriormente.

Todavía no incluye:

- Botón para conectar o desconectar Google Calendar.
- Redirección al consentimiento de Google.
- Callback OAuth.
- Creación, actualización o eliminación de eventos en Google Calendar.
- Sincronización automática.

El login actual con Google permanece intacto y continúa usando `GOOGLE_CLIENT_ID` para validar ID Tokens.

## Migración

Ejecutar:

```sql
database/migrations/2026_08_17_prepare_google_calendar.sql
```

La migración crea:

- `external_integrations`: relación OAuth independiente por usuario y proveedor.
- `calendar_event_sync`: relación futura entre cada evento financiero local y su evento externo.

No modifica `users`, `auth_identities`, `financial_events`, ingresos ni gastos.

## Variables de entorno

Agregar únicamente en `.env.php`:

```php
$_ENV['GOOGLE_CALENDAR_CLIENT_ID'] = '';
$_ENV['GOOGLE_CALENDAR_CLIENT_SECRET'] = '';
$_ENV['GOOGLE_CALENDAR_REDIRECT_URI'] = '';
$_ENV['GOOGLE_CALENDAR_SCOPE'] = 'https://www.googleapis.com/auth/calendar.events';
$_ENV['OAUTH_TOKEN_ENCRYPTION_KEY'] = '';
```

`GOOGLE_CALENDAR_CLIENT_SECRET` y `OAUTH_TOKEN_ENCRYPTION_KEY` nunca deben incluirse en Git.

La clave de cifrado se genera una sola vez por ambiente con PHP:

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Si se pierde o cambia esa clave, los tokens ya almacenados no podrán descifrarse y los usuarios deberán volver a conectar su calendario.

## Configuración requerida en Google Cloud

1. Habilitar Google Calendar API en el mismo proyecto o en uno dedicado.
2. Configurar la pantalla de consentimiento OAuth.
3. Crear un cliente OAuth de tipo aplicación web.
4. Registrar exactamente las URI de redirección de desarrollo y producción.
5. Mantener la aplicación en modo de prueba mientras se valida, agregando los usuarios de prueba necesarios.

URI previstas para la siguiente fase:

```text
http://localhost/finanzas_app_web/routes/google_calendar_callback.php
https://finanzasappsan.com/routes/google_calendar_callback.php
```

No deben registrarse hasta confirmar que esas rutas coinciden con la ubicación final de cada ambiente.

## Seguridad preparada

- Tokens OAuth separados de `auth_identities`.
- Cifrado AES-256-GCM mediante OpenSSL antes de persistir tokens.
- Clave de cifrado fuera del repositorio.
- Un registro de Google Calendar por usuario.
- Eliminación en cascada al borrar el usuario o evento local.
- Estado y último error disponibles para diagnóstico sin exponer tokens.

## Siguiente fase

Con las credenciales confirmadas se implementará:

1. Inicio de autorización con `state` para protección CSRF.
2. Callback y canje del código por tokens.
3. Solicitud de acceso offline para obtener refresh token.
4. Conectar y desconectar desde Cuentas o Calendario, sin duplicar accesos.
5. Sincronización manual inicial de eventos.
6. Tratamiento de expiración, revocación y reintentos.
