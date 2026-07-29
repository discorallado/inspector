# 0008 — Revierte el kanban de Observaciones/Control de Cambios a tabla con select inline

> Estado: implementado. Reemplaza, para estas dos entidades, la decisión
> tomada en el ADR 0004 (PR1) y ADR 0005 (PR2). El kanban como concepto
> **no se descarta** — el usuario lo quiere para el seguimiento de
> hitos/tareas de fabricación de `Tablero`, pero esa pieza queda diferida
> (ver §5) hasta definir cómo se integra con el modelo de
> Actividad/Tarea de `axon`.

## Contexto

Con PR1 y PR2 ya implementados, revisados (`/revisor`) y probados
(`/qa`), el usuario probó el resultado real y pidió explícitamente
revertir el enfoque para estas dos entidades:

> "los cambios necesitan un cambio de estado más estático, tipo tabla con
> columnas select para cambiar entre estados"

Y, tras aclarar el alcance en la conversación: el kanban de Observaciones
(PR1) también vuelve a tabla — solo se mencionó "los cambios" al
principio, pero el usuario confirmó que ambas entidades cambian, no solo
`ControlCambio`.

## Decisión

### Observaciones
- Se elimina `ObservacionesBoard.php` y su ruta `board` en
  `ObservacionResource`. Se saca el botón "Ver Kanban" de
  `ListObservacions`.
- `ObservacionsTable` gana una columna `SelectColumn::make('estado_observacion_id')`
  que reemplaza la antigua `TextColumn` de solo lectura — cambia el
  estado inline, sin abrir nada.
- La acción "Cerrar" (`ObservacionActions`) se mantiene tal cual estaba:
  el select sirve para reclasificaciones rápidas, "Cerrar" sigue siendo
  el camino para cerrar formalmente con `fecha_cierre` y
  `observacion_cierre`.

### Control de Cambios
- Se elimina `ControlCambiosBoard.php` y su ruta `board`. Se saca el
  botón "Ver Kanban" de `ListControlCambios`.
- `ControlCambiosTable` gana `SelectColumn::make('estado_cambio_id')`.
- Las 4 acciones (aprobar/rechazar/implementar + la nueva
  `desimplementar`, ver abajo) se agrupan en un `ActionGroup` en vez de
  aparecer como 4 botones sueltos en cada fila.
- **Nueva acción `desimplementar`**: revierte `Implementado -> Aprobado`,
  por si un cambio se marcó implementado por error o hay que reabrirlo.
  Misma ability que `implementar` (`control_cambio.implementar`) — quien
  puede marcar la implementación puede deshacerla. Se agregó la
  transición `[implementado, aprobado]` a
  `TransicionEstadoPermitidaSeeder`.

### Modelo de datos
- Nueva migración `2026_07_31_090000_quita_posicion_de_observaciones_y_control_cambios.php`
  (no se editan las migraciones de PR1/PR2 ya corridas) que dropea la
  columna `posicion` de ambas tablas — ya no la usa nada.
- **Detalle no trivial encontrado al correrla contra MariaDB real**:
  `estado_observacion_id`/`estado_cambio_id` son FK, y el único índice
  que las tenía como columna izquierda era justo el `unique(estado_*,
  posicion)` que se estaba borrando — MariaDB rechaza dropear un índice
  del que depende una foreign key (error 1553). Se agregó un índice
  simple de reemplazo (`estado_observacion_id`/`estado_cambio_id` solos)
  antes de dropear el compuesto. Verificado con `migrate` y
  `migrate:rollback --step=1` contra MariaDB real, no solo que compile.
- Se sacaron los hooks `creating()` de `ObservacionObserver` y
  `ControlCambioObserver` que asignaban `posicion` — ya no hay columna
  que llenar.

### Autorización del `SelectColumn` — mismo hallazgo que en PR2, corregido desde el arranque
Confirmado leyendo el código fuente de Filament
(`Filament\Tables\Concerns\HasColumns::updateTableColumnState()`): un
`SelectColumn` **no respeta Policies por defecto** — hay que usar
`->disabled()` (si el usuario no tiene la ability, el update se
descarta server-side antes de guardar) y, más importante, **las opciones
del `options()` son la validación real**: el valor recibido se valida
contra `Rule::in()` derivado de las opciones evaluadas para ese
`$record` en esa request — un valor fuera de las opciones se rechaza
aunque alguien lo mande directo por Livewire sin pasar por el `<select>`
del navegador.

Para `ControlCambio`, esto importaba especialmente: un `Gate::any(['proponer',
'decidir', 'implementar'])` genérico en `disabled()` habría dejado a un
rol con **solo** `control_cambio.proponer` (ej. `tecnico`) aprobar/rechazar/implementar
vía el select, aunque los botones agrupados se lo nieguen correctamente
— exactamente el mismo hallazgo que `/revisor` encontró en el
`moveCard()` del kanban de PR2. Se corrigió **antes** de cerrar este PR,
no después: `opcionesEstadoDestino()` filtra cada destino candidato por
la ability específica que le corresponde (aprobado/rechazado ->
`decidir`, implementado -> `implementar`), no por "tiene alguna ability
del módulo".

### Paquete `relaticle/flowforge`
Sigue instalado — no se desinstaló. El usuario quiere un kanban para
hitos/tareas de fabricación de `Tablero` más adelante (§5), así que
mantenerlo evita pagar el costo de reinstalación/reverificación cuando
se retome esa pieza.

## Alternativas descartadas

| Alternativa | Por qué se descartó |
|---|---|
| `disabled()` con `Gate::any([...])` genérico en `ControlCambio` | Repetía el hallazgo de seguridad que ya corrigió `/revisor` en PR2 — un rol con una sola ability podría cambiar a cualquier estado |
| Desinstalar `relaticle/flowforge` | El usuario planea un kanban de hitos/tareas más adelante; desinstalar/reinstalar sería trabajo repetido |
| Editar las migraciones de PR1/PR2 para sacar `posicion` en vez de agregar una nueva | Ya corrieron en el entorno del usuario — editar una migración ya corrida es exactamente lo que `CLAUDE.md` pide evitar |

## Riesgos y supuestos

- La UX de "3-4 botones agrupados en un dropdown" para Control de
  Cambios es una preferencia declarada del usuario, no verificada
  visualmente por mí (sin herramienta de navegador en este entorno).
- Este ADR **no resuelve** la pregunta de fondo que el usuario planteó
  sobre el kanban de hitos/tareas y su relación con `Actividad`/`Tarea`
  de `axon` — queda explícitamente diferida, ver §5.

## 5. Pendiente (diferido a propósito, no resuelto en este ADR)

El usuario quiere: un kanban para "seguimiento de avance de tableros"
sobre los hitos/tareas de fabricación (`TableroHito`), y quiere evaluar
cómo esto se integra con el modelo `Proyecto -> Actividad -> Tarea` que
ya existe en `axon` (cuyos últimos cambios — Kanban/Gantt propios — recién
se mergearon a `main` de ese repo). La pregunta de fondo que dejó
planteada: en `axon` la jerarquía es plana bajo Proyecto; acá haría falta
`Proyecto -> Tablero (varios) -> Actividades/Tareas que pueden diferir
entre tableros` — un nivel más de anidamiento, y las actividades/tareas
no necesariamente son un catálogo compartido entre tableros. Se retoma
cuando el usuario revise el código real de `axon` ya mergeado — no
corresponde diseñar esto a ciegas.

## Siguiente paso

Correr `/revisor` sobre este diff antes de abrir PR. Después, revisar
juntos el `axon` recién mergeado para la pregunta de §5.
