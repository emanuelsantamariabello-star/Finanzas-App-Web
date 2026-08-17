# Auditoría de botones de regreso al dashboard

## Objetivo

Unificar la navegación de regreso al dashboard para que todas las vistas utilicen el mismo patrón visual, accesible y responsivo implementado inicialmente en el calendario financiero.

## Patrón aplicado

- Componente visual secundario `btn-action-secondary`.
- Clase compartida `btn-dashboard-return`.
- Icono de inicio `bi-house-door`.
- Texto uniforme `Volver al dashboard`.
- Alineación consistente de icono y texto.
- Ancho completo en pantallas móviles.
- Compatibilidad con modo claro y modo oscuro.

## Vistas auditadas y actualizadas

- `views/calendar/index.php`
- `views/expenses/index.php`
- `views/reports/index.php`
- `views/reports/graficas.php`
- `views/profile/index.php`
- `views/admin/notifications.php`
- `views/incomes/create.php`
- `views/incomes/edit.php`
- `views/profile/edit.php`
- `views/profile/password.php`

## Enlaces al dashboard excluidos

Se revisaron y conservaron sin cambios los enlaces que apuntan al dashboard pero no representan una acción de regreso:

- Logo principal del encabezado.
- Botón `Limpiar` del filtro por fechas.
- Acción `Limpiar filtro` del estado vacío del dashboard.

Estos controles mantienen su apariencia actual porque cumplen funciones distintas al patrón de navegación auditado.
