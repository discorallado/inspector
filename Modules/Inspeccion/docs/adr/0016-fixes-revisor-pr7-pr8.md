# 0016 — Fixes de `/revisor` sobre PR7/PR8 (Kanban y Gantt)

> Estado: implementado. `/revisor` sobre el diff de PR7+PR8 encontró dos
> bugs de scope entre tableros (uno confirmado con reproducción real vía
> test antes de arreglar) y un crash de JS en el estado vacío del Gantt
> — reportado en vivo por el usuario al cargar la página real.

## Hallazgos y fixes

### 1. CRÍTICO — `TableroKanbanBoard::updateTareaStatus()` sin scope al tablero
`Tarea::findOrFail($tareaId)` no verificaba que la tarea perteneciera a
`$this->record`. Como `TareaPolicy::update()` es un Gate por **rol**, no
por registro (`tablero_tarea.actualizar`), el `authorize('update',
$tarea)` posterior no detectaba nada raro: cualquier usuario con esa
ability, parado en el Kanban del Tablero A, podía mandar por
`wire:call` el id de una tarea del Tablero B y moverla igual — el board
"scopeado a este tablero" no lo estaba en el único método de escritura.
Reproducido con un test antes de arreglar (una tarea de tablero B pasó
de `pendiente` a `en_progreso` operando desde el kanban de tablero A).

**Fix**: mismo criterio de scope que ya usaba el Gantt
(`tareaDelTablero()`) — `whereHas('actividad', fn ($q) => $q->where('tablero_id', $this->record->id))`
antes del `findOrFail()`. `[ARREGLADO]`

Adicional en el mismo método: `TaskStatus::from($status)` con un string
inválido lanzaba `\ValueError` sin capturar → 500. Cambiado a
`TaskStatus::tryFrom()` + `abort(422)` si no matchea. `[ARREGLADO]`

### 2. ALTO — `TableroGanttChart::persistirOrden()` sin scope del `actividadId` en `tareaOrdenes`
El payload trae dos arrays separados: `$actividadIds` (sí se filtraba
por `tablero_id`) y `$tareaOrdenes` (cada grupo trae su propio
`actividadId`, **sin relación verificada con el primer array ni con el
tablero**). `Tarea::where('actividad_id', $grupo['actividadId'])` hacía
match igual si esa actividad era de otro tablero. Reproducido con test:
reordenar tareas de una Actividad de otro tablero, operando desde el
Gantt de este, funcionaba.

**Fix**: se calcula `$actividadIdsDelTablero` (ids reales de
`$this->record->actividades()`) y cada grupo de `tareaOrdenes` se
descarta (`continue`) si su `actividadId` no está en ese set, antes de
tocar ninguna `Tarea`. De paso, todo el método quedó envuelto en
`DB::transaction()` — no había ninguna, y un fallo a mitad de la
secuencia de updates dejaba el orden parcialmente aplicado.
`[ARREGLADO]`

### 3. MEDIO — `agregarLink()` sin validar `type` ni rechazar auto-link
`int $type` se guardaba tal cual en `tarea_links.type` sin restringirlo
a los 4 valores válidos (0=FS 1=SS 2=FF 3=SF) — un `wire:call` directo
podía mandar cualquier entero hasta 255 (columna `unsignedTinyInteger`).
Tampoco se impedía `source_id === target_id` (una tarea dependiendo de
sí misma). `[ARREGLADO]`: `abort_unless(in_array($type, [0,1,2,3], true), 422)`
y `abort_if($origen->id === $destino->id, 422)`.

### 4. ALTO (bug funcional, no de seguridad) — `gantt.init()` truena en un tablero sin tareas
Reportado por el usuario probando la página real:
`Invalid value of the first argument of "gantt.init()"`. Causa: el
`<div id="dhx-gantt">` vive dentro del `@if (empty($ganttData['data']))
... @else ... @endif` de la vista — solo se renderiza si hay datos — pero
el bloque `@script` (que llama `gantt.init('dhx-gantt')`) está **fuera**
de ese condicional y corre siempre. Mismo bug heredado tal cual del
`gantt-chart.blade.php` de axon (no es nuevo de este port, axon lo
tiene también).

**Fix**: `[ARREGLADO]` — el `@script` completo se envolvió en una IIFE
con guard temprano (`if (! document.getElementById('dhx-gantt')) return;`)
en vez de reindentar todo el bloque dentro de un `if` — cambio mínimo,
sin tocar el resto del archivo.

### 5. BAJO — `tarea_links` sin índice en `source_id`/`target_id`
`getGanttData()` corre un `whereIn` sobre ambas columnas en cada carga
del Gantt, y `eliminarLink()` hace un `pluck('id')` amplio para
verificar pertenencia — sin índice, full scan a medida que crezca la
tabla. `[ARREGLADO]`: migración nueva
(`2026_08_02_001300_agrega_indices_a_tarea_links.php`, no se edita la
de PR4 ya corrida) agregando índice simple a ambas columnas.

## No arreglado — señalado, no reescrito

Ninguno. Los 5 hallazgos eran mecánicos (scope faltante siguiendo un
patrón ya existente en el propio código, validación de enum/rango,
guard de JS, índice) — no había nada ambiguo que requiriera una decisión
de producto o arquitectura para resolver.

## Verificación
- Cada bug de scope se reprodujo primero con un test real contra la BD
  antes de tocar código (no se asumió el bug por lectura, se confirmó).
- `ddev exec ./vendor/bin/pest --parallel`: 156 passed — 4 tests de
  regresión nuevos (scope cruzado en Kanban, scope cruzado en
  `persistirOrden`, `type` fuera de rango, auto-link) + 1 test del
  estado vacío del Gantt confirmando que ya no revienta.
- `ddev exec ./vendor/bin/pint --dirty`: limpio.
- `ddev exec php artisan migrate`: índice nuevo aplicado sin error.
- `rm -rf public/build && ddev exec npm run build`: sin errores.

## Siguiente paso

PR9: cleanup de `TableroHito`/`GrupoHito`/`EstadoAvance`.
