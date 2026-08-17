# Preparación técnica para Google Calendar

## Alcance de esta fase

Esta fase prepara la persistencia, configuración y protección de credenciales necesarias para integrar Google Calendar posteriormente.

Esta fase incluye ahora:

- Botón único de conexión dentro del calendario financiero.
- Autorización OAuth con validación `state` y PKCE.
- Callback para canjear el código de autorización.
- Persistencia cifrada de access token y refresh token.
- Estado visual conectado o sin conectar.
- Desconexión con confirmación y revocación en Google.

Todavía no incluye:

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

## Flujo implementado

1. El usuario inicia la conexión desde el calendario financiero.
2. La aplicación genera `state`, verificador PKCE y desafío SHA-256 con vigencia de 10 minutos.
3. Google solicita consentimiento para `calendar.events` y acceso offline.
4. El callback valida usuario, sesión, `state` y PKCE.
5. Los tokens se cifran antes de almacenarse en `external_integrations`.
6. La desconexión requiere POST, CSRF y confirmación visual.
7. Google revoca el token antes de que la aplicación elimine la integración local.

## Siguiente fase

Después de validar la conexión OAuth se implementará:

1. Renovación controlada del access token mediante refresh token.
2. Sincronización manual inicial de eventos.
3. Tratamiento de expiración, revocación y reintentos.
4. Sincronización automática en una fase posterior.
