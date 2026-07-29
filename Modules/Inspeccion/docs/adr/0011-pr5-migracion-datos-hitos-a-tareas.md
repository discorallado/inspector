# 0011 — PR5: comando de migración de datos (TableroHito → Actividad/Tarea)

> Estado: implementado. Segundo PR del plan de 6 definido en
> [0009-integracion-actividad-tarea-desde-axon.md](0009-integracion-actividad-tarea-desde-axon.md) §8.

## 1. Contexto

El ADR 0009 §2.5 aprobó portar los 234 `TableroHito` existentes a
`Actividad`/`Tarea` (PR4, ya implementado) vía un comando de datos, sin
borrar `TableroHito`/`GrupoHito`/`EstadoAvance` (deprecados hasta PR9). El
ADR no cerró dos detalles de implementación: `TaskStatus` no tiene un
caso "N/A" pero `EstadoAvance.codigo = 'na'` sí se usa en datos reales
(item `2.4` en varios tableros), y no definió cómo generar `Tarea.code`
(identificador que `/qa` ya había señalado como sin unicidad garantizada,
ver sesión anterior).

## 2. Decisión

Ambas se le presentaron al usuario antes de implementar (no se inventaron
a ciegas):

- **`na` → `Bloqueada`**: semántica disponible más cercana ("algo que no
  avanza"). Se pierde el matiz "excluido del cálculo de avance" —
  `CalculadorAvanceTablero` (PR6) va a tener que decidir si `Bloqueada`
  también se excluye, o si ese matiz se recupera de otra forma.
- **`Tarea.code = "{tablero.tag}-{hito.item}"`** (ej. `TP-2.4`,
  `BUS_A-1.1`): único en la práctica para los 234 hitos reales (`item`
  ya es único por tablero), legible, trazable al dato origen. **No**
  agrega un `unique()` a nivel de BD — ese es un gap de diseño aparte
  (señalado por `/qa`), no algo que este comando deba resolver
  unilateralmente.

### 2.1 `MigrarHitosATareasCommand`

`php artisan inspeccion:migrar-hitos-a-tareas`:

1. Por cada `Tablero`, agrupa sus `TableroHito` por `GrupoHito` (ordenado
   por `GrupoHito.orden`) y crea/actualiza una `Actividad` por grupo
   (`updateOrCreate` con clave natural `tablero_id`+`nombre`).
2. Por cada `TableroHito`, crea/actualiza una `Tarea` de la `Actividad`
   correspondiente (`updateOrCreate` con clave natural `actividad_id`+
   `code`), preservando `peso`, `real_inicio`/`real_fin`, mapeando
   `plan_inicio`/`plan_fin` a `start_date`/`due_date`, `observaciones` a
   `descripcion`, y `EstadoAvance.codigo` a `TaskStatus` según §2.
3. **`Tarea::withoutEvents()`** al crear/actualizar: es una carga
   histórica, no una transición de negocio hecha por un usuario — mismo
   criterio que `SeguimientoIntegracionTablerosSeeder` ya usa para
   `TableroHito::withoutEvents()`. Sin esto, `TareaObserver` rechazaría
   cualquier hito que no esté en `Pendiente` (solo `[null, Pendiente]`
   está sembrado como transición de creación válida).
4. Todo dentro de una `DB::transaction()` — si algo falla a mitad de
   camino, no deja datos parciales.
5. **Idempotente**: correrlo dos veces no duplica nada (verificado con
   un test que corre el comando dos veces y compara conteos + que el
   `id` de la `Tarea` no cambió entre corridas).
6. No toca `TableroHito`/`GrupoHito`/`EstadoAvance` — quedan intactos.

## 3. Verificación

- Corrido contra los datos históricos reales (234 hitos, vía
  `module:seed Inspeccion` + el comando): 48 `Actividad` creadas, 234
  `Tarea` creadas. Segunda corrida: 0 creadas, 234 actualizadas (mismo
  conteo, sin duplicados) — confirmado también con un test dedicado
  sobre un fixture controlado, no solo manualmente.
- 6 tests nuevos: conversión de `GrupoHito`→`Actividad`, conversión de
  `TableroHito`→`Tarea` con preservación de `peso`/fechas reales, mapeo
  completo de los 4 códigos de `EstadoAvance` (incluyendo `na`→`Bloqueada`),
  que un hito ya `Completado` no rompe el import (confirma que
  `withoutEvents()` funciona), idempotencia, y que
  `TableroHito`/`GrupoHito`/`EstadoAvance` sobreviven intactos.
- Suite completa: **113 passed, 1 risky preexistente** (sin fallas), Pint
  limpio.

## 4. Alternativas descartadas

| Decisión | Alternativa descartada | Por qué |
|---|---|---|
| `na` → `Bloqueada` | No migrar hitos `na` a `Tarea` (dejarlos solo en `TableroHito`) | Se eligió no perder el registro en el modelo nuevo — el matiz "excluido del cálculo" se resuelve en PR6, no ahora |
| `code = "{tag}-{item}"` | Secuencial simple (`TAR-001`, `TAR-002`, ...) | Trazabilidad visual al dato histórico; el secuencial no aporta nada que `id` ya no dé |
| Comando Artisan idempotente (`updateOrCreate`) | Seeder de una sola pasada (como `SeguimientoIntegracionTablerosSeeder`) | El ADR 0009 pide explícitamente "comando", y a diferencia del import inicial (que corre una vez sobre datos fijos), este puede necesitar re-correrse si `TableroHito` cambia mientras conviven ambos modelos |

## 5. Siguiente paso

**PR6** del ADR 0009: `ActividadesRelationManager` (reemplaza
`TableroHitosRelationManager`) + `CalculadorAvanceTablero` adaptado a
sumar sobre `Tarea.peso`. Ahí se resuelve el matiz "excluido del cálculo"
para `Bloqueada`/`na` mencionado en §2. Correr `/revisor` sobre este diff
antes de abrir su PR (mismo trámite retroactivo que los anteriores).
