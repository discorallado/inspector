# 0015 — PR8: Gantt de Tarea (portado de axon, DHTMLX Community pinneado)

> Estado: implementado. Cierra el punto 5 del plan de PRs del ADR 0009
> (§8) y resuelve la deuda de licencia dejada abierta en el ADR 0009 §5.

## Contexto

Con PR7 (Kanban) ya en `main`, tocaba el Gantt de `Tarea` — mismo patrón
que `GanttChart.php`/`gantt-chart.blade.php` de axon (DHTMLX Gantt,
dependencias vía `TaskLink`, zoom, dark mode).

## Decisión

### Licencia DHTMLX: resuelta antes de portar, no heredada
El ADR 0009 §5 había flagueado que axon usa
`cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.{js,css}` — un CDN sin
versión/edición fijada que puede resolver a un build de evaluación PRO
con marca de agua, contradiciendo el propio ADR-0010 de axon (que dice
haber elegido frappe-gantt por ser open-source). Investigado ahora: desde
la v10, DHTMLX publica una **edición Community bajo MIT**, distribuida
como paquete npm real (`dhtmlx-gantt`, código fuente legible, no
ofuscado) — confirmado en `github.com/DHTMLX/gantt` y el anuncio oficial
de DHTMLX. Se pineó el Gantt de Inspeccion a
`https://cdn.jsdelivr.net/npm/dhtmlx-gantt@10.0.0/codebase/dhtmlxgantt.{js,css}`
(jsdelivr sirviendo directo el paquete npm versionado, no el CDN propio
de DHTMLX) — decisión tomada con el usuario vía pregunta explícita antes
de escribir código, entre 3 opciones (DHTMLX pinneado / DHTMLX "edge"
igual que axon / migrar a frappe-gantt). Un test (`TableroGanttChartTest`)
verifica que el HTML servido referencia la versión pinneada y **no**
contiene `cdn.dhtmlx.com/gantt/edge` — evita que un futuro copy-paste de
axon reintroduzca el CDN ambiguo sin que nadie lo note.

