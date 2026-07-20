# 💰 Finanzas App Web

> **Sistema web para la gestión inteligente de finanzas personales**, desarrollado como proyecto de ingeniería de software utilizando **PHP, MySQL y JavaScript**.

Finanzas App Web nace con el objetivo de ayudar a las personas a administrar sus ingresos y gastos de manera sencilla, organizada y segura, permitiéndoles visualizar su situación financiera mediante reportes, balances y herramientas de análisis.

Además de resolver una necesidad real, el proyecto fue diseñado para aplicar buenas prácticas de desarrollo de software, priorizando aspectos como la seguridad, la organización del código, la escalabilidad y la experiencia del usuario.

---

# 📌 Estado del proyecto

🟢 **En desarrollo activo**

Versión actual: **v1.1**

La versión **v1.1** incorpora mejoras técnicas, funcionales y visuales enfocadas en estabilidad, reportes, notificaciones, experiencia responsive y preparación para producción.

---

# 🚀 Funcionalidades principales

### Gestión de usuarios

- Registro de usuarios.
- Inicio de sesión seguro.
- Cierre de sesión.
- Actualización de perfil.
- Cambio de contraseña.

### Gestión financiera

- Registro de ingresos.
- Registro de gastos.
- Asociación de gastos a un ingreso.
- Balance financiero.
- Cálculo automático de totales.
- Historial financiero.
- Filtros por rango de fechas en el dashboard.
- Evolución de saldo acumulado por fechas.
- Coach financiero con clasificación de gastos necesarios y gustos.

### Reportes

- Dashboard financiero.
- Resumen general.
- Exportación de reportes a PDF.
- Visualización organizada de la información financiera.
- Gráficas financieras de ingresos, gastos, saldo y evolución acumulada.
- Generación de PDF compatible con entornos sin extensión GD local.

### Notificaciones

- Panel de notificaciones mediante campana en el navbar.
- Recordatorios automáticos según la fecha habitual de ingresos del usuario.
- Notificaciones del navegador con permiso del usuario.
- Actualización automática de notificaciones mediante endpoint sin caché.
- Marcado local de notificaciones como leídas.
- Novedades globales del sistema para todos los usuarios.
- Panel administrativo para crear, activar, desactivar y eliminar novedades.

### Seguridad

- Protección CSRF.
- Validación de formularios.
- Escape de datos.
- Hash seguro de contraseñas.
- Variables de entorno.
- Consultas preparadas mediante PDO.
- Acceso administrativo controlado por variable de entorno.

---

# 🛠 Tecnologías utilizadas

## Backend

- PHP 8
- MySQL
- Apache

## Frontend

- HTML5
- CSS3
- JavaScript

## Librerías

- DomPDF
- PHPMailer

## Herramientas

- Git
- GitHub
- Visual Studio Code
- XAMPP

---

# 📂 Arquitectura del proyecto

```
FINANZAS_APP_WEB/
│
├── app/
│   ├── config/
│   └── helpers/
│
├── database/
│
├── deploy/
│
├── logs/
│
├── public/
│
├── routes/
│
├── vendor/
│
├── vend0r/
│
├── views/
│
├── README.md
├── web.php
└── index.php
```

La aplicación se encuentra organizada por módulos, separando la configuración, los recursos públicos, las vistas, las rutas, la base de datos y los componentes auxiliares, facilitando el mantenimiento y futuras ampliaciones del sistema.

En la versión **v1.1** se agregaron helpers compartidos para centralizar consultas financieras, periodos de reportes, notificaciones y validación de acceso administrativo.

---

# 📸 Capturas

> Próximamente se incorporarán capturas del sistema.

- Inicio de sesión
- Dashboard
- Gestión de ingresos
- Gestión de gastos
- Reportes
- Notificaciones
- Panel administrativo de novedades
- Perfil
- Exportación a PDF

---

# ⚙ Requisitos

- PHP 8.0 o superior
- MySQL o MariaDB
- Apache
- PDO
- pdo_mysql
- mbstring
- fileinfo
- openssl
- GD o ImageMagick

Recomendado:

- XAMPP
- OPcache habilitado

---

# 💻 Instalación

