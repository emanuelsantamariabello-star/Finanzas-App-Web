# Registro e inicio de sesión con Google

## Alcance

Esta actualización agrega autenticación con Google para usuarios nuevos y login posterior con la misma cuenta Google.

El registro con Google no crea usuarios sin contraseña local. Después de validar la cuenta Google, Finanzas App solicita una contraseña de seguridad antes de crear definitivamente la cuenta.

## Base de datos

Ejecutar la migración:

```sql
database/migrations/2026_08_08_create_auth_identities.sql
```

La tabla `auth_identities` almacena la relación entre un usuario interno y el proveedor externo:

- `provider`: actualmente `google`.
- `provider_user_id`: valor `sub` entregado por Google.
- `provider_email`: correo verificado por Google.
- `user_id`: usuario local en `users`.

## Variables de entorno

Agregar en `.env.php`:

```php
$_ENV['GOOGLE_CLIENT_ID'] = 'client-id-creado-en-google-cloud';
```

No requiere `GOOGLE_CLIENT_SECRET` porque el flujo usa Google Identity Services con ID Token validado en backend.

## Google Cloud

Crear un OAuth Client ID tipo Web application.

Orígenes autorizados recomendados:

- Desarrollo: `http://localhost`
- Producción: `https://finanzasappsan.com`

Si el dominio final cambia, agregar ese origen en Google Cloud antes de desplegar.

## Flujo funcional

### Registro

1. El usuario pulsa `Continuar con Google`.
2. Google entrega un ID Token al frontend.
3. El sistema abre un modal para crear contraseña de seguridad.
4. El backend verifica firma, issuer, audience, expiración, `sub`, correo y `email_verified`.
5. Se crea el usuario en `users`.
6. Se crea la identidad en `auth_identities`.
7. Se envía el correo de bienvenida.
8. Se inicia sesión con la sesión PHP actual del sistema.

### Login

1. El usuario pulsa `Iniciar sesión con Google`.
2. El backend valida el ID Token.
3. Se busca `auth_identities.provider = google` y `provider_user_id = sub`.
4. Si existe, se inicia sesión con `$_SESSION['user_id']` y `$_SESSION['username']`.

## Reglas de seguridad

- No se guarda el ID Token de Google.
- No se usa el correo como identificador permanente de Google; se usa `sub`.
- No se vincula automáticamente un correo ya existente.
- No se crea una cuenta duplicada si el `sub` ya existe.
- La creación `users + auth_identities` se ejecuta dentro de una transacción.
- El fallo del correo de bienvenida no revierte la cuenta creada.

## Pruebas mínimas antes de producción

- Registro tradicional.
- Login tradicional.
- Registro con Google y contraseña de seguridad.
- Login posterior con Google.
- Login tradicional usando la contraseña creada durante registro Google.
- Intento de registro Google con correo ya existente.
- Intento de registro Google con cuenta Google ya registrada.
- Verificación de registros en `users` y `auth_identities`.
