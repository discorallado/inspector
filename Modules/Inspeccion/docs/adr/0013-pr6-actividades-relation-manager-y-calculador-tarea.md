# 0013 — PR6: ActividadesRelationManager + CalculadorAvanceTablero sobre Tarea

> Estado: implementado. Cierra PR6 del plan de ADR 0009.

## Contexto

Con PR4/PR5 (ADR 0010/0011) y el checkpoint de ADR 0012, `Actividad`/`Tarea`
ya tienen los 234 hitos históricos migrados y una clave de matcheo estable
(`tareas.tablero_hito_id`). Faltaba la UI Filament para gestionarlos y el
recálculo de `avance_global` sobre la fuente nueva.

## Decisión

### 1. `CalculadorAvanceTablero` pasa a sumar exclusivamente sobre `Tarea`
Confirmado contra ADR 0009 §2.2: no hay período de doble fuente. La fórmula
(`Σ(peso × valor_estado) / Σ(peso) × 100`) es la misma; cambia de dónde lee
los pesos/estados:
- `TaskStatus::valor()` (nuevo) reemplaza a `EstadoAvance.valor`: Pendiente/
  Bloqueada = 0.0, EnProgreso = 0.5, EnRevision = 0.9 (sin equivalente
  histórico — estimación ajustable), Completada = 1.0.
- `Tarea.excluye_calculo` (columna nueva, boolean) reemplaza a
  `EstadoAvance.codigo = 'na'`. Se separó de `status` porque `Bloqueada`
  tiene dos sentidos distintos en el negocio: "N/A, no aplica" (histórico)
  vs. "bloqueada de verdad, cuenta como 0% pero sí participa del ponderado".
  Confundirlos en un solo enum habría sido incorrecto — decisión tomada con
  el usuario vía pregunta explícita, no asumida.
- `Tablero::tareas()` (`HasManyThrough` vía `Actividad`) es la fuente de
  lectura del calculador.
- `MigrarHitosATareasCommand` llama `recalcularYGuardar()` una vez por
  Tablero al final de su loop, porque usa `withoutEvents()` (bypass de
  `TareaObserver` para el import masivo) y por lo tanto nadie más dispara
  el recálculo durante la migración.
- Fórmula extraída a `CalculadorAvanceTablero::calcularSobreColeccion()`
  (pública, estática) para reutilizarse también en `Actividad::avance()`
  sin duplicar la lógica de ponderado.

`TableroHitoObserver` sigue llamando a `CalculadorAvanceTablero` en sus
hooks `saved`/`deleted`, pero como el calculador ya no lee `TableroHito`,
esas llamadas quedan sin efecto práctico salvo idempotencia (recalculan
sobre las mismas Tareas). No se tocó — es referencia histórica congelada
(readonly desde ADR 0012), y limpiar ese acoplamiento muerto es trabajo de
PR9, no de este PR.

### 2. UI: `ActividadesRelationManager` (plano) + drill-down a `ActividadResource`, no un accordion custom

Alcance reducido frente a lo descrito en ADR 0009 §3 (el componente
`ActivityAccordion` de axon: ~1100 líneas, 3 iteraciones, Actividad como
sección colapsable con sus Tareas adentro). Razón técnica concreta, no solo
de esfuerzo: `Tablero::tareas()` es un `HasManyThrough` y Eloquent no
soporta `create()` sobre relaciones `HasManyThrough` (no hay una FK directa
que fijar). Un `RelationManager` de Filament necesita `create()` real sobre
su relación declarada, así que el CRUD de `Tarea` no puede vivir colgado
directamente de `Tablero`.

Se implementó en su lugar:
- **`ActividadesRelationManager`** (bajo `TableroResource`, reemplaza a
  `TableroHitosRelationManager` como vista principal de avance): CRUD
  completo de `Actividad` (`actividades` es un `HasMany` real de
  `Tablero`), columnas nombre/orden/fechas/cantidad de tareas (`counts()`)
  /avance (`Actividad::avance()`), y una acción `verTareas` que enlaza a
  `ActividadResource`.
