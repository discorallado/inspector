# 0017 — Rename: TableroHito/GrupoHito → HitoLegado/GrupoHitoLegado

> Estado: implementado. Responde a una pregunta del usuario sobre la
> estructura de datos, no a un bug.

## Contexto

El usuario preguntó: "¿HitosTableros es padre de Actividades? ¿Cuál es
el padre de quién?" — la confusión venía de que el sistema viejo
(`TableroHito`/`GrupoHito`, deprecado desde ADR 0009/0011, pendiente de
drop en PR9) y el nuevo (`Actividad`/`Tarea`, portado de axon) cuelgan
los dos de `Tablero`, pero son **árboles paralelos sin relación
estructural entre sí** — el nombre `TableroHito` (comparte prefijo con
`Tablero`, y "Hito" suena a "hito de avance", cerca conceptualmente de
"Actividad") no dejaba eso claro.

Estructura real (sin cambios, solo se aclara acá):

```
Proyecto -> Tablero -> Actividad -> Tarea         (activo, Kanban/Gantt)
Tablero -> HitoLegado -> GrupoHitoLegado (catálogo global)   (deprecado, PR9)
```

El único puente entre ambos es `Tarea.tablero_hito_id` (ADR 0012), que
sirve solo para que `MigrarHitosATareasCommand` sepa qué `HitoLegado`
originó cada `Tarea` migrada — no es una relación estructural, se va con
el resto en PR9.

## Decisión

Se ofrecieron 3 opciones (renombrar solo el sistema viejo / no tocar
código y solo documentar / adelantar PR9 completo). El usuario eligió
**renombrar solo el sistema viejo**: `TableroHito` -> `HitoLegado`,
`GrupoHito` -> `GrupoHitoLegado`. Deja `Proyecto -> Tablero -> Actividad
-> Tarea` intacto (ya es claro) y es trabajo de tránsito bajo, porque
este sistema se borra completo en PR9 de todos modos.

### Qué se renombró
- Modelos: `TableroHito` -> `HitoLegado` (tabla `tablero_hitos` ->
  `hitos_legados`), `GrupoHito` -> `GrupoHitoLegado` (tabla
  `grupo_hitos` -> `grupos_hitos_legados`).
- Relaciones: `Tablero::tableroHitos()` -> `hitosLegados()`,
  `EstadoAvance::tableroHitos()` -> `hitosLegados()`,
  `GrupoHitoLegado::hitosLegados()` (antes `tableroHitos()`),
  `HitoLegado::grupoHitoLegado()` (antes `grupoHito()`),
  `Tarea::hitoLegado()`/`Observacion::hitoLegado()` (antes
  `tableroHito()`).
- Clases: `TableroHitoObserver` -> `HitoLegadoObserver`,
  `TableroHitoPolicy` -> `HitoLegadoPolicy`,
  `TableroHitosRelationManager` -> `HitosLegadosRelationManager`,
  `TableroHitoFactory`/`GrupoHitoFactory` -> `HitoLegadoFactory`/
  `GrupoHitoLegadoFactory`, `GrupoHitoSeeder` -> `GrupoHitoLegadoSeeder`.
- Recurso Filament: directorio `GrupoHitos/` -> `GrupoHitoLegados/`
  (nombre elegido a propósito, ver hallazgo abajo), clase
  `GrupoHitoResource` -> `GrupoHitoLegadoResource`.
- Ability: `tablero_hito.actualizar` -> `hito_legado.actualizar`.
- Lang keys: `inspeccion.tablero_hito.*` -> `inspeccion.hito_legado.*`,
  `inspeccion.catalogos.grupo_hito` -> `inspeccion.catalogos.grupo_hito_legado`
  ("Grupos de Hito (legado)"), `inspeccion.observacion.campos.tablero_hito`
  -> `...campos.hito_legado`.
