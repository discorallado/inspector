# 0001 — Modelo de datos e implementación inicial del módulo Inspección de Tableros

> Estado: implementado.

## Contexto

Se requiere reemplazar 3 planillas Excel (avance ponderado de fabricación de
tableros eléctricos, registro de observaciones de visitas de inspección de
calidad, y control de cambios de ingeniería) por un módulo Laravel + Filament,
autocontenido en `Modules/Inspeccion/`, construido sobre `nwidart/laravel-modules`
para portarse luego al PMIS real (`axon`). El requerimiento funcional completo
está en `Modules/Inspeccion/docs/0002-seguimiento-inspeccion-tableros.md`.

## Decisión

### Modelo de datos

- `Proyecto` vive como **stub** dentro del propio módulo
  (`Modules\Inspeccion\Models\Proyecto`), no en `app/` raíz, para que el
  módulo sea 100% autocontenido y portable. Al integrar a `axon` se elimina
  este archivo y `Tablero::proyecto()` apunta al `Proyecto` real.
- `Tablero` cuelga de `Proyecto`; `TableroHito` cuelga de `Tablero` con
  `peso` + `estado_avance_id` (catálogo) para el cálculo de avance ponderado.
- `Observacion` es la entidad central que generaliza "no conformidad",
  "consulta al integrador" y "sugerencia" mediante `tipo_observacion_id`
  (catálogo con flag `requiere_severidad`), en vez de modelos separados.
- `VisitaInspeccion` ↔ `Tablero` es una relación N:N explícita (pivot
  `tablero_visita_inspeccion`), no derivada de las observaciones, para poder
  representar el estado "Sin Observaciones" de una visita que no generó
  hallazgos en un tablero visitado.
- El checklist IEC 61439 sigue el patrón librería → plantilla → ejecución
  snapshot: `ChecklistEjecucionItem` copia `categoria`/`item`/
  `referencia_normativa` al crear la ejecución (`ChecklistEjecucion::crearDesdeTemplate()`),
  para que el histórico de una visita no cambie si el catálogo maestro se
  edita después.
- Todos los catálogos (`estados_avance`, `estados_observacion`,
  `tipos_observacion`, `severidades`, `estados_cambio`, `especialidades`,
  `grupo_hitos`, `resultados_checklist`) son tablas en BD administrables
  desde Filament, nunca enums de PHP. Todas llevan `organization_id` nullable.

### Máquina de estados

Se descartó adoptar un paquete de state machine de Laravel
(`spatie/laravel-model-states`, `asantibanez/laravel-eloquent-state-machines`)
porque ambos exigen definir los estados como clases/config en PHP, lo que
entra en conflicto directo con el principio de "catálogos configurables en
BD, nunca hardcodeados" de este repo.

En su lugar, `TableroHito.estado_avance`, `Observacion.estado_observacion` y
`ControlCambio.estado_cambio` validan sus transiciones contra una tabla
`transiciones_estado_permitidas` (`tipo_catalogo`, `estado_origen_id`
nullable, `estado_destino_id`), administrable desde Filament sin deploy.
`Modules\Inspeccion\Services\TransicionEstadoGuard` consulta esa tabla; tres
Observers (`TableroHitoObserver`, `ObservacionObserver`,
`ControlCambioObserver`) la invocan en el hook `saving()` de cada modelo,
rechazando con `TransicionEstadoInvalidaException` cualquier salto no
sembrado. Las opciones de los `Select` en Filament ya filtran por
transición válida, así que el usuario normalmente no llega a disparar la
excepción — es una red de seguridad, no la primera línea de defensa.

Reglas de negocio confirmadas y sembradas en `TransicionEstadoPermitidaSeeder`:
- `estado_avance`: secuencia forzada Pendiente → En proceso → Completado,
  con salida a N/A desde Pendiente o En proceso.
- `estado_observacion`: Pendiente → Subsanada (OK) | Informativa, ambos
  terminales sin reapertura (si reaparece el problema se crea una
  observación nueva, para no perder el historial de cierre).
- `estado_cambio`: Propuesto → Aprobado | Rechazado; Aprobado →
  Implementado | Rechazado (se permite revertir una aprobación antes de
  implementar).

