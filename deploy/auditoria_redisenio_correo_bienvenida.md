# Auditoría y despliegue - Rediseño del correo de bienvenida

Fecha: 2026-08-06

## Alcance

Se trabajó en una rama separada para mejorar el contenido visual del correo de bienvenida sin modificar el flujo de registro, base de datos, rutas públicas ni pantallas del sistema.

Rama de trabajo:

- `codex/redisenio-correo-bienvenida`

Archivo funcional modificado:

- `app/helpers/mailer.php`

## Auditoría de identidad visual

Elementos revisados del sistema:

- Logo principal: `public/img/favicon.png`
- Paleta base: azul oscuro `#1E3A8A`, azul principal `#2563EB`, azul claro `#60A5FA`
- Estilo del sistema: tarjetas limpias, botones azules, bordes redondeados y fondo claro
- Enlace de acceso: `APP_URL` + `/views/auth/login.php`

## Cambios implementados

1. Rediseño profesional del correo de bienvenida.
   - Encabezado con degradado azul alineado con la marca.
   - Logo remoto tomado desde `APP_URL/public/img/favicon.png`.
   - Mensaje inicial personalizado con el nombre del usuario.
   - Tres bloques informativos sobre ingresos, evolución financiera y notificaciones.
   - Botón principal `Iniciar sesión` apuntando al login.
   - Footer institucional con el lema del sistema.

2. Compatibilidad técnica del correo.
   - Se mantiene `sendWelcomeEmail($toEmail, $toName)` para no afectar `web.php`.
   - Se mantiene el enlace al login.
   - Se mantiene el texto alternativo `AltBody` para clientes que no renderizan HTML.
   - Se define `UTF-8` explícitamente en PHPMailer para evitar problemas de acentos.

3. Seguridad y operación.
   - No se agregaron credenciales.
   - No se modificó `.env.php`.
   - No se tocaron tablas ni migraciones.
   - No se cambió la lógica de creación de usuarios.

## Validación local

Comando ejecutado:

```bash
C:\xampp\php\php.exe -l .\app\helpers\mailer.php
```

Resultado:

- Sin errores de sintaxis.

## Mapa de despliegue a producción

### Archivos que cambian

- `app/helpers/mailer.php`
- `deploy/auditoria_correo_bienvenida_v1_1.md`
- `deploy/auditoria_redisenio_correo_bienvenida.md`
- `.env.example.php`

### Archivos sensibles que no deben reemplazarse

- `.env.php`

### Base de datos

No hay cambios de base de datos para esta actualización.

### Configuración que debe existir en producción

El correo puede funcionar con las variables principales `SMTP_*` o con alias `MAIL_*`.

Variables recomendadas:

```php
$_ENV['SMTP_HOST'] = 'smtp.tu-proveedor.com';
$_ENV['SMTP_PORT'] = '465';
$_ENV['SMTP_ENCRYPTION'] = 'smtps';
$_ENV['SMTP_USERNAME'] = 'correo@tu-dominio.com';
$_ENV['SMTP_PASSWORD'] = 'password_smtp_seguro';
$_ENV['SMTP_FROM_EMAIL'] = 'correo@tu-dominio.com';
$_ENV['SMTP_FROM_NAME'] = 'Finanzas App';
$_ENV['APP_URL'] = 'https://finanzasappsan.com';
```

Para puerto 587, usar:

```php
$_ENV['SMTP_ENCRYPTION'] = 'tls';
```

## Prueba recomendada en producción

1. Subir el paquete de actualización.
2. Confirmar que `.env.php` conserva las credenciales reales.
3. Crear una cuenta nueva de prueba.
4. Confirmar que el usuario se crea correctamente.
5. Confirmar que llega el correo de bienvenida.
6. Revisar `logs/errors.log` si el correo no llega.
7. Confirmar que el botón `Iniciar sesión` abre el login de producción.

## Estado

Rediseño implementado y listo para empaquetar. Falta prueba real de envío en producción con credenciales SMTP reales.

## Validación final de producción

Fecha: 2026-08-07

La actualización fue desplegada y probada en producción:

- El servicio de correo de Hostinger está activo para `finanzasappsan.com`.
- El buzón usado para SMTP es `soporte@finanzasappsan.com`.
- La configuración final usa `smtp.hostinger.com`, puerto `465` y cifrado `smtps`.
- El error inicial `SMTP Error: Could not authenticate` se resolvió rotando la contraseña del buzón.
- Después de actualizar `SMTP_PASSWORD` en `.env.php`, el correo de bienvenida se envió correctamente.
- No hubo cambios de base de datos para esta actualización.
- `.env.php` sigue fuera del repositorio y no se versionaron credenciales.