- Migración nueva (`2026_08_05_090000_renombra_tablero_hitos_y_grupo_hitos_a_legado.php`,
  no se editan las dos tablas ya creadas por migraciones corridas)
  con `Schema::rename()` — no se tocan columnas (`tablero_id`,
  `grupo_hito_id`, `tareas.tablero_hito_id` quedan iguales, son
  temporales de todos modos): las relaciones que las usan pasan FK
  explícita (`belongsTo(HitoLegado::class, 'tablero_hito_id')`) en vez
  de depender de la convención automática de Eloquent, que con el
  modelo renombrado ya no adivina el nombre de columna correcto.

### Qué NO se tocó
Migraciones ya corridas (comentarios internos siguen mencionando
"TableroHito"/"GrupoHito" — son historia, no se editan, ver CLAUDE.md
§4), el documento de requerimiento original
(`docs/0002-seguimiento-inspeccion-tableros.md`), y las columnas físicas
mencionadas arriba.

### Orden de la migración de rename
Se creó inicialmente con timestamp `2026_08_02_100000`, pero corría
**antes** de otras dos migraciones ya existentes que todavía tocan las
tablas viejas por nombre literal (`2026_08_03_090000_add_unique_a_grupo_hitos_nombre`,
`2026_08_04_090000_add_tablero_hito_id_a_tareas_table` con
`constrained('tablero_hitos')`) — reventaba en una BD fresca (`no such
table: grupo_hitos`, la tabla ya se había renombrado). Se movió a
`2026_08_05_090000`, después de todas las migraciones que aún
referencian los nombres viejos. Verificado con rollback + re-migrate y
con la suite completa (que corre todas las migraciones desde cero en
SQLite en memoria).

### Hallazgo no relacionado, encontrado al renombrar el recurso Filament
Nombrar el directorio del recurso `GruposHitosLegados/` (plural
"completo", como se nombra normalmente en español) hacía que Filament
generara una ruta duplicada: `/admin/configuracion/grupos-hitos-legados/grupo-hito-legados`.
Causa (`Resource::resolveDefaultSlug()`, `vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php`):
Filament compara el namespace del directorio contra
`Str::pluralStudly()` de la clase sin el sufijo `Resource`
(`GrupoHitoLegado` -> `GrupoHitoLegados`, plural simple con `s` al
final) — si no coinciden literalmente, usa `directorio/slug-propio` en
vez de un solo segmento. Se renombró el directorio a `GrupoHitoLegados/`
(coincide con el plural real de Eloquent) para volver a un solo
segmento de URL, como el resto de los recursos del módulo.

## Verificación
- `ddev exec ./vendor/bin/pest --parallel`: 162 passed (misma cobertura
  que antes del rename, más la URL actualizada en
  `FilamentResourcesSmokeTest`).
- `ddev exec ./vendor/bin/pint --dirty`: limpio.
- `ddev exec php artisan migrate`: rename aplicado sin error.
- Datos reales verificados intactos tras el rename: 234 `HitoLegado` /
  8 `GrupoHitoLegado` (mismos conteos que antes del rename).
- `ddev exec php artisan inspeccion:migrar-hitos-a-tareas` corrido dos
  veces contra los datos reales (234 hitos, 48 grupos usados): primera
  corrida crea 48 actividades + 234 tareas, segunda corrida solo
  actualiza (0 creadas) — confirma que el comando renombrado sigue
  siendo idempotente contra datos reales, no solo en tests.
- `rm -rf public/build && ddev exec npm run build`: sin errores.

## Alternativas descartadas

| Alternativa | Por qué se descartó |
|---|---|
| No renombrar, solo documentar | El usuario prefirió que el código mismo sea autoexplicativo, no depender de que alguien lea un ADR antes de tocar el módulo |
| Adelantar PR9 completo en vez de renombrar | Cambio más grande hoy; el usuario prefirió resolver la confusión ahora sin agrandar el alcance de esta sesión |
| Renombrar también las columnas (`tablero_id`, `tablero_hito_id`, `grupo_hito_id`) | Son temporales (se van en PR9), tocar columnas con datos reales ya migrados agrega riesgo sin aportar claridad adicional — la ambigüedad estaba en los NOMBRES DE CLASE, no en las columnas |
