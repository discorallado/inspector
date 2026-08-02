# 0014 — PR7: Kanban de Tarea (portado de axon)

> Estado: implementado. Cierra el punto 4 del plan de PRs del ADR 0009
> (§8), diferido desde el ADR 0008 §5.

## Contexto

Con PR4-PR6 ya en `main` (migración de datos, `ActividadesRelationManager`,
`CalculadorAvanceTablero` sobre `Tarea.peso`), tocaba el Kanban de `Tarea`
que el ADR 0009 §2.4 dejó planteado: mismo patrón que
`KanbanBoard.php`/`kanban-board.blade.php` de axon (Livewire + SortableJS
vía CDN), filtrado por `tablero_id`.

## Decisión

### Página y ruta
`TableroKanbanBoard` (`Filament\Resources\Pages\Page` +
`InteractsWithRecord`), registrada como página adicional de
`TableroResource` en la ruta `/admin/tableros/{record}/kanban`. Acceso
desde un botón "Ver Kanban" en la tabla de listado (`TablerosTable`) y en
las acciones de cabecera de `EditTablero`.

### Adaptación del query respecto a axon
axon filtra `Task::whereHas('activity', fn ($q) => $q->where('project_id', ...))`.
Acá el equivalente es `Tarea::whereHas('actividad', fn ($q) => $q->where('tablero_id', ...))`
— un nivel de scope distinto (`Tablero` en vez de `Project`), sin el filtro
de proyecto suelto que tiene axon porque acá `Tablero` ya es el nivel que
acota.

### Diferencia deliberada: `updateTareaStatus()` NO es `#[Renderless]`
axon marca su equivalente `updateTaskStatus()` con `#[Renderless]` (evita
un re-render Livewire completo por cada drag, por rendimiento) — ahí no
hace falta más: `TaskStatus` de axon no valida transiciones, cualquier
salto vale.

Acá `Tarea.status` sí pasa por `TransicionEstadoGuard` vía
`TareaObserver::saving()` (ADR 0009 §2.3, generalizado desde el guard que
ya usan Observación/ControlCambio). Un salto inválido lanza
`TransicionEstadoInvalidaException`, capturada en `updateTareaStatus()`
para mostrar una `Notification` en vez de un error 500 — pero sin
`#[Renderless]`, si se hubiera dejado, la tarjeta habría quedado
visualmente en la columna nueva (SortableJS ya la movió en el DOM del
lado del navegador) aunque el update se descartó server-side. Se sacó el
atributo a propósito: con render normal, Livewire recalcula
`getColumns()` desde la BD real tras cada llamada, y el hook
`Livewire.hook('morph.updated', () => initKanban())` ya presente en el
script portado reinicializa Sortable — la tarjeta vuelve sola a su
columna real cuando el salto se rechaza.

### Autorización
`TareaPolicy::update` (`tablero_tarea.actualizar`: super_admin,
ingeniero, tecnico) ya existía desde PR4/ADR 0009 §4 — se reutiliza tal
cual en `$this->authorize('update', $tarea)`, sin necesidad de una
policy nueva. `mount()` verifica `tablero.ver` para el acceso a la
página completa (mismo patrón que axon).

**Hallazgo de testing, no de producto**: `Livewire::test()->call()` no
relanza `AuthorizationException` al test — Livewire la excluye
explícitamente de "sin manejo" en
`Features\SupportTesting\RequestBroker::temporarilyDisableExceptionHandlingAndMiddleware()`,
así que el 403 se resuelve puertas adentro sin propagarse como excepción
PHP capturable. El test de "rol sin ability no puede mover tareas"
verifica el efecto real (el `status` no cambió), no una excepción
lanzada — documentado inline en el test para que no se reintente ese
patrón por error en el futuro.

### Colores/iconos de los enums
`TaskStatus`/`TaskPriority` (Inspeccion) no implementaban `HasColor`/
`HasIcon` — axon sí. Se agregaron ambas interfaces con los mismos mapeos
de color semántico de Filament (gray/info/warning/success/danger) que
usa axon, portados literal. Cambio aditivo: no afecta los usos previos de
estos enums en `ActividadesRelationManager`/`TareasRelationManager`
(PR6), que solo usaban `HasLabel`.

### Recortado respecto a axon
- Sin avatares de asignados (`assignees`) ni el botón "ver actividad": el
  modelo `Tarea` de Inspeccion todavía no tiene asignación de usuarios
  (fuera de alcance de PR4-PR7; `tablero_tarea.asignar` está en la
  matriz de permisos del ADR 0009 §4 pero sin UI todavía).
- El pie de la tarjeta muestra `actividad.nombre` + `peso` en vez de
  avatares — información que sí existe en el modelo de Inspeccion y es
  más relevante para el caso de uso (seguimiento de avance ponderado).

### Paquete `relaticle/flowforge`
Sigue sin usarse (igual que antes del ADR 0008) — el Kanban portado de
axon es Livewire + SortableJS vía CDN, Tailwind puro, no flowforge. Se
mantiene instalado sin acción, la decisión de si vale la pena
desinstalarlo queda fuera de alcance de este PR.

## Verificación
- `ddev exec ./vendor/bin/pest --parallel`: 139 passed (suite completa),
  8 tests nuevos de `TableroKanbanBoardTest` cubriendo agrupamiento por
  status, transición válida/inválida, autorización por rol, filtro por
  actividad, y HTML real de la ruta `/kanban` + el link del listado.
- `ddev exec ./vendor/bin/pint --dirty`: limpio.
- `rm -rf public/build && ddev exec npm run build` + `curl` contra
  `https://inspector.ddev.site/build/assets/theme-*.css`: 200, el theme
  compila sin errores con los nuevos `HasColor`/`HasIcon` en los enums.

## Alternativas descartadas

| Alternativa | Por qué se descartó |
|---|---|
| Mantener `#[Renderless]` como axon | Rompería la reversión visual de un drag rechazado por `TransicionEstadoGuard` — axon no tiene ese problema porque no valida transiciones |
| Kanban con `relaticle/flowforge` | El ADR 0009 ya decidió portar el patrón real de axon (Livewire+SortableJS), no reabrir esa decisión acá |
| Policy nueva para autorizar el drag | `TareaPolicy::update` ya cubre exactamente este caso de uso, reutilizarla evita una policy paralela con la misma regla |

## Siguiente paso

PR8 (Gantt de `Tarea`, DHTMLX portado de axon — con la deuda de licencia
del ADR 0009 §5 todavía pendiente de resolver antes de producción real).
