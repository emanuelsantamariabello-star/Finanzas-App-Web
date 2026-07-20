<?php
/*
   PLANTILLA .env.example.php

   Instrucciones para produccion:

   1. Copia este archivo a `.env.php`.
   2. Actualiza los valores con tus credenciales reales.
   3. Cambia permisos: chmod 600 .env.php.
   4. No subas `.env.php` a git.
   5. En el servidor, solo copia el `.env.php`, no el `.example`.
*/

// Configuracion de la base de datos
$_ENV['DB_HOST'] = 'tu_host_produccion.com';
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_NAME'] = 'nombre_base_datos';
$_ENV['DB_USER'] = 'usuario_seguro';
$_ENV['DB_PASS'] = 'password_muy_segura_aqui';

// Configuracion SMTP para correos transaccionales
$_ENV['SMTP_HOST'] = 'smtp.tu-proveedor.com';
$_ENV['SMTP_PORT'] = '465';
$_ENV['SMTP_USERNAME'] = 'correo@tu-dominio.com';
$_ENV['SMTP_PASSWORD'] = 'password_smtp_seguro';
$_ENV['SMTP_FROM_EMAIL'] = 'correo@tu-dominio.com';
$_ENV['SMTP_FROM_NAME'] = 'Finanzas App';
$_ENV['APP_URL'] = 'https://tu-dominio.com';
$_ENV['APP_BASE_PATH'] = '';

// Usuarios administradores separados por coma.
// Deben ser IDs existentes de la tabla users.
$_ENV['ADMIN_USER_IDS'] = '1';
