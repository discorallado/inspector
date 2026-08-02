# 0018 — Reordenamiento de sidebar, drilldown vía relation managers, rename Checklist→Prueba

> Estado: implementado. Cierra el diseño de `/arquitecto` sobre "Proyecto →
> Tableros → (Inspección de Calidad, Control de Cambios)" tras varias
> rondas de iteración con el usuario.

## Contexto

El usuario pidió reordenar el sidebar (`Proyecto → Tableros → (Inspección
de Calidad, Control de Cambios)`), lo cual escaló — vía preguntas de
`/arquitecto` — a una decisión más de fondo: si ese "drilldown" debía ser
nesting real de Filament (URLs jerárquicas, sin listado global) o
relation managers (patrón que el módulo ya usaba parcialmente para
`Actividad`). Se optó por relation managers: reutiliza el patrón
existente, no rompe los listados globales que Calidad necesita, y evita
la contradicción real encontrada en el camino (un resource nesteado no
puede tener a la vez un link de sidebar propio Y ruta jerárquica — un
`NavigationGroup` no resuelve eso, requiere que el recurso tenga ruta
independiente).

En paralelo, el usuario decidió descartar el checklist de inspección
(IEC 61439) por ahora y reutilizar toda su infraestructura (misma
estructura, sin duplicar el patrón snapshot) como "Pruebas" —
`ChecklistEjecucion`/`ChecklistTemplate`/`ChecklistItemLibrary`/
`ChecklistEjecucionItem` → `Prueba`/`PruebaTemplate`/`PruebaItemLibrary`/
`PruebaItem`.

## Decisión

### 1. Árbol de navegación final

```
Proyecto                                    (nav reactivada, sort=1)
 └─ VisitasRelationManager (VisitaInspeccion, sin ítem propio)

Tablero                                     (sort=2)
 ├─ ActividadesRelationManager    (ya existía)
 ├─ ObservacionesRelationManager  (ya existía, solo lectura — rollup)
 ├─ PruebasRelationManager        (nuevo)
 ├─ ControlCambiosRelationManager (ya existía, sin ítem propio ahora)
 └─ HitosLegadosRelationManager   (ya existía, solo lectura histórica)

Inspección (NavigationGroup, sort=3)
 └─ ObservacionResource           (único ítem — ver §2)

Configuración (Cluster, sort=4)
 └─ catálogos, incl. PruebaTemplateResource / PruebaItemLibraryResource
```

`InspeccionCalidadCluster` y el trait `PerteneceAInspeccionCalidad` se
eliminaron (dead code — nada los referenciaba tras mover `Observacion` a
un `NavigationGroup` y ocultar `VisitaInspeccionResource`).

