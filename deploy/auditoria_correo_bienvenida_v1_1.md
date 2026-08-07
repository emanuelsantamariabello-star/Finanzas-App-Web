# Auditoría del correo de bienvenida - v1.1

Fecha: 2026-08-06

## Alcance

Se revisó el flujo que crea una cuenta nueva y dispara el correo de bienvenida, sin modificar el comportamiento del sistema.

Archivos revisados:

- `web.php`
- `app/helpers/mailer.php`
- `.env.example.php`
- `.env.php` local, solo validando nombres de claves y ocultando valores
- `logs/`
- `.gitignore`

## Hallazgos

1. El registro de usuarios sigue invocando el envío de correo.
   - Después de crear el usuario, `web.php` carga `app/helpers/mailer.php`.
   - Luego ejecuta `sendWelcomeEmail($email, $username)`.

2. El resultado del envío no se valida ni se registra.
   - Si PHPMailer falla o falta configuración SMTP, `sendWelcomeEmail()` devuelve `false`.
   - `web.php` ignora ese retorno y redirige al login con cuenta creada correctamente.
   - Esto permite que el registro funcione aunque el correo no salga.

3. El mailer ya no usa credenciales SMTP hardcodeadas.
   - La configuración se lee desde `.env.php`.
   - Las variables esperadas por el código son:
     - `SMTP_HOST`
     - `SMTP_PORT`
     - `SMTP_USERNAME`
     - `SMTP_PASSWORD`
     - `SMTP_FROM_EMAIL`
     - `SMTP_FROM_NAME`
     - `APP_URL`

4. La copia local de desarrollo contiene las claves `SMTP_*`.
   - En la copia de XAMPP se detectaron las claves necesarias, con valores ocultos en la revisión.
   - No se imprimieron ni documentaron credenciales.

5. La configuración de producción compartida anteriormente no incluía las claves `SMTP_*`.
   - El archivo de producción mostrado tenía variables de base de datos y comentarios con `MAIL_*`.
   - El código actual no lee `MAIL_HOST`, `MAIL_USER` ni `MAIL_PASS`.
   - Si producción mantiene esa estructura, el mailer retorna `false` antes de intentar enviar.

6. No se encontraron logs de error del mailer.
   - La carpeta `logs/` no contiene un `errors.log` con fallos SMTP.
   - El `catch` de PHPMailer actualmente no escribe errores.

## Causa probable

El correo de bienvenida dejó de funcionar porque, después de limpiar credenciales del repositorio y mover la configuración a entorno, producción no quedó con las variables `SMTP_*` que el código espera.

El registro no muestra error porque el envío de correo es silencioso: si falla, la cuenta se crea igual y el usuario es redirigido al login.

## Revisión técnica

Flujo actual:

1. Usuario envía formulario de registro.
2. `web.php` valida datos.
3. `web.php` inserta el usuario en la tabla `users`.
4. `web.php` llama `sendWelcomeEmail($email, $username)`.
5. `app/helpers/mailer.php` intenta cargar `.env.php`.
6. `app/helpers/mailer.php` lee las variables `SMTP_*`.
7. Si falta alguna variable obligatoria, retorna `false`.
8. `web.php` no revisa ese resultado.

## Plan de corrección recomendado

1. Ajustar `.env.php` de producción.
   - Agregar las claves `SMTP_*` reales del correo transaccional.
   - No versionar `.env.php`.
   - No reemplazar `SMTP_*` por `MAIL_*`, porque el código no lee esos nombres.

2. Mejorar observabilidad sin romper el registro.
   - Registrar en `logs/errors.log` cuando el correo no pueda enviarse.
   - No mostrar errores SMTP al usuario final.
   - No guardar contraseñas SMTP en logs.

3. Mejorar compatibilidad de configuración.
   - Opcionalmente permitir alias `MAIL_HOST`, `MAIL_USER` y `MAIL_PASS` como respaldo.
   - Mantener `SMTP_*` como configuración principal.

4. Validar con una cuenta de prueba.
   - Crear usuario nuevo en entorno local o producción controlada.
   - Confirmar que el usuario se crea.
   - Confirmar que el correo llega.
   - Confirmar que, si el SMTP falla, queda registrado en logs sin exponer secretos.

## Variables requeridas en producción

Ejemplo de estructura, sin valores reales:

```php
$_ENV['SMTP_HOST'] = 'smtp.tu-proveedor.com';
$_ENV['SMTP_PORT'] = '465';
$_ENV['SMTP_USERNAME'] = 'correo@tu-dominio.com';
$_ENV['SMTP_PASSWORD'] = 'password_smtp_seguro';
$_ENV['SMTP_FROM_EMAIL'] = 'correo@tu-dominio.com';
$_ENV['SMTP_FROM_NAME'] = 'Finanzas App';
$_ENV['APP_URL'] = 'https://finanzasappsan.com';
```

## Corrección aplicada

Fecha: 2026-08-06

Se aplicó una corrección controlada en `app/helpers/mailer.php`:

1. Compatibilidad de variables de entorno.
   - Se mantienen `SMTP_*` como variables principales.
   - Se aceptan alias `MAIL_*` como respaldo:
     - `MAIL_HOST`
     - `MAIL_PORT`
     - `MAIL_USER`
     - `MAIL_USERNAME`
     - `MAIL_PASS`
     - `MAIL_PASSWORD`
     - `MAIL_FROM_EMAIL`
     - `MAIL_FROM_NAME`
     - `MAIL_ENCRYPTION`

2. Logging seguro de fallos.
   - Los errores del correo de bienvenida quedan en `logs/errors.log`.
   - No se registran contraseñas ni secretos SMTP.
   - Si el archivo de log no se puede escribir, se usa `error_log()`.

3. Compatibilidad de cifrado.
   - `SMTP_ENCRYPTION=smtps` usa SMTPS para puerto 465.
   - `SMTP_ENCRYPTION=tls` usa STARTTLS para puerto 587.
   - Si no se define, el valor por defecto es `smtps`.

4. Flujo de registro preservado.
   - La cuenta se sigue creando aunque el correo falle.
   - El fallo queda registrado para diagnóstico.
   - No se muestra error técnico al usuario final.

## Estado

Auditoría completada y corrección técnica aplicada. Falta validar con una cuenta nueva en el entorno donde existan credenciales SMTP reales.

## Validación en producción

Fecha: 2026-08-07

Se validó el flujo real en producción después de desplegar la corrección:

1. Primer intento después de configurar SMTP.
   - El registro creó el usuario correctamente.
   - El correo no salió.
   - `logs/errors.log` registró: `SMTP Error: Could not authenticate`.
   - El host, puerto y cifrado estaban correctos: `smtp.hostinger.com`, `465`, `smtps`.

2. Causa operativa confirmada.
   - La contraseña configurada para el buzón SMTP no correspondía a la contraseña real del buzón.
   - No era un problema del código ni del host SMTP.

3. Corrección aplicada en producción.
   - Se rotó la contraseña del buzón de correo en Hostinger.
   - Se actualizó `SMTP_PASSWORD` en `.env.php` de producción.
   - No se versionó ni documentó la contraseña.

4. Resultado final.
   - Se registró un usuario nuevo de prueba.
   - El correo de bienvenida fue enviado correctamente.
   - El flujo quedó validado en producción.
