# 0005 — Kanban de Control de Cambios con relaticle/flowforge (PR2 del ADR 0003)

> Estado: implementado. Segundo PR del plan de 8 definido en
> [0003-rediseno-ux-seguimiento-terreno.md](0003-rediseno-ux-seguimiento-terreno.md) §6.
> Mismo patrón que
> [0004-kanban-observaciones-flowforge.md](0004-kanban-observaciones-flowforge.md) (PR1) —
> este ADR documenta solo las diferencias.

## Contexto

Extiende a `ControlCambio` el mismo patrón de kanban ya implementado y
revisado (`/ingeniero` + `/revisor` + `/qa`) para `Observacion` en PR1. No
hubo trabajo de descubrimiento sobre el paquete `relaticle/flowforge`
porque ya está instalado y probado — este PR reutiliza el conocimiento
adquirido en PR1 (ver ADR 0004 para el detalle de la API del paquete).

## Decisión

- **Columnas del board**: catálogo `EstadoCambio` (4 estados: Propuesto,
  Aprobado, Rechazado, Implementado — más que los 3 de `EstadoObservacion`
  en PR1), construidas dinámicamente desde BD.
- **Posición**: misma solución que PR1 — columna `posicion`
  (`decimal(20,10) nullable`) + índice único `(estado_cambio_id, posicion)`.
  `ControlCambioObserver::creating()` asigna la posición base **desde el
  arranque de este PR** (en PR1 este hook se agregó recién al pasar
  `/revisor` — acá se adelantó, evitando repetir el mismo hallazgo).
- **Validación de transición**: reutiliza `ControlCambioObserver::saving()`
  ya existente (sin cambios) — se dispara solo porque Flowforge usa
  `Eloquent::update()` internamente.
- **Autorización de `moveCard()`**: a diferencia de PR1 (una sola ability,
  `observacion.cerrar`), acá `ControlCambio` tiene 3 abilities distintas
  según la transición (`control_cambio.proponer`/`decidir`/`implementar`).
  Se replicó exactamente la misma condición que ya usa
  `ControlCambioPolicy::update()` — `Gate::any([...])` con las 3 — en vez
  de intentar distinguir qué ability aplica según el destino del drag
  (eso hubiera requerido lógica adicional para un beneficio marginal,
  dado que las acciones de card ya filtran correctamente por transición
  específica).
- **Acciones de card**: se reutilizó `ControlCambioActions::todas()`
  (aprobar/rechazar/implementar, cada una con su propio Gate y su propia
  condición de visibilidad por estado actual) — sin duplicar lógica.
- **Página**: `ControlCambiosBoard extends BoardResourcePage`, ruta
  adicional de `ControlCambioResource` (`/board`), no reemplaza el
  listado. Botón "Ver Kanban" agregado al header de `ListControlCambios`.

## Alternativas descartadas

| Alternativa | Por qué se descartó |
|---|---|
| Gatear `moveCard()` solo con `control_cambio.decidir` (ignorando `proponer`/`implementar`) | Habría sido más estricto que la Policy existente (`ControlCambioPolicy::update()`), inconsistente con el resto del módulo sin ninguna razón de negocio para la diferencia |
| Determinar la ability exacta según estado origen/destino dentro de `moveCard()` | Complejidad adicional sin beneficio real — las acciones de card (aprobar/rechazar/implementar) ya aplican el Gate correcto por transición específica; el drag crudo solo necesita el filtro amplio que ya define la Policy |

## Consecuencias

- Sin dependencias nuevas (reutiliza `relaticle/flowforge` ya instalado en PR1).
- Tests nuevos: `Modules/Inspeccion/tests/Feature/ControlCambioKanbanTest.php`
  (12 casos, mismo nivel de cobertura que `ObservacionKanbanTest.php`: acceso
  con/sin `tablero.ver`, transición válida/inválida, autorización de
  `moveCard()`, soft-delete, cardId inexistente, posición base al crear,
  acción "Aprobar" end-to-end desde una card, bloqueo de esa acción sin
  permiso, presencia de assets/columnas/botón). Suite completa: 90/90 en
  verde (2 risky sin fallas), Pint limpio.
- `ControlCambio.tablero_id` no es nullable (a diferencia de
  `Observacion.tablero_id`), así que no aplica el caso de borde "sin
  tablero asociado" que sí se probó en PR1.

## Siguiente paso

Correr `/revisor` y `/qa` sobre este diff antes de abrir el PR, igual que
en PR1. Luego, PR3 del ADR 0003: Vista de Tablero (sin Gantt todavía).
