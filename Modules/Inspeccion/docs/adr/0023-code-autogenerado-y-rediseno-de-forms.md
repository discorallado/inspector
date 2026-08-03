# 0023 — Code autogenerado + rediseño de modals y TableroForm

> Estado: implementado. Cierra 3 de los 6 puntos de la última tanda de
> feedback del usuario (items 1, 2 y 3 — los items 4, 5 y 6 quedan para
> sus propios `/arquitecto`, son cambios de arquitectura, no ajustes).

## Contexto

Feedback directo sobre lo construido en las últimas rondas: la UI de los
modals del árbol "da asco" (sin estructura visual), la numeración de
Tarea (`TP-1.1`, `TP-1.2`...) debía ser un TextInput libre que ahora pasa
a autocalcularse incluso al reordenar, y el `Section` de `EditTablero`
quedaba desbalanceado (una columna con 5 campos contra otra con 2).

## Decisión

### `code` de Tarea: de TextInput libre a autocalculado

Formato: `{tablero.tag}-{actividad.orden}.{tarea.orden}` (ej. `TP-1.1`),
vía `Tarea::generarCode()` (nuevo, única fuente de verdad del formato).
Se recalcula en cascada en cada punto donde `orden` cambia:

- `crearTareaAction()`/`insertarTareaAction()`: la Tarea nueva calcula su
  code al crearse.
- `insertarTareaAction()`: además de correr el `orden` de las Tareas
  siguientes (ya lo hacía), ahora también les recalcula el `code` —
  antes solo se corría el número internamente, el code visible quedaba
  desactualizado.
- `reordenarTareas()` (drag-and-drop): idem, el `code` se recalcula al
  mismo tiempo que `orden`/`actividad_id`.
- `reordenarActividades()` (drag-and-drop): como el code embebe
  `actividad.orden`, mover una Actividad obliga a recalcular el code de
  **todas** sus Tareas — `recalcularCodesDeActividad()` nuevo,
  `updateQuietly()` para no disparar `TareaObserver` (no se está tocando
  `status`).

`TareaForm` ya no tiene el campo `code` editable ni su validación
`unique()` — la unicidad ahora es garantizada por construcción (orden
secuencial dentro de cada Actividad no puede repetirse). Se muestra de
solo lectura (`disabled()->dehydrated(false)`) únicamente al editar (al
crear todavía no existe).

**No se migró retroactivamente el `code` de las 234 Tareas ya
existentes** — decisión deliberada, no una omisión: los datos históricos
tienen `orden = 0` en casi todos los casos (nunca se expuso como campo
editable hasta la ronda de reorder de esta sesión), así que recalcular
ahora mismo habría producido códigos peores (`TP-1.0` para todas) en vez
de mejores. Los codes existentes (`TP-1.1`, `TP-1.2`...) ya fueron
puestos a mano correctamente por el import histórico y se dejan como
están; de acá en adelante, cualquier Tarea tocada (creada, insertada,
reordenada) sí queda con code autocalculado y consistente.

### Modals del árbol: `Section` con icono y descripción

`ActividadForm` y `TareaForm` pasan de una lista plana de campos a
`Section`s temáticas (Filament 5: `->icon()`, `->description()`,
`->columns()`):

- **ActividadForm**: Datos generales · Orden y ponderación · Fechas.
- **TareaForm**: Datos generales (con `code` de solo lectura) · Estado y
  prioridad · Fechas · Dependencias (predecesoras).
- La acción "Agendar fechas desde tarea anterior" también pasa de un
  `Placeholder` suelto a una `Section` con la info de la tarea anterior
  como descripción.

Cada descripción explica el "por qué" del campo (ej. "el orden define su
posición en el árbol y el prefijo de las Tareas"), no solo repite el
nombre.

### `TableroForm`: de `Grid(2)` desbalanceado a Sections full-width

Antes: `Grid::make(2)` con una Section de 5 campos a la izquierda y otra
de 2 a la derecha, mismo ancho forzado para ambas. Ahora: ambas Sections
full-width, cada una reparte sus propios campos con `->columns(2)`
interno — Datos (proyecto ocupa la fila completa, tag/nombre/
fabricante/oc_contrato en 2 columnas) y Avance (2 columnas para sus 2
métricas, con `avance_global` destacado en negrita/tamaño grande).

## Qué se creó/cambió

- `Modules/Inspeccion/app/Models/Tarea.php` (`generarCode()`)
- `Modules/Inspeccion/app/Filament/Resources/Tableros/RelationManagers/ActividadesRelationManager.php`
  (cascada de code en crear/insertar/reordenar, Section en agendar fechas)
- `Modules/Inspeccion/app/Filament/Resources/Tableros/Schemas/TareaForm.php`,
  `ActividadForm.php`, `TableroForm.php` (rediseño con Sections)
- Tests: casos nuevos/reescritos en `ActividadesArbolRelationManagerTest.php`
  (código autogenerado, cascada en reorder e insertar)

## Alternativas descartadas

- **Solo el sufijo numérico autocalculado, prefijo libre**: descartado
  por el usuario — se autogenera completo.
- **Migrar retroactivamente los codes existentes**: descartado por el
  riesgo real explicado arriba (datos sin `orden` consistente producirían
  codes peores, no mejores, que los ya cargados a mano).

## Pendiente (no es parte de este ADR)

Los items 4 (navegación con Proyecto actual), 5 (tabla custom para
pesos) y 6 (rediseño UX de `TablerosTable` y otras) quedan para sus
propios `/arquitecto` — son decisiones de arquitectura, no ajustes.

## Siguiente paso

Correr `/revisor` sobre la cascada de `code` en particular — es la parte
con más superficie nueva (tres puntos de recálculo distintos que deben
mantenerse sincronizados).
