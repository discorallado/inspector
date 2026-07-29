# 0012 — Fixes de checkpoint antes de PR6 (matcheo por hito_id, unique de code, readonly)

> Estado: implementado. Cierra 2 hallazgos de la pasada de `/revisor` previa
> al inicio de PR6 (ADR 0009/0010/0011).

## Contexto

`/revisor` encontró, sobre el estado combinado de PR4+PR5 (Actividad/Tarea):
1. `MigrarHitosATareasCommand` matcheaba `Tarea` por `actividad_id`+`code`,
   y `code` se deriva de `TableroHito.item` (un `TextInput` libre, editable
   vía `TableroHitosRelationManager`, que seguía activo). Editar `item`
   entre dos corridas del comando dejaba una `Tarea` huérfana con datos
   desactualizados en vez de actualizar la existente.
2. `tareas.code` no tenía ninguna restricción real en BD.

## Decisión

### 1. Matcheo por `tablero_hito_id`, no por `code`
Nueva columna `tareas.tablero_hito_id` (FK nullable hacia `tablero_hitos`,
`nullOnDelete()`) — puente temporal, se va con el cleanup de PR9.
`MigrarHitosATareasCommand::handle()` ahora hace
`Tarea::query()->updateOrCreate(['tablero_hito_id' => $hito->id], [...])`;
el payload de actualización sigue recalculando `code`/`nombre`/`status`/etc.
en cada corrida, así que si `item` cambia, se refleja en `code` sin perder
la identidad de la `Tarea`.

### 2. `unique(['actividad_id', 'code'])` en `tareas`
Mismo patrón que ya usa `tableros` (`unique(['proyecto_id', 'tag'])`):
único dentro del padre, no global. Verificado antes de aplicar: los 234
registros ya migrados no chocan.

### 3. `TableroHitosRelationManager` de solo lectura
Sin `CreateAction`/`EditAction`/`DeleteAction`/bulk actions; `form()`
retorna un schema vacío; `canCreate()`/`canEdit()`/`canDelete()`/
`canDeleteAny()` devuelven `false`. No es solo por el campo `item` — con
el fix de matcheo por `tablero_hito_id` ya no había riesgo de huérfanas,
pero **cualquier otro campo editable ahí** (`peso`, `estado_avance_id`,
fechas) seguía en riesgo de quedar sobrescrito en silencio si el comando
se vuelve a correr después de que alguien trabaje directamente sobre
`Tarea` — el comando es una migración de una sola vía, no una
sincronización continua. Congelar `TableroHito` es la forma más simple de
garantizar eso, dado que de todos modos se reemplaza por
`ActividadesRelationManager` en PR6 y se deprecia del todo en PR9.

## Alternativas descartadas

| Alternativa | Por qué se descartó |
|---|---|
| Dejar solo el campo `item` en readonly, resto editable | No resuelve el riesgo de sobrescritura silenciosa sobre `peso`/`estado_avance_id`/fechas si el comando se re-corre — el problema de fondo es "una sola vía", no un campo puntual |
| `unique(['code'])` global en vez de compuesto | Mismo criterio que ya usa `tableros.tag` (único por padre, no global) — no hay requisito de negocio que pida códigos únicos en todo el sistema todavía |

## Verificación

Migrado + rollback (`migrate:rollback --step=2`) contra MariaDB real
(ddev). `inspeccion:migrar-hitos-a-tareas` corrido dos veces seguidas
contra los 234 hitos reales: primera corrida 48 Actividades + 234 Tareas
creadas, segunda corrida 0 creadas / 234 actualizadas — idempotente,
confirma que el nuevo matcheo por `tablero_hito_id` no rompe nada
existente. Test de regresión invertido (antes documentaba el bug, ahora
prueba el fix): `MigrarHitosATareasCommandTest.php` — editar `item` entre
corridas actualiza la misma `Tarea`, no crea una huérfana. Suite completa:
120 passed, 1 risky preexistente (sin fallas), Pint limpio.

## Siguiente paso

PR6 del ADR 0009: `ActividadesRelationManager` + `CalculadorAvanceTablero`
adaptado a `Tarea.peso`.
