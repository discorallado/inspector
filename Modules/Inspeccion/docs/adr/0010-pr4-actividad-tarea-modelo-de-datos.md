# 0010 — PR4: modelo de datos Actividad/Tarea + guard generalizado

> Estado: implementado. Primer PR del plan de 6 definido en
> [0009-integracion-actividad-tarea-desde-axon.md](0009-integracion-actividad-tarea-desde-axon.md) §8.

## 1. Contexto

El ADR 0009 cerró la arquitectura de integración profunda con `axon`
(`Actividad`/`Tarea` portados, reemplazando `TableroHito`) pero dejó dos
cosas para resolver en implementación: el acceso real al código de `axon`
(la sesión anterior asumió `/home/ubuntu/axon`, que no existe en este
entorno) y la matriz de transiciones de `Tarea.status`, que axon no valida
y el ADR no definió.

Se encontró el repo real en `/home/sebas/axon` — se portaron tipos de
columna, nombres de enum y relaciones desde el código fuente real
(`app/Models/Task.php`, `Activity.php`, `TaskLink.php`,
`app/Enums/TaskStatus.php`, `TaskPriority.php`, y sus migraciones), no de
memoria ni de la documentación del ADR 0009.

La matriz de transiciones de `Tarea.status` se le presentó al usuario con
una recomendación explícita antes de sembrarla (no estaba en el ADR 0009).

## 2. Decisión

### 2.1 Migraciones

- `transiciones_estado_permitidas` gana `estado_origen_codigo`/
  `estado_destino_codigo` (nullable string) en paralelo a las columnas de
  id existentes, y `estado_destino_id` pasa a nullable. **No se
  reescribieron a texto** las 3 filas de `tipo_catalogo` existentes
  (`estado_avance`, `estado_observacion`, `estado_cambio`) — están
  basadas en catálogo real con id y ya están probadas así; aumentar el
  blast radius de este PR para unificarlas no aporta nada hoy.
- `actividades`, `tareas`, `tarea_links`: columnas exactas del ADR 0009
  §2.2, tipos portados de las migraciones reales de axon (`decimal(8,2)`
  para horas, `decimal(5,2)` para `peso` — mismo tipo que ya usa
  `tablero_hitos.peso`, `unsignedTinyInteger` para `tarea_links.type`).
  **Id autoincremental, no ULID** (axon usa ULID) — se prioriza
  consistencia con el resto de Inspeccion (ningún modelo del módulo usa
  ULID) sobre cero-fricción en una integración a axon que todavía no
  tiene fecha; el rename de PK en ese momento es mecánico.
- `actividades.tablero_id` con `cascadeOnDelete()`, igual que
  `tablero_hitos.tablero_id` (la entidad que reemplaza) — **no**
  `restrictOnDelete()` como `Observacion`/`ControlCambio`/etc., que
  protegen historial de auditoría de calidad; `Actividad`/`Tarea` son
  planificación de avance, mismo tipo de dato que `TableroHito` ya era.
- Validado contra MariaDB real (ddev): `migrate` y `migrate:rollback -
  -step=4` completos, incluyendo el `->change()` de `estado_destino_id`
  (Laravel 13 no necesita `doctrine/dbal` para esto).

### 2.2 Guard generalizado — métodos paralelos, no tipos unión

Se evaluó ensanchar `puedeTransicionar()`/`validar()`/
`transicionesValidasDesde()` a `int|string`, pero `transicionesValidasDesde()`
recibe **solo el origen** (sin destino) — cuando el origen es `null`
(creación), no hay con qué inferir si la key es de catálogo o de código.
Se optó por métodos paralelos explícitos (`puedeTransicionarPorCodigo`,
`validarPorCodigo`, `transicionesValidasDesdePorCodigo`): cero riesgo para
las 3 llamadas existentes basadas en id (no se tocó su firma), y sin
sniffing de tipo frágil. Mismo patrón de cache-por-request-con-clone que
ya se corrigió en `/revisor` sobre el fix de N+1 anterior — aplicado desde
el arranque acá, no como fix posterior.

