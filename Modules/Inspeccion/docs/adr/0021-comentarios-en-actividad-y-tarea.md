# 0021 — Comentarios en Actividad/Tarea + página ActividadDetalle

> Estado: implementado. Sigue al `/arquitecto` sobre comentarios, port de
> `ProjectResource\Pages\ViewActivity` de axon, y contador en el Kanban.

## Contexto

El usuario pidió tres cosas: (1) poder comentar en Actividades o Tareas,
(2) importar la página de detalle de Actividad de axon si existe (existe:
`ViewActivity`) y meter los comentarios ahí, (3) un botón con contador de
comentarios en el Kanban. El punto (3) no existe en axon (se comprobó
contra el Kanban real de axon) — es una pieza nueva, no una importación.

## Decisión

### Infra: `parallax/filament-comments` (paquete, no trait propio)

Elegido en `/arquitecto` sobre construir un `Comentario` polimórfico a
mano: mismo paquete que axon, misma tabla/modelo — cero fricción cuando
este módulo se copie a axon. Trait `HasFilamentComments` agregado a
`Actividad` y `Tarea`.

Migración publicada y movida a `Modules/Inspeccion/database/migrations/`
(CLAUDE.md — nada suelto en la raíz), con `organization_id` nullable
agregado a mano (el paquete no lo trae, es gratis dejarlo listo). El
`config/filament-comments.php` y la traducción `es` sí quedan en la raíz
(`config/`, `lang/vendor/filament-comments/`) — es la única ubicación
donde Laravel/el paquete los busca, no es código del módulo.

**Bug real encontrado al instalar**: el paquete no trae traducción `es`
(solo ar/de/en/fa/fr/it/nl/no/pt_BR/ru/uk) — sin agregarla, la UI de
comentarios queda en inglés, contra CLAUDE.md ("UI en español"). Se
publicó `lang/vendor/filament-comments/es/filament-comments.php` a mano.

**Dos discrepancias reales contra el blade de axon**, encontradas
empíricamente (`Livewire::mount()` a mano) antes de copiar el patrón a
ciegas:
- El alias Livewire registrado es `comments`, no `filament-comments`
  (`Livewire::component('comments', CommentsComponent::class)` en el
  service provider del paquete — confirmado idéntico en axon, así que el
  blade de axon usando `<livewire:filament-comments>` no debería
  funcionar tal cual ahí tampoco).
- La prop pública del componente es `$record`, no `$model`.

Acá se usa `<livewire:comments :record="$x" />`, verificado que resuelve.

### `ComentarioPolicy` custom (no la del paquete)

La policy default del paquete (`delete` = solo el dueño) no cubre lo que
pidió el usuario en `/arquitecto`: super_admin también puede moderar
comentarios ajenos (mismo criterio que `auditoria.purgar` en el resto del
módulo). `ComentarioPolicy` reemplaza la del paquete vía
`config('filament-comments.model_policy')`.

### `ActividadDetalle` — port de `ViewActivity`

Mismo patrón que `TableroKanbanBoard`/`TableroGanttChart` (`Page` +
`InteractsWithRecord`), ruta `/admin/tableros/{record}/actividades/{actividadId}`.
Cabecera (estado calculado, fechas, progreso) + lista de Tareas con
comentarios colapsables por tarea + comentarios de la Actividad al final.
Soporta `?focus={tareaId}` (scroll + highlight), igual que axon.

**Bug real de Filament encontrado y evitado**: el segundo parámetro de
ruta NO puede llamarse `actividad` — aunque el método lo tipe `int|string`,
un nombre de route param que matchea un modelo Eloquent existente
(`Actividad`) dispara el binding implícito de Laravel/Filament de todos
modos, y el parámetro llega como el JSON serializado del registro
completo en vez del id crudo. Se renombró a `actividadId` (documentado
en el docblock de `mount()` para que no se repita).

### Puntos de entrada

1. Ícono "Ver detalle" por fila de Actividad en el árbol
   (`ActividadesRelationManager` — helper `urlDetalleActividad()`, ya que
   es una navegación real, no un modal).
2. Contador de comentarios en cada card del Kanban
   (`TableroKanbanBoard::getColumns()` con `withCount('filamentComments')`
   para no meter N+1 — el conteo por fila con `->count()` habría sido el
   error obvio acá), enlaza con `focus` a la Tarea de esa card.

