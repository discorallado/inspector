# SESSION.md — Estado de sesión de trabajo

> Este archivo lo mantiene Claude Code. Se actualiza al final de cada sesión de trabajo
> y se lee al inicio de la siguiente, para no depender del historial de chat ni de
> copiar/pegar resúmenes a mano. (Convención tomada del repo hermano `axon`.)

---

## Última actualización
2026-07-29

## Módulo / feature en curso
**PR4 y PR5 del ADR 0009 implementados, revisados y con QA** (modelo de
datos `Actividad`/`Tarea`/`TareaLink` + guard generalizado, ADR 0010;
comando de migración de los 234 hitos existentes, ADR 0011). PRs
retroactivos de registro abiertos en GitHub:
[#5](https://github.com/discorallado/inspector/pull/5) (PR4),
[#6](https://github.com/discorallado/inspector/pull/6) (PR5) — igual que
[#3](https://github.com/discorallado/inspector/pull/3)/[#4](https://github.com/discorallado/inspector/pull/4)
para PR3/ADR 0008, no se mergean contra `main` (ya está ahí), son solo
para que GitHub muestre el diff real con el detalle de revisión.
Próximo paso: `/ingeniero` en PR6 (`ActividadesRelationManager` +
`CalculadorAvanceTablero` adaptado). Ver detalle completo más abajo.

Rediseño UX de `Inspeccion`: de CRUD administrativo a herramienta de
seguimiento en terreno. **El usuario pidió revertir el kanban de
Observaciones/Control de Cambios a tabla + select** (ADR 0008) tras
probar PR1/PR2 — el kanban como concepto no se descarta, queda para el
seguimiento de hitos/tareas de `Tablero`, diferido hasta definir la
integración con `axon` (ver abajo, sección grande nueva). PR3 (theme
custom, ADR 0007) sigue vigente sin cambios.

## Estado actual (2026-07-29) — Retroactivos PR4/PR5 en GitHub + /qa del gap de idempotencia

El usuario pidió retomar los pendientes de PR4/PR5 con `/revisor`, aplicar
`/qa`, y seguir con los PRs siguientes repitiendo el ciclo — se ejecutó la
parte de registro/QA; PR6-PR9 quedan para la próxima sesión (ver "Próximo
paso concreto").

- **PRs retroactivos abiertos**: [#5](https://github.com/discorallado/inspector/pull/5)
  (PR4: rango `f9b0644..421dd02`, incluye el feat + las 2 correcciones de
  `/revisor` + el commit de `/qa`) y [#6](https://github.com/discorallado/inspector/pull/6)
  (PR5: rango `421dd02..3d6bd80`, feat + las 2 correcciones de `/revisor`).
  Mismo trámite que PR3/ADR 0008: ramas `archive/*` base+head, no se
  mergean contra `main`.
- **`/qa` sobre el estado combinado de PR4+PR5** encontró 1 gap real no
  documentado antes: `TableroHitosRelationManager` sigue activo y
  editable (no es de solo lectura todavía) — si alguien edita `item` de
  un `TableroHito` entre dos corridas de
  `inspeccion:migrar-hitos-a-tareas`, la clave natural de `Tarea`
  (`actividad_id`+`code`, con `code` derivado del `item`) no encuentra la
  `Tarea` vieja y crea una nueva, dejando la anterior huérfana con datos
  desactualizados. **Documentado con test, no corregido** — la solución
  correcta (¿matchear por `hito_id` en vez de por `code`? ¿congelar
  `TableroHito` a solo-lectura ya, antes de PR9?) es una decisión de
  `/arquitecto`, no algo que QA deba resolver unilateralmente.
- `docs/0002-seguimiento-inspeccion-tableros.md` (el requerimiento
  original) actualizado con una nota de superación parcial: el
  seguimiento de avance ponderado de su §3.2/§4 fue reemplazado por
  `Actividad`/`Tarea` (ADR 0009-0011) — el resto del documento
  (Observaciones, Control de Cambios, Checklist) sigue vigente tal cual.
- Suite completa: **119 passed, 1 risky preexistente** (sin fallas), Pint
  limpio.

## Estado histórico (2026-07-29) — PR5: comando de migración de datos (ADR 0011)

Sesión de `/ingeniero`, continuación directa de PR4. Dos decisiones que
el ADR 0009 §2.5 no cerró, presentadas al usuario antes de implementar:
`EstadoAvance.codigo = 'na'` (usado en datos reales, ej. item `2.4`) no
tiene equivalente directo en `TaskStatus` (5 casos, ninguno "N/A") —
mapeado a `Bloqueada` (semántica más cercana, se pierde el matiz
"excluido del cálculo" hasta que `CalculadorAvanceTablero` en PR6 lo
resuelva); y `Tarea.code` se genera como `"{tablero.tag}-{hito.item}"`
(único en la práctica para los 234 hitos reales, sin agregar un
`unique()` de BD — ese gap señalado por `/qa` sigue como decisión
aparte).

- **`MigrarHitosATareasCommand`** (`inspeccion:migrar-hitos-a-tareas`):
  agrupa `TableroHito` por `GrupoHito` → una `Actividad` por grupo usado
  en el `Tablero`; cada `TableroHito` → una `Tarea`, preservando `peso`/
  `real_inicio`/`real_fin`. `Tarea::withoutEvents()` al crear (carga
  histórica, no transición de usuario — mismo criterio que
  `SeguimientoIntegracionTablerosSeeder` ya usa para `TableroHito`), todo
  en una `DB::transaction()`. **Idempotente** vía `updateOrCreate()` con
  clave natural — correrlo dos veces no duplica nada.
- Corrido contra los 234 hitos reales: 48 `Actividad` + 234 `Tarea`
  creadas la primera vez, 0 creadas / 234 actualizadas la segunda.
  `TableroHito`/`GrupoHito`/`EstadoAvance` quedan intactos (deprecados,
  no borrados, hasta PR9).
- 6 tests nuevos. Suite completa: **113 passed, 1 risky preexistente**
  (sin fallas), Pint limpio. Detalle completo en
  [ADR 0011](Modules/Inspeccion/docs/adr/0011-pr5-migracion-datos-hitos-a-tareas.md).

## Estado histórico (2026-07-29) — PR4: modelo de datos Actividad/Tarea (ADR 0010)

Sesión de `/ingeniero`. Hallazgo importante antes de escribir código: la
sesión anterior asumió `axon` en `/home/ubuntu/axon` (no existe en este
entorno) — el repo real está en `/home/sebas/axon`. Se portaron tipos de
columna, nombres de enum (`TaskStatus`: Pendiente/EnProgreso/EnRevision/
Completada/Bloqueada; `TaskPriority`: Baja/Media/Alta/Critica, ambos ya
en español en axon) y relaciones desde el código fuente real, no de
memoria.

- **Migraciones**: `transiciones_estado_permitidas` gana columnas de
  código (`estado_origen_codigo`/`estado_destino_codigo`) en paralelo a
  las de id — no se reescribieron las 3 filas de catálogo existentes a
  texto, menor blast radius. `actividades`/`tareas`/`tarea_links` con
  columnas exactas del ADR 0009 §2.2. **Id autoincremental, no ULID como
  axon** — consistencia con el resto de Inspeccion, el rename de PK al
  integrar a axon es mecánico. `actividades.tablero_id` con
  `cascadeOnDelete()` (como `tablero_hitos`, la entidad que reemplaza),
  no `restrictOnDelete()` como las tablas de auditoría de calidad.
  Validado con `migrate` + `migrate:rollback --step=4` contra MariaDB
  real (ddev).
- **Guard generalizado**: métodos paralelos `puedeTransicionarPorCodigo`/
  `validarPorCodigo`/`transicionesValidasDesdePorCodigo`, no tipos unión
  sobre los existentes — `transicionesValidasDesde()` no recibe destino,
  así que con origen `null` no hay forma de inferir catálogo-vs-código
  por tipo. Cero riesgo para las 3 llamadas ya probadas basadas en id.
  Mismo patrón cache-por-request-con-clone que ya se corrigió en
  `/revisor` sobre el N+1 anterior, aplicado desde el arranque acá.
- **Matriz de transiciones de `Tarea.status`**: axon no la valida
  (cualquier salto vale); el ADR 0009 dejó esto sin definir. Se le
  presentó al usuario antes de sembrarla — aprobó la matriz con
  reapertura: `null→Pendiente→EnProgreso→EnRevision→Completada`, rebote
  `EnRevision→EnProgreso`, y `Bloqueada` alcanzable desde
  `Pendiente`/`EnProgreso` y viceversa. Sin salto directo a `Completada`
  ni reapertura de `Completada`.
- **`TareaObserver`**: mismo patrón que `ObservacionObserver`/
  `ControlCambioObserver`, valida `status` dirty en `saving()`.
- **Diferido a propósito**: `predecesoras()`/`sucesoras()` de `Tarea`
  (axon distingue Tarea/Actividad en `tarea_links` vía prefijo de ULID;
  acá con id autoincremental por tabla hay colisión real de ids entre
  ambas — se resuelve en PR8 cuando el Gantt lo necesite de verdad,
  inventar un discriminador ahora sin uso sería diseñar a ciegas).
  Policies/abilities de la matriz de permisos del ADR 0009 §4: no hay
  recurso Filament todavía que las invoque, llegan en PR6.
- **`/revisor` corrido sobre el diff, 2 hallazgos reales corregidos**:
  (1) el `down()` de la migración de columnas de código no era
  reversible en la práctica una vez que el seeder corría (`ALTER` a
  `NOT NULL` rechazado por las 8 filas `tarea_status` con
  `estado_destino_id NULL`) — confirmado con rollback real contra
  MariaDB después de sembrar datos, corregido borrando esas filas antes
  del `ALTER`; (2) `actividades`/`tareas` tenían `cascadeOnDelete()` sin
  `SoftDeletes` (igual que `tablero_hitos`, pero sin la protección que sí
  tiene el historial de calidad) — corregido con `SoftDeletes` +
  `restrictOnDelete()`, mismo patrón que `Observacion`/`ControlCambio`.
- 20 tests (guard *PorCodigo, modelos/relaciones, `TareaObserver`,
  rollback de migración, borrado lógico/`restrictOnDelete`). Suite
  completa: **103 passed, 1 risky preexistente** (sin fallas), Pint
  limpio. Detalle completo en
  [ADR 0010](Modules/Inspeccion/docs/adr/0010-pr4-actividad-tarea-modelo-de-datos.md).

## Estado histórico (2026-07-31) — ADR 0009: integración Actividad/Tarea desde axon

Sesión de `/arquitecto` cerrando la pregunta que quedó diferida en el ADR
0008. Revisé el código **real** de `axon` en `/home/ubuntu/axon` (no
supuestos): jerarquía `Organization -> Client -> Project -> Activity ->
Task` con ULIDs, multi-tenancy real (`HasOrganizationScope`), Kanban
Livewire+SortableJS (relaticle/flowforge se instaló y se removió ahí
también — mismo camino que recorrimos acá), Gantt migrado de frappe-gantt
a **DHTMLX Gantt** (dependencias vía `TaskLink`, zoom, dark mode).

El usuario eligió la integración profunda (Opción A de 3 presentadas):
portar (copiar y adaptar, no importar en runtime — son apps Laravel
separadas) `Activity`/`Task` a Inspeccion como `Actividad`/`Tarea`,
reemplazando el `TableroHito` plano actual.

**Hallazgo importante**: el ADR-0010 de axon rechazó DHTMLX explícitamente
("comercial, viola la regla open-source") pero el código real lo usa
igual, sin ADR que documente el cambio. Investigué: desde DHTMLX Gantt
v10 la edición Community es MIT (antes GPL) — no es necesariamente una
violación, pero el CDN que usa axon (`edge`, sin fijar versión/edición)
podría estar sirviendo un build de evaluación PRO con marca de agua. El
usuario decidió portarlo igual y resolver esto como deuda aparte, no
bloqueante para el diseño.

**4 decisiones cerradas con el usuario** (ver ADR 0009 para el detalle
completo):
- Nombres en español (`Actividad`/`Tarea`, no `Activity`/`Task`).
- `Actividad` lleva solo `tablero_id` (no `proyecto_id` duplicado, se
  deriva vía `tablero->proyecto_id`).
- `TransicionEstadoGuard` se generaliza para aceptar códigos string
  (además de IDs de catálogo) — `Tarea.status` es un enum, no una tabla,
  pero sigue validado por la misma máquina de estados ya probada.
- Riesgo de licencia DHTMLX: se acepta y se porta igual, se resuelve
  después.

`Tarea` gana dos columnas que axon no tiene hoy: `peso` (para que
`CalculadorAvanceTablero` siga funcionando) y `real_inicio`/`real_fin`
(seguimiento planificado-vs-real que axon no cubre). Ambas nullable, no
rompen compatibilidad con un futuro merge.

Los 234 hitos ya importados se migran con un comando de datos (no se
borra `TableroHito`/`GrupoHito`/`EstadoAvance` de entrada — quedan
deprecados hasta validar en uso real, se sacan en un PR de limpieza
aparte). El PR4 original del ADR 0006 (autonumeración de
`TableroHito.item`) queda **cancelado** — la entidad se depreca.

Plan: **PR4** (migraciones + modelos + guard generalizado) -> **PR5**
(migración de datos) -> **PR6** (ActividadesRelationManager +
CalculadorAvanceTablero adaptado) -> **PR7** (Kanban) -> **PR8** (Gantt)
-> **PR9** (cleanup de tablas viejas). Nada implementado todavía.

## Estado histórico (2026-07-31) — ADR 0008: kanban → tabla + select, y pregunta de integración con axon

**Reversión de PR1/PR2** (el usuario probó el resultado real y pidió
volver a algo "más estático"):
- Se eliminaron `ObservacionesBoard.php` y `ControlCambiosBoard.php` (páginas,
  rutas `board`, botones "Ver Kanban"). `relaticle/flowforge` sigue
  instalado — el usuario quiere un kanban para hitos/tareas de tablero
  más adelante, no tiene sentido desinstalar y reinstalar.
- `ObservacionsTable` y `ControlCambiosTable` ganan `SelectColumn` para
  cambiar el estado inline. Las acciones de `ControlCambio`
  (aprobar/rechazar/implementar) se agruparon en un `ActionGroup`, más
  una acción nueva **`desimplementar`** (revierte Implementado ->
  Aprobado) — nueva transición sembrada `[implementado, aprobado]`.
- **Mismo hallazgo de seguridad que ya encontró `/revisor` en PR2, esta
  vez corregido desde el arranque**: confirmado en el código de Filament
  que `SelectColumn` no respeta Policies — `disabled()` bloquea el save,
  pero la validación real es que el valor recibido se compara contra
  `options()`. Para `ControlCambio`, las opciones se filtran por la
  ability específica de cada destino (no un `Gate::any()` genérico), o
  un rol con solo `control_cambio.proponer` habría podido
  aprobar/rechazar/implementar por el select aunque los botones se lo
  nieguen.
- Nueva migración (no se editan las de PR1/PR2 ya corridas) que dropea
  `posicion` de ambas tablas. **Detalle no trivial en MariaDB real**: el
  único índice que cubría `estado_observacion_id`/`estado_cambio_id`
  como FK era justo el unique que se estaba borrando (error 1553) — hubo
  que agregar un índice simple de reemplazo antes. Verificado con
  `migrate` + `migrate:rollback` reales, no solo que compile.
- 10 tests nuevos (`ObservacionEstadoSelectTest`, `ControlCambioEstadoSelectTest`),
  reemplazando los ~28 tests de los kanbans eliminados. Suite completa:
  **80 passed, 1 risky** (preexistente, sin fallas), Pint limpio.

**Pregunta grande, diferida a propósito — no resuelta todavía**: el
usuario quiere integrar el seguimiento de avance de `Tablero` con el
modelo `Proyecto -> Actividad -> Tarea` de `axon`. Sus últimos cambios en
`axon` (locales, no pusheados) **ya se mergearon a GitHub** durante esta
sesión — el bloqueo original para diseñar esto ya no existe. Pregunta de
fondo sin resolver: en `axon` la jerarquía es plana bajo Proyecto; acá
haría falta `Proyecto -> Tablero (varios) -> Actividades/Tareas que
pueden diferir entre tableros` — no se diseñó a ciegas, se retoma
revisando el código real de `axon` ya mergeado.
— ver "Próximo paso concreto" para PR4.

## Estado actual (2026-07-30) — PR3: Theme custom de Filament

- `resources/css/filament/admin/theme.css` nuevo: importa el theme base
  de Filament + `@source` hacia `app/Filament`, `Modules/Inspeccion/app/Filament`,
  `Modules/Inspeccion/resources/views` y `vendor/relaticle/flowforge/resources/views`.
  Registrado con `->viteTheme(...)` en `AdminPanelProvider` + agregado al
  `input` de `vite.config.js`.
- **Bug propio durante la implementación**: el primer build falló
  (`CssSyntaxError: Invalid custom property`) porque un comentario CSS
  mío tenía la secuencia `*/` dentro del texto (`--primary-*/--success-*`),
  cerrando el comentario antes de tiempo. Se aisló incrementalmente
  (build mínimo, agregar `@source` de a uno) antes de asumir que era un
  problema de configuración de Tailwind/Filament — no lo era.
- Paleta de color sigue diferida (`Color::Amber` sin cambios) — a
  propósito, ver ADR 0006 §3.2.
- Verificado que el HTML real que devuelve `/admin` y el board de
  Observaciones referencian el asset compilado (`build/assets/theme-*.css`),
  no solo que el build compile sin error. 2 tests nuevos. Suite completa:
  **99 passed, 3 risky** (preexistentes, sin fallas), Pint limpio.
- **Pendiente real, no verificable desde acá**: confirmar visualmente en
  un navegador que el kanban ahora se ve con estilos (no tengo
  herramienta de screenshot en este entorno).

## Estado actual (2026-07-30) — ADR 0006: theme custom + UI bespoke + autonumeración

El usuario probó el panel y pidió un replanteo de UI/UX: el kanban se ve
"solo texto" (bug real, diagnosticado) y el resto es CRUD Filament por
defecto — pidió algo "increíble", mobile-first, sin depender de relation
managers para todo. Sesión de `/arquitecto`, cerrada en ADR
[`0006-theme-custom-y-ui-bespoke.md`](Modules/Inspeccion/docs/adr/0006-theme-custom-y-ui-bespoke.md),
que amplía el plan de PRs del ADR 0003 (no reabre PR1/PR2):

- **Causa del kanban sin estilos**: `AdminPanelProvider` nunca configuró
  `->viteTheme(...)`. Filament usa su CSS precompilado propio (solo
  clases de sus componentes core); las vistas de `relaticle/flowforge`
  usan clases Tailwind que nunca se compilaron en ningún stylesheet.
  Afecta a cualquier vista custom futura, no solo al kanban.
- **PR3 (nuevo, prerequisito)**: theme custom Filament + Tailwind 4
  (`@source` escaneando el módulo + flowforge), con tokens de diseño CSS
  para la paleta de color (el usuario prefirió definir los colores más
  adelante, no bloquear con eso).
- **PR4 (nuevo)**: autonumeración en 10 campos de "orden" manual
  (9 catálogos simples + `ChecklistTemplateItems.orden`) vía
  autocálculo + `->reorderable()`, y backfill de `TableroHito.item`
  (recalcula los 234 existentes con `{grupo.orden}.{posición}` — el
  usuario eligió recalcular todo, no solo los nuevos).
- **Checklist táctil (PR8, redefinido)**: lista scrolleable con 3
  botones grandes por ítem (Cumple/No Cumple/N.A.), sin modal —
  el usuario eligió esto sobre un wizard tipo app porque permite saltar
  entre ítems libremente.
- **Lenguaje visual**: "bespoke marcado" (elegido por el usuario sobre
  un refinamiento liviano) para Centro de Seguimiento, Vista de Tablero
  y checklist táctil — cards con elevación, progress rings, touch
  targets grandes, color de estado consistente en toda la app.
- Plan de PRs completo (10 en total) en el ADR 0006 §4.

## Estado histórico (2026-07-30) — PR2: Kanban de Control de Cambios

Mismo patrón que PR1 (ver ADR 0005 para el detalle completo, solo difiere
de PR1 en lo siguiente):
- Columnas = catálogo `EstadoCambio` (4 estados, no 3).
- `moveCard()` gateado con `Gate::any(['control_cambio.proponer',
  'control_cambio.decidir', 'control_cambio.implementar'])` — misma
  condición que ya usa `ControlCambioPolicy::update()` — porque acá hay 3
  abilities distintas según la transición, no una sola como en PR1.
- Acciones de card reutilizadas: `ControlCambioActions::todas()`
  (aprobar/rechazar/implementar).
- `ControlCambioObserver::creating()` con la posición base **se agregó
  desde el arranque** (en PR1 este hook salió recién al pasar `/revisor`
  — acá no hubo que esperar a encontrarlo de nuevo).
- 12 tests nuevos (`ControlCambioKanbanTest`). Suite completa: **90/90 en
  verde** (2 risky sin fallas), Pint limpio.
- Pendiente: correr `/revisor` y `/qa` sobre este PR (igual que se hizo
  con PR1) antes de abrir su PR en GitHub.

## Estado histórico (2026-07-29) — PR1: Kanban de Observaciones

- `relaticle/flowforge ^4.0` instalado. Columna `posicion`
  (`decimal(20,10)`, no `integer` como decía el borrador del ADR 0003 —
  corregido al leer el código fuente del paquete) + índice único
  `(estado_observacion_id, posicion)` en `observaciones`.
- Nueva página `ObservacionesBoard` (`BoardResourcePage`), ruta adicional
  de `ObservacionResource` (`/board`), **no reemplaza** el listado actual
  todavía — eso es una decisión de UX diferida, no de este PR. Botón
  "Ver Kanban" agregado al header del listado.
- Columnas del board construidas dinámicamente desde el catálogo
  `EstadoObservacion` (nunca hardcodeadas).
- La validación de transición de estado **no se duplicó**: ya vive en
  `ObservacionObserver::saving()`, y se dispara sola porque Flowforge usa
  `Eloquent::update()` internamente para mover una card.
- `moveCard()` sobrescrito solo para agregar
  `Gate::authorize('observacion.cerrar')` — Flowforge no gatea el
  movimiento de cards por su cuenta. Ver acceso al board ya viene gratis
  de `BoardResourcePage` (`ObservacionPolicy::viewAny` → `tablero.ver`).
- Acción "Cerrar" existente (`ObservacionActions`) reutilizada como
  acción de card, sin duplicar lógica.
- **`/revisor` corrido sobre el diff** (ver ADR 0004): sin hallazgos
  críticos. Se verificó con reproducciones reales (no solo lectura de
  código) que `ObservacionActions::cerrar()` (que usa `->visible()` con
  un Gate, no `->authorize()`) sí bloquea la ejecución server-side —
  Filament liga `isDisabled()` a `isHidden()`, así que no es una
  vulnerabilidad pese a no ser el patrón más idiomático. Se cerró un
  hallazgo real: `ObservacionObserver::creating()` ahora asigna una
  posición base (`DecimalPosition::after()`/`forEmptyColumn()`) a toda
  Observacion nueva — antes quedaban con `posicion = NULL` indefinidamente
  si no se creaban arrastrando una card en el board.
- **`/qa` corrido en modo completo**: sin bugs. 5 casos nuevos (mover a
  Informativa, acción "Cerrar" end-to-end desde una card del board con
  fill+call real, tecnico bloqueado de mountear "Cerrar" sobre una card
  puntual, Observacion sin `tablero_id` no rompe el render, HTML del
  board incluye el asset de Flowforge + las 3 columnas del catálogo).
  `ObservacionKanbanTest` queda con 14 tests en total. Suite completa del
  módulo: **79 passed + 1 risky** (no es una falla, solo una aserción sin
  `expect()` explícito en la cadena), Pint limpio.
- **Limitación explícita**: no hay herramienta de navegador/screenshot en
  este entorno — no se verificó visualmente el drag-and-drop real, ni
  errores de consola JS, ni el click-through del modal "Cerrar". Todo lo
  de arriba se probó a nivel HTTP/Livewire (lo más cercano posible sin
  navegador). **Pendiente real**: probar en un navegador/tablet real
  antes de considerar el drag-and-drop 100% validado (riesgo heredado
  del ADR 0003 §7).
- **PR1 cerrado.** Listo para abrir PR en GitHub cuando se decida.

## Estado histórico (sesión 2026-07-28)

### Completado ✅ (esta sesión, 2026-07-28)

**Reestructuración de navegación Filament** (de 17 ítems de menú a 4):
- `Tableros` queda como recurso simple top-level; `Proyecto` (stub) perdió
  su ítem de menú propio y ahora se crea inline desde el Select de
  Proyecto (`createOptionForm`) en los formularios de Tablero/Visita.
- Cluster **Inspección de Calidad**: `Observaciones` como página principal
  (control QA/QC transversal), `Visitas de Inspección` como secundaria,
  `ChecklistEjecucion` sin ítem propio (se llega por link desde la Visita).
- `Control de Cambios` sigue top-level.
- Cluster **Configuración** (solo `super_admin`): los 9 catálogos +
  checklist maestro + transiciones de estado, agrupados por dominio.
  `CatalogoPolicy::viewAny/view` se endureció a requerir
  `catalogo.gestionar` (antes era público de solo-lectura).

**Corrección de un bug bloqueante**: `SeguimientoIntegracionTablerosSeeder`
creaba hitos históricos ya en `Completado`/`En proceso` sin pasar por la
transición `null → Pendiente`, y `TableroHitoObserver` lo rechazaba —
`migrate:fresh --seed` no corría limpio en ningún ambiente. Fix: el import
usa `TableroHito::withoutEvents()` (una carga histórica no es una
transición de negocio hecha por un usuario).

**Dos rondas de `/revisor`, con hallazgos reales corregidos** (no solo
teóricos — varios se verificaron con tests que reproducen la falla antes
del fix):
- **Cascada de borrado física** (crítico): `Proyecto`/`Tablero` cascadeaban
  el borrado físico de todo el historial de calidad. Fix: `SoftDeletes` en
  `VisitaInspeccion`, `Observacion`, `ControlCambio`, `ChecklistEjecucion`
  (con `TrashedFilter`/Restore/ForceDelete en sus tablas Filament,
  `forceDelete` gateado a `super_admin` vía nueva ability
  `auditoria.purgar`), y las FK desde Tablero/Proyecto hacia esas tablas
  pasaron de `cascadeOnDelete()` a `restrictOnDelete()` — no se puede
  borrar un Tablero/Proyecto mientras tenga historial, ni soft-deleted.
- **Autorización de Bulk Actions abierta a cualquiera** (crítico,
  confirmado con exploit real en test): ninguna Policy del módulo definía
  `deleteAny()`/`restoreAny()`/`forceDeleteAny()`. Filament autoriza
  `DeleteBulkAction`/`RestoreBulkAction`/`ForceDeleteBulkAction` contra esas
  abilities `*Any()`, y si el método no existe en la policy, **falla
  abierto (permite)** en vez de denegar. Un usuario con rol `tecnico` (sin
  ningún permiso configurado) pudo bulk-borrar una Observacion sin
  restricción. Fix: se agregaron `deleteAny()` a las 9 policies del módulo,
  y `restoreAny()`/`forceDeleteAny()` a las 4 históricas.
- Acciones de negocio (`cerrar`, `aprobar`, `rechazar`, `implementar`) y
  `EditAction` quedaban activas sobre registros ya soft-deleted — ahora
  ocultas vía `AccionesBorradoLogico::esTrashed()`.
- 3 RelationManagers (`ControlCambiosRelationManager` bajo Tablero;
  `ObservacionesRelationManager` y `ChecklistEjecucionesRelationManager`
  bajo VisitaInspeccion) tenían su propio `DeleteAction` sin el wiring de
  papelera — un soft-delete ahí desaparecía sin forma de restaurarlo desde
  esa pantalla. Unificado con el helper compartido `AccionesBorradoLogico`.
- N+1 introducido y corregido el mismo día en
  `TransicionEstadoPermitidasTable` (resolución de nombres de estado).
- `TransicionEstadoPermitidaForm` pasó de `TextInput` numérico libre (IDs
  a mano) a Selects dependientes del catálogo elegido.
- Validación server-side de "severidad obligatoria" en
  `ObservacionObserver` (antes solo vivía en el `required()` reactivo del
  form de Filament — un seeder/import/tinker podía saltársela).

**Gap de asignación de `role` cerrado** (venía de la sesión anterior):
- `DatabaseSeeder` raíz ahora asigna `role => 'super_admin'` al `Test User`
  sembrado, para que un `migrate:fresh --seed` deje un admin usable.
- Nuevo `UserResource` (Filament, dentro del cluster Configuración, ability
  `usuario.gestionar` restringida a `super_admin`) para crear usuarios y
  asignarles rol desde la UI en vez de por tinker.

**Validado contra MariaDB real** (no solo SQLite en memoria, que es lo
único que corren los tests): `migrate:fresh --seed`, el `restrictOnDelete()`
nuevo, y el rollback de las 2 migraciones nuevas se probaron a mano contra
el servicio `db` (mariadb 11.8) de ddev — todo funcionó igual que en
SQLite. Este proyecto ya se cruzó antes con un bug específico de MariaDB
que SQLite no detectaba (índice de 70 caracteres > límite de 64), así que
esta verificación no es un formalismo.

**Entorno local**: `.env` (no versionado) ahora apunta a MariaDB real vía
ddev (`DB_CONNECTION=mariadb`, host `db`, `root` sin password) en vez de
SQLite — refleja el motor real del proyecto. La suite de tests no se ve
afectada: `phpunit.xml` fuerza `DB_CONNECTION=sqlite` + `:memory:` para
tests sin importar lo que diga `.env`.

Suite final: **66/66 tests en verde**, Pint limpio.

### Decisiones de diseño cerradas (vigentes)
- **Centro de Seguimiento**: página custom con cards por `Tablero`, reemplaza
  el dashboard genérico y `ListTableros` como landing.
- **Vista de Tablero**: página custom con Gantt interactivo (`frappe-gantt`
  vía CDN, mismo patrón que `axon`) sobre `TableroHito`, agrupado por
  `GrupoHito`; observaciones/cambios de ese tablero inline.
- **Kanban** (`Observacion` por `EstadoObservacion`, `ControlCambio` por
  `EstadoCambio`) con `relaticle/flowforge` — riesgo aceptado, fallback a
  Livewire+SortableJS (patrón real de `axon`) si aparecen problemas.
- **Checklist en modo terreno**: pantalla táctil de llenado (Cumple/No
  Cumple/N.A.), reemplaza el relation manager tabular solo para el flujo de
  ejecución en sitio.
- **Un solo panel Filament** — se descartó un panel `terreno` separado; las
  páginas de seguimiento usan un layout con clase CSS modificadora dentro
  del panel `admin` existente.
- **Catálogos de Configuración**: sin cambios, siguen CRUD clásico.
- **Modelo de datos**: `observaciones.posicion` ya implementado (PR1, ver
  arriba) como `decimal(20,10)`, no `integer` como decía este borrador —
  corregido tras leer el código fuente de Flowforge. `control_cambios.posicion`
  sigue pendiente (PR2), mismo tipo.
- **Plan de implementación**: 8 PRs secuenciales, cada uno con sus propios
  tests Pest (detalle completo en el ADR §6). **PR1 cerrado** (ver arriba,
  ADR 0004).

## Decisiones pendientes
Ninguna de arquitectura — el diseño quedó cerrado y documentado en el ADR.

**Antecedente, no bloqueante** (de la ronda de `/revisor` sobre el fix de N+1,
commits `76ab2e2`/`5a457b1`): `TransicionEstadoGuard` tiene una cache estática
por request (`$cacheTransicionesValidas`) sin invalidación. Segura hoy
(requests HTTP nuevos por proceso, sin Octane; tests resetean IDs solos por
`RefreshDatabase`). El usuario decidió no resolverlo ahora. `PR4` (ver
abajo) generalizó el guard con los métodos `*PorCodigo` y aplicó el mismo
patrón cache-con-clone desde el arranque — la cache nueva
(`$cacheTransicionesValidasPorCodigo`) hereda el mismo antecedente: sin
invalidación, seguro hoy, pero cualquier comando Artisan de vida larga
(como la migración de datos de `PR5`) que llame al guard mientras el
catálogo de transiciones cambia a mitad de camino podría ver resultados
viejos. Detalle completo en el comentario de la clase
(`Modules/Inspeccion/app/Services/TransicionEstadoGuard.php`).

## Próximo paso concreto
`/ingeniero` — **PR6 del ADR 0009**: `ActividadesRelationManager`
(reemplaza `TableroHitosRelationManager`) + `CalculadorAvanceTablero`
adaptado a sumar sobre `Tarea.peso` (ahí se resuelve el matiz "excluido
del cálculo" para `Bloqueada`/`na` que quedó pendiente en PR5, ver ADR
0011 §2). El usuario pidió repetir el ciclo `/ingeniero` → `/revisor` →
`/qa` para PR6, PR7 (Kanban), PR8 (Gantt DHTMLX) y PR9 (cleanup) hasta
completarlos todos — quedan trackeados como tareas pendientes (#12-#15
en el TaskList de la sesión que los creó). Antes de PR7 (Gantt/Kanban)
conviene resolver el gap de idempotencia del comando de migración
señalado arriba (¿`TableroHito` de solo lectura ya?), porque PR9 va a
depender de que los datos migrados sean confiables. Ver
[`0009-integracion-actividad-tarea-desde-axon.md`](Modules/Inspeccion/docs/adr/0009-integracion-actividad-tarea-desde-axon.md)
§8 para el plan completo (PR4-PR9),
[`0010-pr4-actividad-tarea-modelo-de-datos.md`](Modules/Inspeccion/docs/adr/0010-pr4-actividad-tarea-modelo-de-datos.md)
y
[`0011-pr5-migracion-datos-hitos-a-tareas.md`](Modules/Inspeccion/docs/adr/0011-pr5-migracion-datos-hitos-a-tareas.md)
para el detalle de lo implementado. La autonumeración de catálogos del
ADR 0006 (los 9 simples, sin `TableroHito.item` que quedó cancelado)
sigue pendiente pero sin relación con esto — se puede retomar en
cualquier momento, no depende de PR4-PR9.

---

## Historial de sesiones anteriores

<details>
<summary>2026-07-31 — Arquitectura: integración Actividad/Tarea portada desde axon (ADR 0009)</summary>

Sesión de `/arquitecto` resolviendo la pregunta diferida en el ADR 0008.
Revisado el código real de `axon` (no la copia vieja): jerarquía
`Organization -> Client -> Project -> Activity -> Task` con ULIDs,
multi-tenancy real, Kanban Livewire+SortableJS (mismo camino de
flowforge instalado-y-removido que recorrimos acá), Gantt migrado a
DHTMLX. Usuario eligió integración profunda: portar `Activity`/`Task`
como `Actividad`/`Tarea` en Inspeccion, reemplazando `TableroHito`.
Hallazgo: el ADR-0010 de axon rechazó DHTMLX por licencia pero el código
real lo usa igual sin ADR que lo documente — investigado, DHTMLX v10
Community es MIT, pero el CDN de axon no fija edición y podría estar
sirviendo el build de evaluación PRO; riesgo aceptado, no bloqueante.
4 decisiones cerradas: nombres en español, `Actividad` con solo
`tablero_id` (no `proyecto_id` duplicado), `TransicionEstadoGuard`
generalizado para códigos string, DHTMLX se porta igual. `Tarea` gana
`peso` y `real_inicio`/`real_fin` (extensiones sobre axon, nullable).
Los 234 hitos existentes se migran con un comando de datos; tablas
viejas quedan deprecadas, no borradas, hasta un PR de limpieza aparte.
Plan: PR4 (modelo de datos) -> PR5 (migración de datos) -> PR6
(relation manager + calculador) -> PR7 (kanban) -> PR8 (gantt) -> PR9
(cleanup). Nada implementado todavía — ADR 0009 documenta todo. El PR4
original del ADR 0006 (autonumeración de `TableroHito.item`) queda
cancelado.

</details>

<details>
<summary>2026-07-31 — Revierte kanban de Observaciones/Control de Cambios a tabla + select (ADR 0008)</summary>

El usuario probó PR1/PR2 y pidió volver a un cambio de estado "más
estático": se eliminaron los boards de Flowforge para ambas entidades
(el paquete queda instalado para un futuro kanban de hitos/tareas de
Tablero). `SelectColumn` inline reemplaza el cambio de estado; acciones
de Control de Cambios agrupadas en `ActionGroup` + nueva acción
`desimplementar`. Mismo hallazgo de autorización que ya encontró
`/revisor` en PR2 (`SelectColumn` no respeta Policies, hay que filtrar
`options()` por ability específica de cada destino, no un `Gate::any()`
genérico) — corregido desde el arranque esta vez. Nueva migración
dropea `posicion`; en MariaDB real hubo que agregar un índice de
reemplazo antes de poder borrar el índice compuesto (dependía de él una
FK). 10 tests nuevos, suite completa 80 passed / 1 risky, Pint limpio.
Pregunta grande diferida: integración con `Actividad`/`Tarea` de `axon`
para el kanban de hitos/tareas — el bloqueo (cambios de axon sin
pushear) se resolvió durante la sesión (ya se mergearon a GitHub), pero
el diseño en sí queda pendiente de revisar el código real.

</details>

<details>
<summary>2026-07-30 — PR3: Theme custom de Filament (ADR 0007)</summary>

Diagnóstico del ADR 0006 resuelto: `resources/css/filament/admin/theme.css`
nuevo (importa el theme base de Filament + `@source` hacia el módulo y
`vendor/relaticle/flowforge/resources/views`), registrado con
`->viteTheme(...)` en `AdminPanelProvider` y agregado al `input` de
`vite.config.js`. Bug propio en el camino: un comentario CSS con la
secuencia `*/` cerraba el comentario antes de tiempo y rompía el build;
se aisló incrementalmente antes de asumir que era un problema de
Tailwind/Filament. Paleta de color sigue diferida a propósito. 2 tests
nuevos verifican que el HTML real (no solo el build) referencia el CSS
compilado, tanto en `/admin` como en el kanban de Observaciones. Suite
completa 99 passed, 3 risky preexistentes, Pint limpio. Pendiente:
confirmar visualmente en navegador (sin herramienta de screenshot acá).

</details>

<details>
<summary>2026-07-30 — PR2: Kanban de Control de Cambios con relaticle/flowforge (ADR 0005)</summary>

Mismo patrón que PR1, sin nuevas dependencias. Migración `posicion`
(decimal 20,10) + índice único en `control_cambios`. Nueva página
`ControlCambiosBoard` (`BoardResourcePage`) con columnas dinámicas desde
`EstadoCambio` (4 estados) y card actions reutilizando
`ControlCambioActions` (aprobar/rechazar/implementar). `moveCard()`
gateado con `Gate::any(['control_cambio.proponer', 'control_cambio.decidir',
'control_cambio.implementar'])` — misma condición que
`ControlCambioPolicy::update()`, porque acá hay 3 abilities distintas
según la transición en vez de una sola. `ControlCambioObserver::creating()`
con la posición base se agregó desde el arranque (en PR1 salió recién al
pasar `/revisor`). 12 tests nuevos, suite completa 90/90 en verde (2 risky
sin fallas), Pint limpio. Pendiente: correr `/revisor` y `/qa` sobre este
PR antes de abrir su PR en GitHub.

</details>

<details>
<summary>2026-07-29 — PR1: Kanban de Observaciones con relaticle/flowforge (ADR 0004)</summary>

Instalado `relaticle/flowforge ^4.0`. Migración `posicion` (decimal 20,10,
no integer — corregido tras leer el código fuente del paquete instalado)
+ índice único en `observaciones`. Nueva página `ObservacionesBoard`
(`BoardResourcePage`) registrada como ruta adicional de `ObservacionResource`
(no reemplaza el listado todavía), con columnas dinámicas desde el catálogo
`EstadoObservacion` y card actions reutilizando `ObservacionActions`. La
validación de transición de estado no se duplicó — ya la dispara
`ObservacionObserver::saving()` porque Flowforge usa `Eloquent::update()`
internamente. Se sobrescribió `moveCard()` solo para agregar
`Gate::authorize('observacion.cerrar')` (Flowforge no gatea el movimiento
por su cuenta); ver el board ya viene gratis vía `ObservacionPolicy::viewAny`.
5 tests nuevos, suite completa 71/71 en verde, Pint limpio. Pendiente:
correr `/revisor` y validar drag-and-drop táctil en tablet real.

</details>

<details>
<summary>2026-07-28 — Reestructuración de navegación + 2 rondas de /revisor (bug de autorización crítico) + gap de usuarios</summary>

Reestructuración de navegación Filament de 17 ítems a 4 (clusters
`Inspección de Calidad` y `Configuración`, `Proyecto` sin ítem propio).
Fix de bug bloqueante en `SeguimientoIntegracionTablerosSeeder` (violaba
`TransicionEstadoGuard` al importar hitos ya avanzados). Dos rondas de
`/revisor`: SoftDeletes + `restrictOnDelete()` para frenar la cascada de
borrado físico del historial de calidad; y un hallazgo crítico de
autorización confirmado con exploit real — ninguna Policy definía
`deleteAny()`/`restoreAny()`/`forceDeleteAny()`, por lo que Filament fallaba
abierto y cualquier usuario autenticado podía bulk-borrar/restaurar/purgar
lo que fuera, sin importar su rol. Corregido en las 9 policies del módulo.
También: acciones de negocio activas sobre registros trashed, 3
RelationManagers sin wiring de papelera, N+1 en tabla de transiciones,
`TransicionEstadoPermitidaForm` con Selects en vez de IDs a mano, validación
server-side de severidad obligatoria. Cerrado el gap de asignación de
`role` (arrastrado de la sesión anterior): `DatabaseSeeder` deja un
`super_admin` sembrado y nuevo `UserResource` para gestionar usuarios/roles
desde la UI. Migraciones nuevas validadas a mano contra MariaDB real (ddev),
no solo SQLite. 66/66 tests, Pint limpio. El ADR 0003 (rediseño UX,
Kanban/Gantt/terreno) sigue sin implementación — no se tocó esta sesión.

</details>

<details>
<summary>2026-07-27 — Fixes de arranque, import de datos históricos y rediseño UX (`/arquitecto`)</summary>

Fix de índice MariaDB demasiado largo en migración de transiciones de estado.
Fix de locale (`en`→`es` en `.env`) que dejaba ver claves de traducción crudas
en vez de texto. Fix de `DatabaseSeeder` raíz que no llamaba al seeder del
módulo. Corrección de `GrupoHitoSeeder` (5 grupos inventados → 8 reales).
Nuevo `SeguimientoIntegracionTablerosSeeder` importando la planilla histórica
"Seguimiento Integracion Tableros (1).xlsx" (1 proyecto, 6 tableros, 234
hitos, 2 control de cambios) — avance calculado verificado contra la
planilla original. Sesión completa de `/arquitecto` rediseñando la UX de
CRUD administrativo a herramienta de seguimiento en terreno, con
verificación cruzada contra el estado real (GitHub) del repo `axon`,
cerrada en ADR 0003 y plan de 8 PRs. Gap identificado y no resuelto: no hay
mecanismo que asigne `role` a usuarios nuevos.

</details>
