# 0003 — Rediseño UX: de CRUD administrativo a herramienta de seguimiento en terreno

> Estado: aprobado (arquitectura). Pendiente de implementación en varios PRs (ver §6).
> Contexto: propuesto en sesión de `/arquitecto`, discutido y ajustado por el usuario.

## 1. Contexto

El módulo `Inspeccion` se construyó inicialmente (ver
[0002-seguimiento-inspeccion-tableros.md](../0002-seguimiento-inspeccion-tableros.md))
como un conjunto estándar de recursos Filament: tabla índice + formulario +
relation managers como pestañas, para `Tablero`, `Observacion`,
`ControlCambio`, `VisitaInspeccion` y `ChecklistEjecucion`.

Una vez con datos reales cargados (ver seeder de importación de la planilla
histórica), el usuario identificó que esta estructura sirve para *administrar*
registros pero no para *hacer seguimiento* activo del avance de fabricación
en terreno — que es el uso real de la herramienta (reemplaza 3 planillas
Excel que un supervisor revisa/actualiza caminando la planta).

Este ADR documenta la decisión de reestructurar la capa de presentación
(Filament) hacia una experiencia de seguimiento, **sin tocar el modelo de
datos ni las máquinas de estado ya implementadas** (`EstadoAvance`,
`EstadoObservacion`, `EstadoCambio`, `TransicionEstadoGuard` se mantienen
intactos), salvo dos columnas nuevas descritas en §3.

## 2. Decisión

### 2.1 Centro de Seguimiento (nueva página, reemplaza el Dashboard genérico)
Página custom Filament como landing del panel: grilla de *cards* por
`Tablero` (avance global, badges de NC abiertas/cambios pendientes), no una
tabla. Reemplaza `ListTableros` como punto de entrada.

### 2.2 Vista de Tablero (nueva página, reemplaza Edit + relation managers)
Página custom por tablero con:
- Header con avance global.
- **Gantt interactivo** de los `TableroHito`, agrupados por `GrupoHito` —
  mismo patrón vigente en `axon` (verificado contra `origin/main` del repo
  `axon`, no la copia local desactualizada — ver §4.1):
  `Filament\Resources\Pages\Page` + `InteractsWithRecord`, `mount()` resuelve
  el record y llama `$this->authorize('view', ...)` manualmente, los datos
  del Gantt se arman en un método `getGanttTasks()` (no una propiedad
  precomputada), y la vista Blade/Alpine carga `frappe-gantt` por CDN (sin
  build JS, sin dependencia npm, `wire:ignore` sobre el contenedor del SVG
  para que Livewire no lo destruya en cada render). Cambiar el estado de un
  hito sigue disparando `TransicionEstadoGuard` igual que hoy.
- Observaciones y control de cambios de ese tablero, inline, con las acciones
  de transición que ya existen (`ObservacionActions`, `ControlCambioActions`).

### 2.3 Kanban de Observaciones y Control de Cambios
`relaticle/flowforge` (Filament ^5, compatible con este repo que usa ^5.7)
sobre `Observacion` (columnas = `EstadoObservacion`) y `ControlCambio`
(columnas = `EstadoCambio`). Sin tablas nuevas; ver §3 para la columna de
orden que requiere el paquete. **Decisión tomada con el riesgo conocido de
que `axon` instaló y luego removió este mismo paquete — ver §4.1 y §7.**

### 2.4 Checklist en modo terreno
Página custom de llenado táctil (categoría + ítem + 3 botones grandes
Cumple/No Cumple/N.A.), reemplaza el `ItemsRelationManager` tabular para el
flujo de ejecución en sitio. El listado/administración de plantillas de
checklist (`ChecklistTemplateResource`) se mantiene como CRUD clásico.

