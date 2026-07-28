# 0004 — Kanban de Observaciones con relaticle/flowforge (PR1 del ADR 0003)

> Estado: implementado. Primer PR del plan de 8 definido en
> [0003-rediseno-ux-seguimiento-terreno.md](0003-rediseno-ux-seguimiento-terreno.md) §6.

## Contexto

El ADR 0003 definió reemplazar el listado plano de `Observacion` por un
tablero kanban agrupado por `EstadoObservacion`, usando `relaticle/flowforge`
(decisión tomada por el usuario con el riesgo conocido de que `axon` instaló
y luego removió el mismo paquete sin dejar registro del motivo — ver ADR
0003 §4.1 y §7).

## Decisión

- **Paquete**: `relaticle/flowforge ^4.0` (v4.0.14 al momento de instalar;
  no existe todavía una v5.x del paquete pese a que su documentación hable
  de "Filament ^5.0" — esa cifra es la versión de Filament que requiere,
  no la suya propia).
- **Posición**: columna `posicion` en `observaciones`, tipo
  `decimal(20,10) nullable` vía el macro `flowforgePositionColumn()` que
  registra el propio paquete — no es un `integer` como se documentó
  originalmente en el ADR 0003 (dato corregido al leer el código fuente
  instalado, no solo la documentación web). Índice único
  `(estado_observacion_id, posicion)`, requerido por Flowforge para
  concurrencia segura entre usuarios moviendo cards a la vez.
- **Página**: `ObservacionesBoard extends Relaticle\Flowforge\BoardResourcePage`,
  registrada como página adicional de `ObservacionResource` (ruta
  `observacions/board`), no como reemplazo del listado — el ADR 0003 no
  pide eliminar el índice actual todavía (eso es una decisión de UX para
  una iteración posterior), y mantenerlo evita romper
  `FilamentResourcesSmokeTest`/`FilamentEditPagesSmokeTest` antes de tiempo
  (eso es explícitamente el alcance de PR8, no de este PR). Se agregó un
  botón "Ver Kanban" en el header del listado para llegar a la nueva vista.
- **Columnas del board**: construidas dinámicamente desde
  `EstadoObservacion::query()->orderBy('orden')->get()` — nunca
  hardcodeadas, siguiendo el principio del CLAUDE.md de catálogos en BD.
- **Validación de transición**: **no se duplicó** la lógica de
  `TransicionEstadoGuard`. `Observacion` ya tiene un `ObservacionObserver`
  cuyo `saving()` valida cualquier cambio de `estado_observacion_id` contra
  la máquina de estados, sin importar la vía de escritura (form, seeder,
  tinker). Como `Flowforge::moveCard()` internamente hace
  `$card->update([...])`, ese observer se dispara igual para un movimiento
  de kanban — mover una card a un estado no alcanzable lanza
  `TransicionEstadoInvalidaException` dentro de la misma transacción de
  Flowforge (que hace rollback), sin persistir nada.
- **Autorización**: `Flowforge::moveCard()` no gatea nada por su cuenta —
  cualquiera que vea el board podría, en teoría, mover cualquier card. Se
  sobrescribió `moveCard()` en `ObservacionesBoard` para exigir
  `Gate::authorize('observacion.cerrar')` antes de delegar en
  `parent::moveCard()` — la misma ability que ya gobierna la acción
  "Cerrar" existente en `ObservacionActions`, porque mover una card
  equivale semánticamente a cambiar su estado. El acceso a **ver** el
  board ya queda cubierto gratis por `BoardResourcePage` (hereda
  `CanAuthorizeResourceAccess`, que llama a
  `ObservacionResource::canAccess()` → `ObservacionPolicy::viewAny()` →
  `Gate::allows('tablero.ver')`) — verificado leyendo el código fuente de
  Filament, no asumido.
- **Acciones de card**: se reutilizó `ObservacionActions::todas()` (la
  acción "Cerrar" existente, con su propio form y sus propias opciones de
  destino ya filtradas por `TransicionEstadoGuard`) en vez de duplicar
  lógica de cierre dentro del board.

## Alternativas descartadas

| Alternativa | Por qué se descartó |
|---|---|
| Duplicar la validación de `TransicionEstadoGuard` dentro de `moveCard()` | Ya existe en `ObservacionObserver`, que se dispara automáticamente porque Flowforge usa `Eloquent::update()` internamente — duplicarla habría violado DRY sin ganar nada |
| Reemplazar el índice (`ListObservacions`) por el kanban | Fuera de alcance de este PR según el ADR 0003; se difiere para no romper smoke tests antes de PR8 |
| `columna posicion` como `integer` | Corregido a `decimal(20,10)` tras leer el código fuente instalado de Flowforge — su servicio `DecimalPosition` usa BCMath sobre ese tipo específicamente |

## Consecuencias

- Nueva dependencia de composer: `relaticle/flowforge ^4.0`.
- Nuevos assets publicados en `public/js/relaticle/flowforge/` (mismo
  patrón que los assets de Filament, ya versionados en este repo).
- Riesgo pendiente de validar (heredado del ADR 0003 §7): soporte táctil
  del drag-and-drop en tablet — no se probó en dispositivo real todavía,
  solo el flujo servidor vía tests.
- Tests nuevos: `Modules/Inspeccion/tests/Feature/ObservacionKanbanTest.php`
  (5 casos: acceso con/sin `tablero.ver`, mover a estado válido persiste,
  mover a estado inválido se rechaza server-side, mover sin
  `observacion.cerrar` no persiste). Suite completa: 71/71 en verde, Pint
  limpio.

## Siguiente paso

Correr `/revisor` sobre este diff antes de abrir el PR. Luego, PR2 del ADR
0003: mismo patrón de kanban para `ControlCambio`.