### Avance ponderado

`Modules\Inspeccion\Services\CalculadorAvanceTablero` calcula
`Σ(peso × valor_estado) / Σ(peso) × 100`, excluyendo hitos cuyo estado tiene
`excluye_calculo = true` (N/A). El resultado se cachea en
`tableros.avance_global` (+ `avance_calculado_at`) vía `TableroHitoObserver`
en cada `saved()`/`deleted()` de un hito, para que las tablas Filament puedan
ordenar/filtrar por avance sin N+1.

### Permisos

CLAUDE.md prohíbe instalar `filament-shield`/`spatie/laravel-permission` en
este repo transicional. Se implementó una matriz de permisos simple en
`config/inspeccion.php` (rol → habilidades), registrada como `Gate::define()`
en `InspeccionServiceProvider`, más una `CatalogoPolicy` compartida y
Policies dedicadas por entidad (`TableroPolicy`, `ObservacionPolicy`, etc.),
todas marcadas `// TODO: reemplazar por policy real al integrar a axon`. Se
agregó una columna `role` a `users` (migración del módulo) porque el repo no
tiene aún ningún sistema de roles.

### Otros ajustes de infraestructura

- El `composer.json` raíz no tenía configurado
  `wikimedia/composer-merge-plugin`, por lo que el `composer.json` del
  módulo nunca se fusionaba al autoload — se agregó la config de
  `extra.merge-plugin.include` para `Modules/*/composer.json`.
- `App\Models\User` implementa `Filament\Models\Contracts\FilamentUser`
  (`canAccessPanel()` retorna `true`); sin esto, Filament niega el acceso al
  panel en cualquier entorno que no sea `local` (bloqueaba incluso los tests).

## Alternativas descartadas

- **Un modelo `NoConformidad` separado** en vez de `Observacion.tipo`: se
  descartó porque duplicaría el tracking que hoy vive repartido en 2
  planillas Excel distintas (requerimiento §3.4).
- **Catálogo único polimórfico** (`catalogo_valor` + columna `tipo`) en vez
  de una tabla por catálogo: se descartó porque `estados_avance` necesita
  columnas propias (`valor`, `excluye_calculo`) que no aplican al resto,
  y una tabla compartida dejaría esas columnas nulas y sin sentido en los
  demás catálogos.
- **Derivar Visita↔Tablero de las Observaciones** en vez de pivot explícita:
  pierde la capacidad de registrar una visita sin hallazgos a un tablero.
- **Kanban de terceros para `ObservacionResource`**: se prefirió tabla
  agrupada nativa de Filament para no sumar una dependencia externa en un
  repo cuyo alcance es acotado y transicional.

## Qué se construyó

- 22 migraciones (catálogos + entidades core + checklist + `users.role`),
  todas con `down()` real.
- 19 modelos Eloquent con relaciones, casts y `$fillable`.
- 3 servicios (`CalculadorAvanceTablero`, `CalculadorEstadoVisita`,
  `TransicionEstadoGuard`) + 3 Observers + 1 excepción de dominio.
- Gates + 8 Policies (una compartida para catálogos).
- 19 factories + 10 seeders (catálogos, transiciones, checklist IEC 61439
  base de 8 ítems).
- 17 recursos Filament (incluye catálogos) con relation managers para
  `Tablero` (hitos, observaciones rollup, cambios), `VisitaInspeccion`
  (observaciones, checklists) y `ChecklistTemplate`/`ChecklistEjecucion`.
- 3 widgets de dashboard (avance por tablero, observaciones por estado,
  cambios pendientes).
- 48 tests Pest (unitarios de los 3 servicios + feature de los flujos de
  hito/observación/cambio + smoke tests de todas las páginas Filament).

## Import de datos históricos

No implementado en este alcance — el comando artisan de import desde los
`.xlsx` originales (`maatwebsite/excel`) queda pendiente como próximo paso,
usando como referencia las columnas reales de
`Seguimiento_Integracion_Tableros_Integrado.xlsx`,
`Control_Observaciones_Visitas_IFX.xlsx` y
`Control_Inspecciones_Calidad_IFX.xlsx` (requerimiento §8).