### 2.5 Un solo panel Filament (no dos)
Se descarta la alternativa de un panel `terreno` separado. Las páginas de
§2.1–2.4 viven en el panel `admin` existente. Para la sensación "amigable a
tablet" se envuelve su contenido en un componente de layout compartido
(`resources/views/components/vista-terreno.blade.php` o similar) con una
clase CSS modificadora (botones más grandes, más espaciado) aplicada solo a
esas páginas — el resto del panel (catálogos, formularios administrativos)
no cambia. Evita duplicar registro de recursos, auth y sesión entre dos
panels.

### 2.6 Catálogos de Configuración: sin cambios
`EstadoAvance`, `Especialidad`, `Severidad`, etc. siguen siendo CRUD clásico
del cluster `Configuración`, visible solo a `super_admin`.

## 3. Cambios al modelo de datos

| Tabla | Columna nueva | Tipo | Motivo |
|---|---|---|---|
| `observaciones` | `posicion` | `integer nullable` | Orden dentro de columna del kanban (requerido por Flowforge) |
| `control_cambios` | `posicion` | `integer nullable` | Ídem |

Ninguna otra tabla cambia. `TableroHito` ya tiene `plan_inicio`,
`plan_fin`, `real_inicio`, `real_fin` — suficiente para el Gantt, sin
columnas nuevas.

## 4. Alternativas consideradas y descartadas

| Decisión | Alternativa descartada | Por qué se descartó |
|---|---|---|
| Kanban | `mokhosh/filament-kanban` | El usuario revisó personalmente ambos paquetes y prefiere `relaticle/flowforge` |
| Kanban | Grouping nativo de Filament Table (sin drag-and-drop) | Menos "app-like"; el usuario prefiere drag-and-drop real pese al mayor esfuerzo |
| Gantt | Librería distinta a frappe-gantt, o build a mano | `axon` (repo hermano/destino final del módulo) ya resolvió esto con frappe-gantt vía CDN; reusar el mismo patrón evita reinventar y facilita la integración futura del módulo a `axon` |
| Gantt | Barras de progreso por grupo sin timeline | Se prefiere fidelidad a la planilla Excel original que se reemplaza |
| Navegación terreno | Panel Filament separado (`Panel::make()->id('terreno')`) | Duplica auth/sesión/registro de recursos sin necesidad real; un layout con CSS modificador dentro del mismo panel logra el objetivo con menos superficie que mantener |

### 4.1 Hallazgo posterior: `axon` instaló y luego removió `relaticle/flowforge`

Al revisar `origin/main` del repo `axon` (la copia local estaba desactualizada
3 commits) se encontró que:

- En un commit anterior (`024e478`, local), `axon` sí tuvo
  `relaticle/flowforge: ^4.0` (v4.0.13) instalado por composer para su
  Kanban de tareas.
- En commits posteriores de `origin/main` (`b5c58e4`, `d09a8f8`, `bb70c5a`),
  `flowforge` fue **removido por completo** de `composer.json`/`composer.lock`
  y reemplazado por un Kanban construido a mano: página Filament +
  Livewire + **SortableJS 1.15.6 vía CDN** (`@assets` de Livewire 3), con
  `updateTaskStatus()` marcado `#[Renderless]`.
- El ADR que `axon` dejó escrito para ese cambio
  (`docs/adr/0007-kanban-gantt-export.md` en su `origin/main`) solo
  documenta el rechazo de `mokhosh/filament-kanban` (incompatible con
  Filament 5) — **no hay commit, comentario ni doc que explique por qué
  removieron `flowforge`** después de haberlo instalado y (presumiblemente)
  usado.
- Se presentó esto al usuario explícitamente como pregunta antes de
  continuar. **Decisión: mantener `relaticle/flowforge`** (preferencia
  original del usuario), aceptando el riesgo de que aparezca el mismo
  problema no documentado que llevó a `axon` a abandonarlo. Ver plan de
  mitigación en §7.

## 5. Permisos

Sin cambios a la matriz ya definida en `config/inspeccion.php`. Las páginas
custom nuevas deben verificar los Gates manualmente en cada acción (mismo
patrón que ya usa `ControlCambioActions`), ya que al ser páginas custom (no
Resources estándar) las Policies no se aplican automáticamente vía
`viewAny`/`update`.

