# Checklist pre-lanzamiento

Antes de apuntar el dominio en producción y abrir la aplicación al público, sigue estos pasos:

1. Configuración
   - [ ] Crear `/.env.php` con credenciales reales y permisos `600`.
   - [ ] Confirmar que `APP_DEBUG` está en `false` en `app/config/app.php`.
   - [ ] Verificar `BASE_PATH` si despliegas en subdirectorio.

2. Seguridad
   - [ ] Instalar SSL/TLS (Let’s Encrypt u otro) y activar HTTPS en el vhost.
   - [ ] Verificar que `logs/`, `app/`, `vendor/` y `.env.php` NO sean accesibles desde web.
   - [ ] Revisar permisos: `logs/` escribible por webserver, no más permisivos de lo necesario.

3. Infraestructura
   - [ ] Ejecutar `php deploy/check_requirements.php` y resolver faltantes.
   - [ ] Configurar OPcache en `php.ini`.
   - [ ] Instalar `logrotate` y colocar `deploy/logrotate_finanzas.conf` en `/etc/logrotate.d/finanzas_app`.
   - [ ] Configurar backups: añadir `deploy/backup_db.sh` a cron y probar restauración.

4. Pruebas funcionales (manuales)
   - [ ] Registrar un usuario nuevo y verificar email único.
   - [ ] Login con credenciales recién creadas.
   - [ ] Crear un ingreso, agregar varios gastos, editar y eliminar un gasto.
   - [ ] Generar reporte PDF desde `public/reports/resumen_pdf.php`.
   - [ ] Probar cambio de contraseña y logout.

5. Smoke tests automáticos
   - [ ] Ejecutar `php deploy/smoke_test.php` y resolver errores reportados.

6. Go-live
   - [ ] Configurar DNS para tu dominio y esperar propagación.
   - [ ] Activar HTTPS y forzar redirección HTTP -> HTTPS.
   - [ ] Monitorizar logs y rendimiento las primeras 48 horas.

Notas:
- Antes de poner en producción, realiza una copia completa y prueba el proceso de restauración de la base de datos.
- Considera usar un entorno staging idéntico al de producción para pruebas previas.
