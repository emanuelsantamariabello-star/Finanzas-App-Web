# Pasos recomendados para poner la app en PRODUCCIÓN

Este documento reúne los pasos prácticos y comandos que conviene ejecutar en el servidor para poner `Finanzas App` en producción de forma segura.

IMPORTANTE: Ejecuta los comandos como administrador/root cuando sea necesario. Ajusta rutas, usuarios y nombres de servicio según tu proveedor.

1) Preparar el código

 - Subir todos los archivos al servidor (no subir `.env.php` desde repositorio; crea uno en servidor).
 - Si vas a servir desde la raíz del dominio, ajusta `BASE_PATH` en `app/config/app.php` a `''` o al subdirectorio real.

2) Permisos y .env

 - Copia la plantilla `.env.example.php` a `/.env.php` y edítala con credenciales reales.
 - Dar permisos seguros:

```bash
cd /var/www/finanzas_app
chown -R www-data:www-data .
find . -type d -exec chmod 750 {} \;
find . -type f -exec chmod 640 {} \;
# .env debe ser legible solo por el propietario
chmod 600 .env.php
```

3) DocumentRoot y .htaccess

 - Ideal: configurar `DocumentRoot` al directorio `public/`.
 - Si no puedes, asegúrate de que `.htaccess` raíz está activo (archivo creado) y protege `app/`, `vendor/`, `logs/` y `.env.php`.

4) SSL / HTTPS

 - Instalar certificado (Let's Encrypt recomendado):

```bash
apt-get update
apt-get install certbot python3-certbot-apache
certbot --apache -d example.com -d www.example.com
```

 - Forzar redirección HTTP → HTTPS (ya hay reglas comentadas en `.htaccess` y ejemplo de vhost en `deploy/apache_vhost.conf.example`).

5) PHP y OPcache

 - Instalar PHP 8.0+ y extensiones requeridas (`pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `gd` o `imagick`).
 - Activar OPcache en `php.ini` (valores recomendados):

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.validate_timestamps=1
```

 - Reiniciar PHP-FPM/Apache:

```bash
systemctl restart php8.0-fpm
systemctl restart apache2
```

6) Composer / dompdf

 - `vendor/dompdf` ya está incluido, pero si usas Composer en servidor:

```bash
cd /var/www/finanzas_app
composer install --no-dev --optimize-autoloader
```

7) Logs y rotación

 - Copiar `deploy/logrotate_finanzas.conf` a `/etc/logrotate.d/finanzas_app` y ajustar paths/usuario.
 - Verificar `logs/errors.log` y permisos.

8) Backups

 - Editar `deploy/backup_db.sh` con credenciales del usuario de backups y programar cron:

```cron
# Daily at 03:30
30 3 * * * /var/www/finanzas_app/deploy/backup_db.sh >> /var/log/finanzas_app_backup.log 2>&1
```

9) Ejecutar chequeos y pruebas

 - Ejecuta en servidor:

```bash
php /var/www/finanzas_app/deploy/check_requirements.php
php /var/www/finanzas_app/deploy/smoke_test.php
```

 - Realiza las pruebas manuales indicadas en `deploy/checklist_prelaunch.md` (registro, login, CRUD, PDF, cambio de contraseña).

10) Monitorización y mantenimiento

 - Configura alertas (logs, espacio en disco, uso CPU) y revisa los primeros días.
 - Considera configurar fail2ban, firewall (ufw) y backups remotos.

Si quieres, puedo:
- Generar un paquete `.tar.gz` listo para subir.
- Preparar instrucciones específicas para cPanel, Plesk o DigitalOcean.
- Automatizar la instalación de dependencias en un script (ansible/bash). 