## 6. Plan de implementación (varios PR, uno por unidad de trabajo)

Cada PR incluye sus propios tests Pest (no un suite monolítico al final) y
debe quedar en verde + Pint limpio antes de pasar al siguiente, según el
método de trabajo del `CLAUDE.md`.

1. **PR1 — Kanban de Observaciones**: instalar/configurar `relaticle/flowforge`,
   migración `posicion` en `observaciones`, board Filament con columnas por
   `EstadoObservacion`, reutilizando `ObservacionActions`. Tests: creación de
   board, movimiento de columna respeta transición válida, filtros básicos.
2. **PR2 — Kanban de Control de Cambios**: mismo patrón que PR1 sobre
   `ControlCambio` (migración `posicion` en `control_cambios`, board con
   columnas por `EstadoCambio`). Tests equivalentes.
3. **PR3 — Vista de Tablero (sin Gantt aún)**: página custom con header,
   hitos agrupados por `GrupoHito` (progreso por grupo, sin timeline),
   observaciones/cambios inline. Tests: acceso por Gate, transición de
   estado de hito desde la vista.
4. **PR4 — Gantt interactivo en Vista de Tablero**: agregar el componente
   Blade/Alpine con frappe-gantt (patrón `axon`) sobre los `TableroHito` de
   PR3. Tests: datos correctos pasados a la vista (fechas, agrupación),
   caso sin hitos con fecha.
5. **PR5 — Centro de Seguimiento**: página custom con cards por tablero,
   reemplaza `ListTableros` como landing. Tests: cálculo de badges (NC
   abiertas, cambios pendientes), Gate de acceso.
6. **PR6 — Checklist modo terreno**: pantalla de llenado táctil para
   `ChecklistEjecucion`. Tests: guardado de resultado por ítem, Gate
   `checklist_ejecucion.completar`.
7. **PR7 — Layout/CSS terreno + navegación**: componente de layout
   compartido con clase modificadora para tablet, reagrupación de
   navegación (sin segundo panel). Tests: smoke test de render en las
   páginas de terreno.
8. **PR8 — Reescritura de smoke tests existentes**: `FilamentResourcesSmokeTest`,
   `FilamentEditPagesSmokeTest`, `FilamentDashboardSmokeTest` y
   `PermisosConfiguracionTest` quedan desalineados con la nueva estructura
   de páginas; se reescriben uno por uno reflejando las rutas nuevas.

## 7. Riesgos y supuestos (actualizado tras revisión del usuario)

- Paquetes (`relaticle/flowforge`, `frappe-gantt`) ya revisados personalmente
  por el usuario — sin acción pendiente antes de implementar.
- **Riesgo conocido y aceptado sobre `flowforge`**: `axon` lo instaló y
  luego lo removió sin dejar registro del motivo (§4.1). Plan de mitigación
  para PR1: si durante la implementación aparece un problema de
  compatibilidad/estabilidad similar al que presumiblemente motivó su
  remoción en `axon`, el fallback es replicar el patrón que `axon` dejó
  funcionando (Livewire + SortableJS vía CDN, sin paquete de terceros) en
  lugar de insistir con `flowforge`. No bloquea el inicio de PR1.
- Soporte táctil del drag-and-drop en tablet: se verificará **después** de
  implementado PR1/PR2, en dispositivo real.
- El alcance completo (8 PRs) es grande; se ejecuta secuencialmente, cada
  uno cerrado con tests en verde antes de continuar — no se abre PR N+1
  con PR N roto.
- Al integrar el módulo a `axon` (CLAUDE.md §7), la reutilización del
  patrón de Gantt de `axon` debería simplificar esa integración (misma
  librería, mismo enfoque de página custom). Si el fallback de Kanban
  termina activándose, también se alinea mejor con `axon` en ese frente.
