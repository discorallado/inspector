# 0019 — Árbol Actividad→Tarea embebido en Tablero, CRUD 100% modal

> Estado: implementado. Cierra el diseño de `/arquitecto` sobre "actividades
> y tareas con modal para todo caso" + "tabla tipo tree".

## Contexto

El usuario pidió dos cosas sobre la gestión de Actividad/Tarea dentro de un
Tablero:

1. Que el CRUD de Actividad y Tarea sea siempre modal, nunca navegación de
   página completa.
2. Una vista tipo árbol que muestre Actividades y sus Tareas juntas, donde
   click en cualquiera de las dos abra el modal de crear/editar
   correspondiente.

El estado previo (PR6, ADR 0013) ya usaba modales para crear/editar
Actividad y, por separado, Tarea — pero el botón "Ver Tareas" de
`ActividadesRelationManager` navegaba a una página completa
(`ActividadResource::edit`), que a su vez alojaba
`TareasRelationManager` (Tarea es un `HasMany` real de Actividad, mientras
que `Tablero->tareas()` es un `HasManyThrough` sin soporte de `create()` —
por eso el CRUD de Tarea vivía en un Resource aparte). Ese salto de página
era exactamente lo que el pedido 1 quería eliminar, y el pedido 2 (ver
ambos niveles juntos) era imposible de resolver navegando a otro Resource.

## Decisión

### Por qué no es un RelationManager con `Table` de Filament

Filament no tiene un componente nativo de "tabla árbol" para dos modelos
heterogéneos — su soporte de nesting (`nesting()`) es para estructuras
auto-referenciales de un solo modelo. Actividad y Tarea son modelos
distintos con formularios distintos, así que ninguna combinación de
`Filament\Tables\Table` (ni `->groups()`, evaluado y descartado) representa
el árbol pedido sin perder la posibilidad de click-to-modal en ambos
niveles.

### Solución: `RelationManager` con `$view` custom + Actions montadas a mano

`ActividadesRelationManager` (`Modules/Inspeccion/app/Filament/Resources/Tableros/RelationManagers/ActividadesRelationManager.php`)
deja de definir `table()` y en su lugar sobreescribe `protected string
$view` apuntando a una vista Blade propia
(`resources/views/.../actividades-arbol.blade.php`) que renderiza el árbol
con Alpine (`x-collapse` por Actividad). `RelationManager` ya implementa
`HasActions` (`InteractsWithActions`) de fábrica — es lo mismo que usa
Filament por debajo para los botones de una tabla — así que no hace falta
salir del framework de Actions para tener modales reales:

- Cada operación (crear/editar/eliminar/restaurar/eliminar definitivo, por
  Actividad y por Tarea) es un método `xxxAction(): Action` normal,
  reconocido por convención de nombre por Filament (`mountAction('xxx')` →
  busca `xxxAction()`).
- Las acciones sobre un registro existente usan `->record(fn (array
  $arguments) => ...)` + `->authorize('update'|'delete'|'restore'|
  'forceDelete')`. `->authorize()` no es solo cosmético: Filament lo
  vuelve a chequear server-side antes de ejecutar la acción (no solo para
  ocultar el botón), así que sigue habiendo defensa real contra un
  `wire:call` manual con otro id.
- `crearTareaAction()` recibe `actividadId` como argumento de montaje
  (`mountAction('crearTarea', { actividadId: ... })`), no como owner
  record — el árbol no anida un RelationManager por Actividad.

### `TareaForm` como schema compartido, desacoplado de un owner record