## Bugs no relacionados, encontrados y arreglados en el camino

Durante esta sesión, probando el árbol en vivo, el usuario reportó tres
bugs reales que se arreglaron junto con lo de arriba (no eran parte del
pedido de comentarios, pero bloqueaban probarlo):

1. **Modales del árbol no se veían al hacer click**: `actividades-arbol.blade.php`
   sobreescribe `$view` del RelationManager por completo, y se perdió
   `<x-filament-actions::modals />` — el partial que Filament necesita
   explícito en cualquier Livewire component `HasActions` que no pase por
   el pipeline estándar de `Table`. Sin él, las Actions se montan
   (cambia el estado server-side) pero nunca se renderiza el modal.
2. **Borrar un Tablero/Proyecto/Actividad/Tarea con hijos asociados
   tiraba un 500 con stack trace de SQL** en vez de un aviso:
   `DeleteAction`/las Actions de forceDelete no atrapaban la
   `QueryException` de la FK `RESTRICT` (comportamiento esperado, ver
   `RestriccionBorradoFisicoTest`). Nuevo helper
   `AccionesBorradoFisico::intentar()`/`eliminar()` atrapa
   específicamente SQLSTATE 23000 y muestra una notificación en vez de
   reventar; reutilizado en `EditTablero`, `EditProyecto`, y los dos
   `eliminarDefinitivo*Action()` del árbol.
3. **`TareaObserver::deleted()`/`saved()` truena con "Attempt to read
   property tablero on null"** al borrar/tocar una Tarea cuya Actividad
   ya está en la papelera: `$tarea->actividad` (`belongsTo` sin
   `withTrashed()`) devuelve `null` cuando el padre está soft-deleted.
   Arreglado resolviendo con `->actividad()->withTrashed()->first()` y
   un guard de null.

## Qué se creó/cambió

- Composer: `parallax/filament-comments` (`^3.0`)
- `Modules/Inspeccion/database/migrations/2026_08_07_090000_create_filament_comments_table.php` (+organization_id)
- `Modules/Inspeccion/database/migrations/2026_08_07_090001_add_index_to_subject.php`
- `config/filament-comments.php`, `lang/vendor/filament-comments/es/filament-comments.php`
- `Modules/Inspeccion/app/Policies/ComentarioPolicy.php` (nuevo)
- `Modules/Inspeccion/app/Models/Actividad.php`, `Tarea.php` (trait `HasFilamentComments`)
- `Modules/Inspeccion/app/Filament/Resources/Tableros/Pages/ActividadDetalle.php` (nuevo) + blade
- `Modules/Inspeccion/app/Filament/Resources/Tableros/TableroResource.php` (ruta nueva)
- `Modules/Inspeccion/app/Filament/Resources/Tableros/RelationManagers/ActividadesRelationManager.php` (link "Ver detalle")
- `Modules/Inspeccion/app/Filament/Resources/Tableros/Pages/TableroKanbanBoard.php` (contador + `withCount`)
- `Modules/Inspeccion/app/Filament/Support/AccionesBorradoFisico.php` (nuevo helper, bug #2)
- `Modules/Inspeccion/app/Observers/TareaObserver.php` (bug #3)
- `Modules/Inspeccion/resources/views/filament/resources/tableros/relation-managers/actividades-arbol.blade.php` (bug #1 + link)
- Tests: `ActividadDetalleTest.php` (nuevo), casos nuevos en `ActividadesArbolRelationManagerTest.php` y `RestriccionBorradoFisicoTest.php`

## Diferido a propósito

Asignados y adjuntos (`HasAttachments`) — mencionados en el `/arquitecto`
original de axon pero fuera del pedido de esta ronda; quedan para pedirse
explícitamente, cada uno con su propio diseño.

## Siguiente paso

Correr `/revisor` — en particular la autorización de `ComentarioPolicy`
contra intentos de comentar/borrar cruzando Tableros, y confirmar que el
paquete `parallax/filament-comments` no reintroduce alguna dependencia
(`spatie/laravel-permission`, etc.) que CLAUDE.md pide evitar en este
repo.