### 2. Por qué `ObservacionResource` se reubica en vez de construir una página nueva
El usuario pidió "una página que agrupe todas las observaciones para
mantener esa funcionalidad" (la vista transversal cross-tablero que
Calidad usa a diario). En vez de construir una `Page` de solo lectura en
paralelo, se reubicó `ObservacionResource` completo (ya tenía filtros
por tablero/tipo/estado/especialidad y una acción rápida "vencidas") al
nuevo `NavigationGroup` "Inspección" — reutiliza CRUD ya probado en vez
de duplicar lógica, cumple el pedido literal ("mantener esa
funcionalidad") con más capacidad (edición inline), no menos.

`ControlCambio` no tuvo el mismo pedido explícito — su resource queda
oculto del todo (`shouldRegisterNavigation = false`), accesible solo vía
`ControlCambiosRelationManager` en `Tablero`.

### 3. Rename Checklist → Prueba
- Modelos: `ChecklistEjecucion` → `Prueba` (tabla `checklist_ejecuciones`
  → `pruebas`), `ChecklistEjecucionItem` → `PruebaItem`
  (`checklist_ejecucion_items` → `prueba_items`), `ChecklistTemplate` →
  `PruebaTemplate` (`checklist_templates` → `prueba_templates`,
  `checklist_template_items` → `prueba_template_items`),
  `ChecklistItemLibrary` → `PruebaItemLibrary`
  (`checklist_item_libraries` → `prueba_item_libraries`). A diferencia
  del rename `TableroHito`/`GrupoHito` → `*Legado` (ADR 0017, que solo
  tocó tablas), acá **se renombran también las columnas FK**
  (`checklist_template_id` → `prueba_template_id`, etc.) — "Prueba" es
  el nombre definitivo hacia adelante, no un puente temporal camino a
  borrarse, así que vale la pena la consistencia completa. Sin datos
  reales que migrar: `checklist_ejecuciones`/`visitas_inspeccion`
  estaban en 0 filas, solo el catálogo (1 plantilla, 8 ítems IEC 61439)
  tenía contenido, y ese catálogo se reemplaza de todos modos.
- `visita_inspeccion_id` en `pruebas` pasa a **nullable**: el punto de
  entrada de una Prueba ahora es su Tablero (`PruebasRelationManager`),
  no una Visita — antes siempre había una Visita en contexto
  (`ChecklistEjecucionesRelationManager` vivía en
  `VisitaInspeccionResource`), ahora es opcional.
- Guardrail de Schema (`Filament\Schemas\Components\Section`/`Grid`,
  ver `/arquitecto` con la doc de Schemas): el `Select` de
  `prueba_template_id` en `PruebaForm` solo es visible en `create`
  (`->visible(fn (string $operation) => $operation === 'create')`) — una
  vez tomado el snapshot de ítems, cambiar la plantilla los dejaría
  inconsistentes. Verificado con un test que revisa la clase `fi-hidden`
  real en el HTML (ver hallazgo de testing abajo).
- Ability `checklist_ejecucion.completar` → `prueba.completar` (mismos
  roles: super_admin, supervisor, calidad).

### 4. `TableroForm` con `Section` + `Grid`
Aplicando la doc de Schemas de Filament: `Grid::make(2)` con una
`Section` "Datos del tablero" (campos editables) y otra "Avance"
(`TextEntry` de solo lectura para `avance_global`/`avance_calculado_at`,
visible solo en edición — un Tablero nuevo no tiene avance que mostrar
todavía). Antes esos dos campos calculados no aparecían en el form en
absoluto (solo en la tabla de listado); ahora quedan visibles pero
inequívocamente no editables (`TextEntry`, no `TextInput`).

## Hallazgos de bugs reales, encontrados escribiendo los tests

### `VisitasRelationManager`: `Select::options()` no valida, `relationship(modifyQueryUsing:)` sí
Al escribir el test de "no permite adjuntar un tablero de OTRO proyecto",
la primera implementación (`->options(fn () => $this->getOwnerRecord()->tableros()->pluck(...))`
junto a `->relationship('tableros', 'tag')`) **no rechazó** un tablero
ajeno enviado directo por `wire:call` — se adjuntó igual. `options()`
solo controla qué se **muestra** en el `<select>`, no valida el submit.
Corregido con `relationship('tableros', 'tag', modifyQueryUsing: fn ($query) => $query->whereBelongsTo($this->getOwnerRecord(), 'proyecto'))`,
que sí acota la query real usada tanto para poblar opciones como para
guardar — Filament valida contra esa query (confirmado con el mismo
test, ahora en verde con `validation.in`). Mismo patrón de hallazgo que
el `SelectColumn` de ADR 0008: un campo de opciones "acotadas" en la UI
no es automáticamente una validación server-side.

### Test del guardrail `$operation`: `strpos()` sobre el string suelto no prueba nada
El primer intento de verificar que `prueba_template_id` está oculto en
edición comparaba si el string `"prueba_template_id"` aparecía en el
HTML — aparece siempre (está en el `wire:snapshot` JSON de Livewire,
antes que el campo real). El test pasaba sin haber probado el guardrail.
Corregido buscando el `wire:key` específico del campo
(`form.prueba_template_id`) y verificando la clase `fi-hidden` en el
bloque que lo envuelve.

## Verificación
- `ddev exec ./vendor/bin/pest --parallel`: 176 passed — incluye tests
  nuevos para `PruebasRelationManager`, `VisitasRelationManager`
  (incluyendo el hallazgo de scope corregido), navegación del sidebar
  (`shouldRegisterNavigation`/`getNavigationGroup`/`getNavigationSort`
  contra las clases reales), y el guardrail `$operation` verificado
  contra la clase `fi-hidden` real, no contra presencia de texto.
- `ddev exec ./vendor/bin/pint --dirty`: limpio.
- `ddev exec php artisan migrate`: rename + nullable aplicados sin error
  contra MariaDB real.
- `rm -f bootstrap/cache/filament/panels/admin.php && composer dump-autoload`:
  necesario tras el rename — Filament cachea las clases de resources
  descubiertas, un rename de clase/namespace sin esto deja el cache
  apuntando a la clase vieja (mismo síntoma ya visto en el rename de
  ADR 0017: `Class "...ChecklistEjecucionResource" not found`).
- `curl` contra las rutas nuevas reales (`/admin/proyectos`,
  `/admin/observacions`, `/admin/pruebas`,
  `/admin/configuracion/prueba-templates`,
  `/admin/configuracion/prueba-item-libraries`): 302 (redirect a login,
  sin sesión) en todas — sin 500, confirma que el routing quedó sano.

## Alternativas descartadas

| Alternativa | Por qué se descartó |
|---|---|
| Nesting real (`BelongsToParent`) para Tablero/Observacion/etc. bajo Proyecto | Contradicción real: un resource nesteado no tiene ruta propia — no puede a la vez tener un link de sidebar y ser hijo de un registro puntual. El usuario lo descartó al entender la consecuencia. |
| Construir una `Page` de solo lectura nueva para el agregado de Observaciones | `ObservacionResource` ya tenía exactamente los filtros necesarios (tablero/tipo/estado/especialidad); reubicarlo es menos código y más funcionalidad que duplicar la tabla en una página aparte. |
| Set de modelos `PruebaEjecucion`/etc. en paralelo, dejando `ChecklistEjecucion` intacto | El usuario prefirió una sola infraestructura repropuesta — sin datos reales que migrar, no había costo real en mantener dos sistemas del mismo patrón. |
| Modelo `Pruebas` (plural) como nombre de clase | Rompía la convención singular del resto del módulo sin razón técnica — el usuario confirmó plural solo para el nombre visible en el menú (ya resuelto por Filament vía `getPluralModelLabel()`, sin necesidad de tocar el nombre de la clase). |

## Siguiente paso

`/revisor` sobre este diff antes de abrir PR — hay bastante superficie
nueva (rename completo + 2 relation managers + reorganización de nav) en
un solo commit (pedido explícito del usuario).
