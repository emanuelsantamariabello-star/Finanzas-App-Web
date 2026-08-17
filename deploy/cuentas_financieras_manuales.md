# Cuentas financieras manuales

## Alcance

Esta fase agrega el módulo local **Mis cuentas** para registrar dónde administra dinero cada usuario, sin modificar la lógica actual de ingresos, gastos, calendario o reportes.

Cada cuenta permite gestionar:

- Nombre.
- Tipo: banco, billetera digital, efectivo, ahorro, crédito u otra.
- Institución opcional.
- Saldo inicial manual, incluyendo valores negativos.
- Moneda mediante código de tres letras.
- Estado activo o inactivo.

## Base de datos

Aplicar en cada ambiente:

`database/migrations/2026_08_17_create_financial_accounts.sql`

La tabla `financial_accounts`:

- Asocia cada registro con `users.id`.
- Elimina las cuentas del usuario mediante `ON DELETE CASCADE`.
- Indexa consultas por usuario, estado y nombre.
- Mantiene `created_at` y `updated_at`.
- No almacena credenciales bancarias ni tokens externos.

## Seguridad

- Todas las operaciones requieren sesión autenticada y CSRF válido.
- Las consultas de lectura, edición y eliminación se limitan por `user_id`.
- Los tipos, estados, moneda, longitud de textos y saldo se validan en backend.
- La eliminación utiliza un modal de confirmación y no afecta movimientos actuales.

## Responsive

- Las tarjetas usan una columna en celular, dos en tablet y tres en pantallas amplias.
- Los formularios cambian automáticamente a campos de ancho completo en móvil.
- Los botones principales y acciones de tarjeta se apilan cuando falta espacio.
- En pantallas pequeñas, el acceso a **Mis cuentas** permanece dentro del menú de usuario para no saturar la barra superior.

## Límite funcional actual

El valor mostrado es el **saldo inicial registrado**, no un saldo calculado desde ingresos y gastos. Esta separación es intencional para no alterar el modelo financiero actual, donde cada gasto pertenece a un ingreso.

No se agregan todavía campos `account_id` a `incomes` o `expenses`. Esa relación deberá diseñarse en una fase posterior y utilizar claves foráneas opcionales, manteniendo compatibilidad con movimientos existentes.

## Preparación futura

La estructura admite ampliar una cuenta manual con metadatos de integración sin guardar esos datos sensibles en la misma tabla. Las conexiones bancarias u OAuth deberán utilizar tablas separadas para proveedor, tokens cifrados, estado de sincronización y referencias externas.

## Validación local realizada

- Creación, consulta, edición y eliminación dentro de una transacción de prueba.
- Bloqueo de acceso con un usuario diferente.
- Rechazo de moneda inválida.
- Verificación de migración mediante `deploy/smoke_test.php`.
- Confirmación de cero registros temporales después de las pruebas.