### Dependencias (`tarea_links`): solo Tarea-Tarea, no Tarea-Actividad
axon puede vincular tanto tareas como actividades en el Gantt porque
`task_links.source_id`/`target_id` son ULID — sin colisión posible entre
`Task` y `Activity`. Acá `tarea_links.source_id`/`target_id` son enteros
autoincrementales: una `Tarea #5` y una `Actividad #5` pueden coexistir,
y la tabla no tiene columna `tablero_id` propia para scopear (a
diferencia de `project_id` en axon). Se optó por **restringir las
dependencias del Gantt a Tarea-Tarea únicamente** en vez de rediseñar el
esquema de `tarea_links` (que ya corrió en PR4) — decisión de alcance
tomada acá, no consultada, consistente con CLAUDE.md §4 ("resuelve el
alcance del requerimiento, no el PMIS completo"): el caso de uso real
(dependencias de secuencia entre tareas) no pierde nada; lo que se
descarta es vincular una fila-resumen de Actividad, algo que ni axon usa
en la práctica (esas filas son `readonly`/`type=project`).

Doble resguardo:
- Cliente: `gantt.attachEvent('onBeforeLinkAdd', ...)` bloquea visualmente
  cualquier intento de link cuyo origen o destino tenga el prefijo
  `act-` (usado internamente por dhtmlx para las filas de Actividad).
- Servidor: `TableroGanttChart::agregarLink()` valida lo mismo y
  responde 422 si de todos modos llega — cubierto por test.

Se agregaron `Tarea::linksComoOrigen()`/`linksComoDestino()`
(`HasMany` hacia `TareaLink`), que la migración de PR4 había dejado
pendientes explícitamente para PR8.

### Progreso reutilizado, no duplicado
axon calcula el % de avance de cada tarea con un `taskProgress()` propio
(`match($task->status)`). Acá se reutiliza `TaskStatus::valor()`, que ya
existe desde PR4/ADR 0009 con el mismo propósito (alimentar
`CalculadorAvanceTablero`) — evita mantener dos fórmulas de "cuánto vale
cada estado" en paralelo.

### Estructura de la página — igual que axon
`TableroGanttChart` (`Page` + `InteractsWithRecord`), ruta
`/admin/tableros/{record}/gantt`. Mismos métodos que
`GanttChart.php` de axon, renombrados al español consistente con el
resto del módulo: `getGanttData()`, `updateTareaFechas()`,
`persistirOrden()`, `agregarLink()`, `eliminarLink()`,
`updateTareaDetalles()`, `refreshData()` — todos `#[Renderless]` salvo
`getGanttData()`/`mount()`, igual que axon (acá no aplica el mismo
argumento de PR7 sobre revertir drags rechazados: `updateTareaFechas()`
no pasa por ninguna máquina de estados que pueda rechazar el cambio,
solo fechas libres).

### Autorización — reutiliza policies existentes, sin abilities nuevas
- `mount()`: `authorize('view', $tablero)` → `tablero.ver` (igual que
  Kanban).
- `updateTareaFechas()`/`updateTareaDetalles()`: `authorize('update',
  $tarea)` → `TareaPolicy::update` → `tablero_tarea.actualizar`
  (super_admin, ingeniero, tecnico) — igual criterio que PR7.
- `persistirOrden()`/`agregarLink()`/`eliminarLink()`: `authorize('update',
  $tablero)` → `TableroPolicy::update` → `tablero.gestionar`
  (super_admin, ingeniero) — son cambios estructurales (reordenar,
  crear/borrar dependencias), no edición de una tarea puntual, así que
  usan la ability más restrictiva que ya existía para "gestionar" el
  tablero, igual que axon usa `authorize('update', $this->record)`
  (el `Project`) para las mismas operaciones.

## Verificación
- `ddev exec ./vendor/bin/pest --parallel`: 150 passed, 11 tests nuevos
  de `TableroGanttChartTest` (estructura de filas/progreso, drag de
  fechas por rol, reordenamiento por rol, creación/rechazo/borrado de
  links, scoping de `eliminarLink()` a tareas del propio tablero, HTML
  real de la ruta `/gantt` incluyendo la aserción negativa del CDN
  `edge`, link "Ver Gantt" del listado).
- `ddev exec ./vendor/bin/pint --dirty`: limpio.
- `rm -rf public/build && ddev exec npm run build`: sin errores, el
  theme sigue cubriendo la vista nueva del módulo.

## Alternativas descartadas

| Alternativa | Por qué se descartó |
|---|---|
| DHTMLX `edge` igual que axon | Mantenía la deuda de licencia del ADR 0009 §5 sin resolver — decisión del usuario, ver arriba |
| Migrar a frappe-gantt | Reescritura completa de la integración (sin `zoom.ext`, sin links visuales nativos, sin el mismo dark mode) en vez de portar 1:1; el usuario prefirió resolver la ambigüedad de licencia manteniendo DHTMLX |
| Rediseñar `tarea_links` con discriminador de tipo (`linkable_type`) para permitir Tarea-Actividad como axon | La tabla ya corrió en PR4 (no se edita una migración corrida, CLAUDE.md §4); y el caso de uso de vincular actividades completas no es real en la práctica, ni en axon |
| `TareaLink` con relaciones `BelongsToMany` genéricas hacia Tarea/Actividad | Con la restricción Tarea-Tarea ya no hace falta polimorfismo — un `HasMany` simple es suficiente y más honesto sobre lo que el dato realmente permite |

## Siguiente paso

PR9: cleanup, drop de `TableroHito`/`GrupoHito`/`EstadoAvance` (deprecados
desde PR5, sin uso una vez que Kanban y Gantt cubren el seguimiento
completo sobre `Actividad`/`Tarea`).