### 2.3 Matriz de transiciones de `Tarea.status`

Aprobada explícitamente por el usuario (axon no valida esto — cualquier
salto es válido ahí):

```
null → Pendiente
Pendiente → EnProgreso
EnProgreso → EnRevision
EnRevision → Completada
EnRevision → EnProgreso   (rebote)
EnProgreso → Bloqueada
Bloqueada → EnProgreso
Pendiente → Bloqueada
```

Sin salto directo a `Completada` ni reapertura de `Completada` — si hace
falta reabrir una tarea terminada, se define cuando el Kanban (PR7) lo
pida, no ahora.

### 2.4 `TareaObserver`

Mismo patrón que `ObservacionObserver`/`ControlCambioObserver`: valida en
`saving()` cuando `status` está dirty, usando `getOriginal('status')`
(que sí aplica el cast a enum — no es el string crudo) contra
`validarPorCodigo`.

### 2.5 Diferido a propósito (fuera de alcance de PR4)

- `predecesoras()`/`sucesoras()` en `Tarea` (relaciones vía
  `tarea_links`): axon distingue Tarea de Actividad en `source_id`/
  `target_id` porque usa ULID con prefijo `act-` para actividades: los
  ids son globalmente únicos. Acá, con id autoincremental por tabla, un
  `id` de `Tarea` y uno de `Actividad` **pueden colisionar** — un
  `BelongsToMany` ingenuo sería sutilmente incorrecto. Se resuelve en
  PR8 (Gantt), cuando de verdad hace falta escribir/leer estos links.
- Policies para `Actividad`/`Tarea` y abilities de la matriz de permisos
  del ADR 0009 §4 (`tablero_actividad.gestionar`, etc.): no hay ningún
  recurso Filament todavía que las invoque (llegan en PR6) — agregarlas
  ahora sería una Gate/Policy sin punto de aplicación.
- `getStatusAttribute()`/`completionPercentage()` de `Activity` (axon):
  es lógica de `CalculadorAvanceTablero` adaptado, PR6.

## 3. Verificación

- Migraciones: `migrate` + `migrate:rollback --step=4` contra MariaDB
  real (ddev), no solo SQLite en memoria.
- 15 tests nuevos: guard *PorCodigo (6, unit), modelos/relaciones (4),
  `TareaObserver` (6 — incluye rechazo de salto directo y de reapertura
  de Completada). Suite completa: **98 passed, 1 risky preexistente**
  (sin fallas), Pint limpio.

## 4. Alternativas descartadas

| Decisión | Alternativa descartada | Por qué |
|---|---|---|
| Métodos `*PorCodigo` paralelos | Ensanchar el guard existente a `int\|string` | Ambigüedad de tipo cuando origen es `null`; cero riesgo para las 3 llamadas ya probadas |
| Id autoincremental en `Actividad`/`Tarea`/`TareaLink` | ULID como axon | Consistencia con el resto de Inspeccion; el rename en la integración futura es mecánico |
| Reescribir `transiciones_estado_permitidas` a solo columnas de código | Columnas de código en paralelo a las de id | Menor blast radius: no toca las 3 filas de catálogo ya probadas |
| Diferir `predecesoras()`/`sucesoras()` a PR8 | Definirlas ahora con un `type` de link discriminador inventado | El ADR 0009 no definió cómo distinguir Tarea de Actividad en el link — inventarlo ahora sin necesidad real de uso es diseñar a ciegas |

## 5. Siguiente paso

**PR5** del ADR 0009: comando de migración de datos (234 hitos existentes
→ `Actividad`/`Tarea`). Correr `/revisor` sobre este diff antes de abrir
su PR (mismo trámite retroactivo que PR3/ADR 0008,
[#3](https://github.com/discorallado/inspector/pull/3) y
[#4](https://github.com/discorallado/inspector/pull/4)).
