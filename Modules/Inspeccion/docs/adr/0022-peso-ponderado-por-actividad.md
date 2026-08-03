# 0022 — Peso ponderado por Actividad + página de resumen

> Estado: implementado. Cierra dos pedidos encadenados en `/arquitecto`:
> una página de Tareas agrupadas por Actividad con estado/avance en vivo,
> y que las Actividades tengan peso propio para el avance global.

## Contexto

El usuario pidió una página con Tareas agrupadas por Actividad, mostrando
el peso de cada Actividad, un select de estado por Tarea, y avance % en
tiempo real. Al diseñar "peso de Actividad" surgió la pregunta real: hoy
`CalculadorAvanceTablero` pondera todas las Tareas del Tablero de una
sola pasada, ignorando a qué Actividad pertenecen — no hay ningún
concepto de "peso de Actividad". El usuario after eligió que sí lo
hubiera, para que el avance global pondere entre Actividades, no solo
entre Tareas sueltas.

## Decisión

### Fórmula de dos niveles, con fallback

`Actividad::avance()` queda exactamente igual (pondera sus propias
Tareas). Se agrega `CalculadorAvanceTablero::calcularSobreActividades()`:

```
avance_global = Σ(actividad.peso × actividad.avance()) / Σ(actividad.peso)
```

excluyendo Actividades sin `peso` asignado o cuyo `avance()` es `null`
(sin Tareas con peso computable — mismo criterio de "no participa, no
cuenta como 0%" que ya regía para Tarea). `calcular(Tablero)` prueba esta
fórmula primero; si el peso total de Actividades da 0 (nadie migró
todavía a pesos por Actividad), cae a la fórmula plana vieja
(`calcularSobreColeccion` directo sobre todas las Tareas) — así ningún
Tablero existente se queda sin número mientras la migración es parcial.

### Corrección real sobre lo que propuse en `/arquitecto`

En el diseño, para el backfill de `actividades.peso` en las Actividades
existentes ofrecí "peso=1 parejo para todas" prometiendo que "el
avance_global no cambia el día del deploy". **Eso era matemáticamente
falso** y se corrigió antes de implementar: peso parejo (1) convierte la
fórmula nueva en un promedio simple entre Actividades, no en la misma
fórmula ponderada de Tareas — da un número distinto salvo que todas las
Actividades tengan exactamente la misma suma de peso de Tareas (caso
raro). La migración real hace backfill con
`actividad.peso = Σ(peso de sus Tareas no excluidas)`, que sí es
algebraicamente equivalente a la fórmula vieja el día del deploy (se
puede demostrar reduciendo la fórmula de dos niveles con ese backfill —
queda documentado en el docblock de la migración). Actividades sin
Tareas con peso computable quedan en 1 por default, sin efecto real
(sin `avance()` propio, se excluyen igual).

### Página `TableroActividadesResumen`: tabla nativa, no un componente custom

A diferencia del árbol (`ActividadesRelationManager`, ADR 0019), acá el
pedido calza exacto con lo que `Filament\Tables\Table` ya sabe hacer:
Tareas agrupadas por Actividad (`->groups()`), select inline restringido
a transiciones válidas (`SelectColumn`, mismo patrón ya usado en
`ControlCambiosTable::opcionesEstadoDestino()`), peso de Tarea sumado por
grupo (`->summarize(Sum::make())`), peso de Actividad editable inline
(`TextInputColumn::make('actividad.peso')` con `updateStateUsing` propio
para escribir en el modelo padre), avance % calculado
(`status->valor() × 100`), y `->poll('10s')` para "tiempo real" sin
infraestructura nueva (WebSockets/broadcasting quedó descartado en
`/arquitecto` por costo de infraestructura vs. lo pedido).

**Deliberadamente sin CRUD completo** en esta página (crear/eliminar
Tarea, editar todos sus campos): reusar `EditAction` directo acá habría
ignorado en silencio la sincronización de predecesoras (`TareaForm` tiene
el campo virtual `predecessors`, que `EditAction` no sabe sincronizar sin
el `fillForm()`/`action()` custom que ya tiene
`ActividadesRelationManager::editarTareaAction()`) — ese CRUD completo ya
vive en el árbol, esta página es lectura + edición rápida (estado, pesos).

## Qué se creó/cambió

- `Modules/Inspeccion/database/migrations/2026_08_08_090000_add_peso_a_actividades_table.php`
- `Modules/Inspeccion/app/Models/Actividad.php` (`peso` fillable + cast)
- `Modules/Inspeccion/app/Filament/Resources/Tableros/Schemas/ActividadForm.php` (campo `peso`)
- `Modules/Inspeccion/app/Services/CalculadorAvanceTablero.php` (`calcularSobreActividades()` + `calcular()` con fallback)
- `Modules/Inspeccion/app/Filament/Resources/Tableros/Pages/TableroActividadesResumen.php` (nuevo) + blade
- `Modules/Inspeccion/app/Filament/Resources/Tableros/TableroResource.php` (ruta nueva), `EditTablero.php` (link)
- Tests: `TableroActividadesResumenTest.php`, `AddPesoAActividadesMigrationTest.php`, casos nuevos en `CalculadorAvanceTableroTest.php`

## Alternativas descartadas

- **Peso de Actividad independiente de sus Tareas** (asignación top-down
  tipo "esta fase vale 30% del proyecto"): descartado en `/arquitecto` —
  es un concepto distinto (no ponderar entre Actividades por su propio
  avance interno, sino una asignación arbitraria), fuera de lo pedido.
- **Backfill parejo (peso=1)**: descartado tras encontrar el error
  matemático arriba.
- **Broadcasting real (Echo/Reverb) para "tiempo real"**: descartado en
  `/arquitecto` — infraestructura nueva no justificada por el pedido,
  `poll('10s')` alcanza.
- **Reusar `EditAction` sin `fillForm` custom**: descartado por el bug
  real de predecesoras silenciosamente perdidas, explicado arriba.

## Siguiente paso

Correr `/revisor` — en particular que confirme la reducción algebraica
del backfill (que de verdad el avance_global no cambió en los 6 Tableros
de demo tras la migración) y la autorización de `TextInputColumn` de
`actividad.peso` contra intentos cruzando Tableros.