Antes, los campos de Tarea vivían inline en
`Actividades\RelationManagers\TareasRelationManager::form()`, acoplados a
`$this->getOwnerRecord()->id` para la regla `unique(code)` por Actividad.
Se extrajo a `Tableros\Schemas\TareaForm` (mismo lugar que ya tenía
`ActividadForm`, movido desde `Actividades\Schemas\`), con `actividad_id`
como campo `Hidden` que el árbol llena vía `fillForm()` antes de abrir el
modal (tanto al crear como al editar). La regla `unique()` scopea contra
ese campo con `$get('actividad_id')`.

Gotcha real encontrado acá: `unique()` sin owner record ni
`->model()`/`->table()` explícito no lanza error — silenciosamente **no
aplica ninguna regla** (la condición interna de Filament que decide si
registrar la regla es `(bool) ($table ?? $model)`, y sin RelationManager
anidado ambos son null). El duplicado pasaba validación y reventaba recién
en el `UniqueConstraintViolationException` de la base de datos. Se arregló
pasando `table: 'tareas'` explícito. Cubierto por test
(`rechaza un code duplicado dentro de la misma Actividad`).

### Retiro de `ActividadResource`

Sin el botón "Ver Tareas", `ActividadResource` (Resource sin navegación,
solo servía de contenedor para `TareasRelationManager`) quedó sin ningún
punto de entrada — se eliminó junto con sus `Pages` y
`RelationManagers\TareasRelationManager`. El árbol embebido cubre el mismo
CRUD sin la navegación de página que existía antes.

### Papelera (soft deletes) sin `TrashedFilter`

Como no hay `Table`, no hay `AccionesBorradoLogico::filtros()` (el
`TrashedFilter` de Filament). Se reemplazó por una propiedad Livewire
`mostrarEliminados` (toggle simple) que agrega `withTrashed()` a la query
del árbol cuando está activa, mostrando ahí las acciones de
restaurar/eliminar definitivamente en vez de editar/eliminar. Mismo nivel
de capacidad que tenía la tabla anterior, sin la maquinaria de tabla.

## Alternativas descartadas

- **Página propia con URL, enlazada como Kanban/Gantt** (mismo patrón que
  `TableroKanbanBoard`/`TableroGanttChart`): más simple de construir y
  consistente con ese precedente, pero implica una navegación de página
  para llegar al árbol — justo lo que el pedido 1 quería evitar. Elegida en
  contra explícitamente por el usuario en `/arquitecto`.
- **`Filament\Tables\Table` con `->groups()`** (mismo mecanismo usado para
  agrupar `PruebaItem` por categoría): visualmente parecido a un árbol,
  pero el header de grupo no es clickeable en Filament — no hay forma
  nativa de abrir el modal de editar Actividad desde ahí, y
  `Tablero->tareas()` (`HasManyThrough`) no soporta `create()`. Descartada.
- **Copiar el patrón hand-rolled de `TableroGanttChart`** (modal HTML a
  mano con `x-data`/clases vanilla JS, sin `mountAction`): es lo que ya
  existe en el módulo para edición rápida de 4 campos de Tarea, pero acá
  hace falta el formulario completo (11 campos, validación real,
  reutilización de `ActividadForm`/`TareaForm`) — usar el sistema de
  Actions de Filament da eso gratis y es la única vía en este código donde
  la validación de formulario corre de verdad.

## Qué se creó/cambió

- `Modules/Inspeccion/app/Filament/Resources/Tableros/Schemas/ActividadForm.php` (movido)
- `Modules/Inspeccion/app/Filament/Resources/Tableros/Schemas/TareaForm.php` (nuevo, extraído)
- `Modules/Inspeccion/app/Filament/Resources/Tableros/RelationManagers/ActividadesRelationManager.php` (reescrito: árbol + Actions)
- `Modules/Inspeccion/resources/views/filament/resources/tableros/relation-managers/actividades-arbol.blade.php` (nuevo)
- `Modules/Inspeccion/lang/es/inspeccion.php` (claves `actividad.arbol.*`, `tarea.arbol.*`)
- Eliminado: `Modules/Inspeccion/app/Filament/Resources/Actividades/` completo (`ActividadResource`, `Pages`, `RelationManagers\TareasRelationManager`, `Schemas\ActividadForm`, `Tables\ActividadesTable`)
- `Modules/Inspeccion/tests/Feature/ActividadesArbolRelationManagerTest.php` (reemplaza a `TareasRelationManagerTest.php` y `ActividadesRelationManagerTest.php`, ambos ahora obsoletos)

## Siguiente paso

Correr `/revisor` antes de abrir PR — en particular vale la pena que
revise la autorización de las Actions montadas a mano (¿algún camino donde
`->record()`/`->authorize()` no alcancen a un id de otro Tablero?) y el
comportamiento de `mostrarEliminados` bajo concurrencia.
