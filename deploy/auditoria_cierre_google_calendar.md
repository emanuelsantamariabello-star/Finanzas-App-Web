# Auditoría de cierre - Google Calendar

## Resultado

La integración queda apta para despliegue controlado después de completar las validaciones automáticas y manuales documentadas.

## Seguridad

- Login Google y Calendar permanecen separados.
- Client Secret, tokens y clave de cifrado quedan fuera de Git.
- Tokens almacenados con AES-256-GCM.
- OAuth usa `state`, PKCE y vigencia de diez minutos.
- Callback y acciones requieren sesión autenticada.
- Sincronización y desconexión requieren POST y CSRF.
- `.htaccess` permite únicamente los endpoints OAuth definidos.
- El alcance solicitado se limita a `calendar.events`.

## Integridad

- Las migraciones son aditivas.
- `calendar_event_sync` evita duplicados por integración y evento local.
- La sincronización manual actualiza el evento remoto ya relacionado.
- Fallos externos no eliminan ni revierten eventos financieros locales.
- Eventos cancelados quedan excluidos de la sincronización manual.

## Responsive

- Escritorio conserva cuadrícula mensual de siete columnas.
- Celular conserva la estructura convencional de calendario mensual.
- Los días externos al mes permanecen visibles con menor opacidad.
- Los eventos usan tarjetas compactas y títulos truncados a dos líneas.
- Los montos se ocultan dentro de las celdas móviles para evitar desbordamientos; permanecen disponibles al abrir el evento.
- Acciones OAuth se apilan en pantallas pequeñas.

## Pendiente deliberado

- Sincronización automática al guardar, editar, cancelar o eliminar.
- Eliminación remota automática.
- Sincronización bidireccional.

Estos elementos no forman parte de este despliegue y deben tratarse como fases independientes.
