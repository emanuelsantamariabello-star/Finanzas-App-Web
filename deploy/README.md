# Deploy helpers: Logs y Backups

Archivos incluidos:

- `backup_db.sh`: script simple para volcar la base de datos con `mysqldump` y comprimir el resultado.
- `logrotate_finanzas.conf`: ejemplo de configuración para `logrotate` (colocar en `/etc/logrotate.d/finanzas_app`).

Pasos recomendados en el servidor:

1. Crear directorio de backups y asignar propietario correcto (ejemplo con usuario `www-data`):

```bash
sudo mkdir -p /var/backups/finanzas_app
sudo chown www-data:www-data /var/backups/finanzas_app
sudo chmod 750 /var/backups/finanzas_app
```

2. Editar `deploy/backup_db.sh` con las credenciales reales o configurar un usuario de sistema con un fichero `~/.my.cnf` seguro.

3. Añadir cron para ejecutar backup diario (ejemplo, como root o usuario con permisos):

```cron
# Ejecuta backup cada día a las 03:30
30 3 * * * /path/to/finanzas_app/deploy/backup_db.sh >> /var/log/finanzas_app_backup.log 2>&1
```

4. Instalar `deploy/logrotate_finanzas.conf` en `/etc/logrotate.d/finanzas_app` y ajustar rutas/usuario/versión de PHP-FPM en el `postrotate` si aplica.

5. Verificar que `logs/errors.log` sea escribible por el usuario del servidor web y protegido (ya existe `logs/.htaccess`).

Notas de seguridad:
- Nunca almacenes contraseñas en repositorio. Usa `/.env.php` fuera del control de versiones o `~/.my.cnf` con permisos `600`.
- Prueba los scripts manualmente antes de programar cron.