## Clonar el repositorio

```bash
git clone https://github.com/TU-USUARIO/Finanzas-App-Web.git
```

## Crear la base de datos

Ejecutar:

```
database/schema.sql
```

## Configurar variables de entorno

Copiar:

```
.env.example.php
```

como:

```
.env.php
```

y configurar las credenciales.

## Configurar BASE_PATH

Modificar:

```
app/config/app.php
```

## Ejecutar

Abrir:

```
http://localhost/finanzas_app
```

Si el proyecto se instala en XAMPP dentro de `htdocs/finanzas_app_web`, configurar:

```
$_ENV['APP_BASE_PATH'] = '/finanzas_app_web';
```

Abrir:

```
http://localhost/finanzas_app_web
```

---

# 📊 Primer uso

1. Crear una cuenta.
2. Iniciar sesión.
3. Registrar ingresos.
4. Registrar gastos.
5. Consultar el dashboard.
6. Generar reportes PDF.
7. Activar notificaciones del navegador si se desean recibir avisos.

---

# 🌐 Producción

Antes de desplegar:

- Desactivar APP_DEBUG.
- Configurar correctamente `.env.php`.
- Configurar HTTPS.
- Ajustar BASE_PATH.
- Revisar permisos de logs.
- Ejecutar migraciones pendientes de base de datos.
- Configurar `ADMIN_USER_IDS` con el ID del usuario administrador.
- Verificar que la tabla `system_notifications` exista para el panel de novedades.

Migraciones relevantes para v1.1:

```
database/migrations/2026_07_19_add_expenses_reflection_type.sql
database/migrations/2026_07_19_create_system_notifications.sql
```

Variable administrativa:

```
$_ENV['ADMIN_USER_IDS'] = '1';
```

El valor debe corresponder al `id` real del usuario administrador en la tabla `users`.

---

# ⚡ Rendimiento

El proyecto incorpora diferentes estrategias para mejorar el rendimiento:

- Versionado automático de archivos CSS y JavaScript mediante `filemtime()`.
- Compatibilidad con OPcache.
- Organización modular del código.
- Uso de consultas preparadas con PDO.
- Reutilización de consultas financieras mediante helpers.
- Endpoint de notificaciones con `Cache-Control: no-store`.

---

# 📦 Dependencias

Actualmente el proyecto utiliza:

- DomPDF
- PHPMailer

Las dependencias se encuentran integradas en el proyecto para facilitar su instalación sin requerir Composer.

---

# 🔒 Seguridad implementada

- Protección CSRF.
- Variables de entorno.
- Validación de formularios.
- Escape de datos.
- Hash seguro de contraseñas.
- Regeneración del identificador de sesión.
- Consultas preparadas mediante PDO.

---

# 🗺 Roadmap

## v1.1

- Filtros por fecha en dashboard.
- Recordatorios automáticos de registro financiero.
- Panel de notificaciones y notificaciones del navegador.
- Novedades globales del sistema.
- Panel administrativo para gestionar novedades.
- Gráfica de evolución de saldo acumulado por fechas.
- Mejoras responsive en vistas principales.
- Refactorización de consultas financieras y reportes.

## v1.2

- Categorías personalizadas.
- Presupuestos.
- Metas financieras.

## v2.0

- API REST.
- Aplicación móvil Flutter.
- Sincronización entre dispositivos.
- Notificaciones inteligentes avanzadas.
- Inteligencia Artificial para análisis financiero.
- Estadísticas avanzadas.

---

# 👨‍💻 Autor

**Emanuel Esteban Santamaría Bello**

Estudiante de Ingeniería de Sistemas.

Desarrollador Full Stack en formación con interés en el desarrollo de software, arquitectura de aplicaciones, desarrollo web y aplicaciones móviles.

**GitHub**

https://github.com/emanuelsantamariabello-star

**LinkedIn**

https://www.linkedin.com/in/emanuel-steban-santamaría-bello-301a5b399

---

# 📄 Licencia

Este proyecto fue desarrollado con fines educativos, de aprendizaje y como parte del portafolio profesional del autor.

© 2026 Emanuel Esteban Santamaría Bello. Todos los derechos reservados.
