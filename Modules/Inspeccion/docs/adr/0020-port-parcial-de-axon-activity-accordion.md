# 0020 — Port parcial de axon ActivityAccordion al árbol de Inspeccion

> Estado: implementado. Sigue al ADR 0019 (árbol embebido) — acá se cierra
> la brecha de funcionalidad contra `axon` (app/Livewire/ActivityAccordion.php`)
> que el usuario pidió explícitamente ("importalo completo").

## Contexto

Después de construir el árbol Actividad/Tarea (ADR 0019), el usuario pidió
llevar la paridad de funcionalidad contra el `ActivityAccordion` de `axon`
(repo hermano, `/home/sebas/axon`). Comparando ambos, `ActivityAccordion`
ya usa el mismo patrón arquitectónico que se había elegido de forma
independiente para Inspeccion (RelationManager/Livewire + Actions montadas
a mano, sin `Filament\Tables\Table`) — buena señal de que el diseño del
ADR 0019 estaba bien encaminado.

Se encontraron 10 gaps reales. Con el usuario se acordó portar los 7 que no
requieren infraestructura nueva, y diferir 3 a una ronda aparte:

**Portados en este ADR:**
1. Reorder drag-and-drop (Actividades y Tareas, incluyendo mover una Tarea
   entre Actividades)
2. Insertar Tarea en una posición específica (antes/después de otra)
3. "Agendar fechas desde la tarea anterior"
4. Notificaciones (`Notification::make()`) en cada Action
5. Predecesoras editables desde el modal de Tarea (antes solo existía en
   el Gantt)
6. `Tarea::isOverdue()`
7. Estado calculado por Actividad (badge Pendiente/En Progreso/Completada)

**Diferidos explícitamente** (necesitan tabla/paquete nuevo, cada uno
amerita su propio `/arquitecto`):
- Asignados (`Task belongsToMany User`, pivot `role`) — Inspeccion no
  tiene ningún vínculo real a `User` en ningún modelo todavía.
- Comentarios (`HasFilamentComments`, paquete `parallax/filament-comments`)
  — no instalado en este repo.
- Adjuntos (`HasAttachments`) — misma razón.

**Explícitamente NO portado, nunca** (van contra CLAUDE.md §3.3 y la
naturaleza standalone de este repo):
- `HasOrganizationScope` (multi-tenancy real) — acá `organization_id` es
  un stub nullable a propósito, hasta la integración a `axon`.
- `HasUlids` — Inspeccion usa ids autoincrementales en todo el esquema ya
  construido; cambiar a ULID ahora sería una migración masiva sin
  beneficio real en este alcance.

## Decisión

### Reorder (`reordenarActividades()`/`reordenarTareas()`)

Mismo patrón que `TableroGanttChart::persistirOrden()` (ya existente):
SortableJS (misma versión/CDN que ya usa el Kanban) + un evento de
Alpine/`window.dispatchEvent` que dispara un método público del
RelationManager. `reordenarTareas()` acepta mover una Tarea a otra
Actividad (mismo `group` de SortableJS entre todas las listas de tareas,
igual que axon) — el `actividadId` destino se valida contra
`getOwnerRecord()` antes de escribir, mismo hallazgo de /revisor que ya
existía en `persistirOrden()` (un id ajeno no debe poder reordenar/mover
nada de otro Tablero).

### Predecesoras (`TareaDependencyService`)

Port directo de `TaskDependencyService` de axon (sync + detección de
ciclos por BFS). Única adaptación real: axon scopea por `project_id`
(columna real en `task_links`); acá `tarea_links` no tiene columna de
Tablero (ADR 0015 — mismo diseño no-normalizado que axon, un link puede
apuntar a una Actividad), así que el scope de la detección de ciclos se
resuelve al vuelo, filtrando a las Tareas de Actividades del mismo
Tablero que la Tarea editada.

Se agregaron `Tarea::predecessors()`/`successors()` (BelongsToMany sobre
`tarea_links`), espejo de los mismos métodos en `Task` de axon — antes
Inspeccion solo tenía las relaciones crudas `linksComoOrigen()`/
`linksComoDestino()`.

El campo `predecessors` en `TareaForm` no es una columna de `tareas`: se
extrae de `$data` antes de `Tarea::create()`/`update()` y se sincroniza
aparte vía el servicio (igual que axon hace con `unset($data['assignees'],
$data['predecessors'])`).

### `orden` pasa a ser real

Al construir insertar/reorder, `orden` (antes con `default(0)` sin ningún
campo que lo pusiera, ver el fix de ordering de esta misma sesión) pasa a
poblarse de verdad: `crearTareaAction()` calcula `max(orden)+1` de la
Actividad, `insertarTareaAction()` corre el orden de las tareas
siguientes, y el drag lo persiste directo.

### Estado calculado (`Actividad::estadoCalculado()`)

Port de `Activity::getStatusAttribute()` de axon, sin persistirse (igual
que `avance()`). Es un semáforo simple (todas completadas → Completada,
alguna activa → EnProgreso, resto → Pendiente), a diferencia de
`avance()` que pondera por `peso` — ambos coexisten, cada uno responde
una pregunta distinta.

## Qué se creó/cambió

- `Modules/Inspeccion/app/Enums/ActividadEstado.php` (nuevo)
- `Modules/Inspeccion/app/Services/TareaDependencyService.php` (nuevo)
- `Modules/Inspeccion/app/Models/Actividad.php` (`estadoCalculado()`)
- `Modules/Inspeccion/app/Models/Tarea.php` (`isOverdue()`,
  `predecessors()`, `successors()`)
- `Modules/Inspeccion/app/Filament/Resources/Tableros/Schemas/TareaForm.php`
  (campo `predecessors`)
- `Modules/Inspeccion/app/Filament/Resources/Tableros/RelationManagers/ActividadesRelationManager.php`
  (reorder, insertar, agendar fechas, notificaciones, sync de
  predecesoras en crear/editar)
- `Modules/Inspeccion/resources/views/.../actividades-arbol.blade.php`
  (drag handles, SortableJS, dropdown de acciones extra de Tarea, badges
  de estado/vencida)
- Tests: 15 casos nuevos en `ActividadesArbolRelationManagerTest.php`

## Siguiente paso

No se pudo probar el drag-and-drop en un navegador real (sin
Playwright/chromium-cli disponible en este entorno) — se verificó
render correcto vía Livewire/tinker y toda la suite en verde, pero la
interacción de arrastre en sí queda sin cubrir por un test automatizado
end-to-end. Recomendado probarlo manualmente antes de dar por cerrado
este ADR.

Pendiente, aparte, la tarea de backlog PR9 (retirar
`HitoLegado`/`GrupoHitoLegado`/`EstadoAvance`), que quedó interrumpida
por este pedido y no se tocó en esta sesión.