- **`ActividadResource`** (nuevo, `shouldRegisterNavigation = false` — sin
  entrada propia en el menú, solo alcanzable desde el enlace anterior):
  contiene **`TareasRelationManager`**, CRUD completo de `Tarea` (`tareas`
  es un `HasMany` real de `Actividad`, sin la limitación de arriba).
- `TableroHitosRelationManager` no se tocó más allá de lo ya hecho en ADR
  0012 (readonly); sigue registrado en `TableroResource` como referencia
  histórica hasta el cleanup de PR9.

La UX de accordion completa (Tareas visibles inline debajo de cada
Actividad, sin navegar a otra página) queda abierta como follow-up si se
necesita — este PR prioriza corrección y cobertura de tests sobre paridad
visual con axon.

### 3. Policies nuevas
`ActividadPolicy`/`TareaPolicy` (mismo patrón `// TODO: reemplazar por
policy real al integrar a axon` que el resto del módulo), registradas en
`InspeccionServiceProvider`. Permisos nuevos en `config/inspeccion.php`:
`tablero_actividad.gestionar` (super_admin, ingeniero — crear/editar/borrar
Actividad, y borrar/crear Tarea), `tablero_tarea.actualizar` (+ tecnico —
editar Tarea), `tablero_tarea.asignar` (+ supervisor, sin uso todavía, para
cuando exista asignación de responsable en PR7/PR8).

### 4. `TaskStatus`/`TaskPriority` implementan `HasLabel`
Necesario para que los `Select`/badges de las nuevas relation managers
muestren la etiqueta traducida (`lang/es/inspeccion.php`) en vez del value
crudo del enum. No implican Kanban/Gantt (eso sigue en PR7/PR8); es UI
mínima para un `Select` de formulario.

## Alternativas descartadas

| Alternativa | Por qué se descartó |
|---|---|
| Accordion custom estilo axon (Blade/Livewire a medida) | Alto costo (axon tardó 3 iteraciones, ~1100 líneas) para este PR; los RelationManagers estándar de Filament cubren el CRUD real con mucho menos código y tests más simples. Se puede retomar como follow-up. |
| `TareasRelationManager` colgado directamente de `Tablero` vía `tareas()` | `HasManyThrough` no soporta `create()` — habría que reescribir la relación o el `create` a mano, más frágil que separar el CRUD en `ActividadResource`. |
| Mantener `CalculadorAvanceTablero` sumando sobre ambas fuentes (`TableroHito` y `Tarea`) durante una transición | Ya no hay nada que transicionar: los 234 hitos reales ya están migrados a `Tarea` y `TableroHitosRelationManager` es readonly. Sumar dos fuentes sería doble conteo o lógica de deduplicación innecesaria. Confirmado contra ADR 0009 §2.2. |

## Verificación

Suite completa: 128 passed, 1 risky preexistente (sin fallas), Pint limpio.
Contra MariaDB real (ddev): `migrate:fresh --seed` limpio, y
`inspeccion:migrar-hitos-a-tareas` corrido dos veces (primera: 48
Actividades + 234 Tareas creadas; segunda: 0 creadas / 234 actualizadas,
confirma idempotencia). Verificado a mano que `avance_global` calculado
sobre `Tarea` coincide con el cálculo manual para varios tableros (ej. `TP`:
33 pendientes + 1 en_progreso + 4 completadas + 1 bloqueada/excluida →
(4×1 + 1×0.5) / 38 × 100 = 11.84%, igual al valor persistido). Tests nuevos:
`ActividadesRelationManagerTest`, `TareasRelationManagerTest` (creación,
permisos por rol, unique `code` por Actividad), `CalculadorAvanceTableroTest`
reescrito sobre `Actividad`/`Tarea` en vez de `GrupoHito`/`TableroHito`/
`EstadoAvance`.

## Siguiente paso

PR7 del ADR 0009: Kanban de `Tarea` (retoma `TaskStatus`/`TaskPriority` con
contratos `HasColor`/`HasIcon` si hace falta). Sugerido correr `/revisor`
antes de abrir PR.
