# 0009 — Integración profunda: Tablero + Actividad/Tarea portado desde axon

> Estado: aprobado (arquitectura). Pendiente de implementación en PR4-PR9
> (renumera y reemplaza el PR4 pendiente del ADR 0006).

## 1. Contexto

El usuario tiene, en el repo hermano `axon` (`/home/ubuntu/axon`, últimos
cambios recién mergeados a `main` durante esta sesión), una jerarquía
`Proyecto -> Actividad -> Tarea` madura: Kanban (Livewire + SortableJS),
Gantt (DHTMLX, con dependencias vía `TaskLink`), subtareas, asignados,
multi-tenancy real. Pidió explícitamente integrar el seguimiento de
avance de `Tablero` con ese modelo, en vez de seguir construyendo el
kanban de hitos que había quedado diferido en el ADR 0008 §5, para no
duplicar trabajo cuando este módulo se integre a `axon` (CLAUDE.md §7).

De las 3 alternativas presentadas (independiente / integración profunda /
híbrida), el usuario eligió la integración profunda: `Actividad`/`Tarea`
nuevas en Inspeccion, portadas (copiadas y adaptadas, no importadas en
runtime — son apps Laravel separadas) desde el código real de axon.

## 2. Decisión

### 2.1 Nombres — español, consistente con el resto de Inspeccion
`Actividad`/`Tarea` (no `Activity`/`Task`). Al integrar a axon en el
futuro va a hacer falta un rename, pero es mecánico (namespace/nombre de
clase), no una reescritura de lógica de negocio.

### 2.2 Modelo de datos

| Tabla | Columnas | Notas |
|---|---|---|
| `actividades` | `id`, `organization_id` (nullable), `tablero_id` (FK, obligatoria), `nombre`, `descripcion`, `orden`, `start_date`, `end_date` | **Solo `tablero_id`**, sin `proyecto_id` duplicado — se deriva vía `tablero->proyecto_id`. Nombres de fecha literales de axon (no `plan_inicio`/`plan_fin`), a propósito, para portabilidad. |
| `tareas` | `id`, `organization_id`, `actividad_id`, `parent_tarea_id` (nullable), `code`, `nombre`, `descripcion`, `status`, `priority`, `orden`, `start_date`, `due_date`, `completed_at`, `estimated_hours`, `actual_hours`, **`peso` (decimal, nullable)**, **`real_inicio`/`real_fin` (date, nullable)** | `peso` y `real_inicio`/`real_fin` son extensión de Inspeccion — axon no las tiene hoy. Nullable: no rompen la compatibilidad de un futuro merge, los `Project` de axon sin tablero las dejan en null. |
| `tarea_links` | `id`, `organization_id`, `source_id`, `target_id`, `type` | Espejo de `TaskLink` de axon, para dependencias del Gantt. |

`CalculadorAvanceTablero` se adapta para sumar sobre `tareas.peso` en vez
de `tablero_hitos.peso` — misma fórmula, mismo servicio.

### 2.3 Máquina de estados — `TransicionEstadoGuard` generalizado
axon no valida transiciones de `TaskStatus` (cualquier salto es válido).
Inspeccion sí las valida vía `TransicionEstadoGuard` + tabla
`transiciones_estado_permitidas`, hoy solo con IDs de catálogo. Se
generaliza el guard para aceptar también códigos string (el `TaskStatus`
de `Tarea` es un enum, no una tabla de catálogo) — reutiliza la misma
validación ya probada en vez de dejar `Tarea` sin control de máquina de
estados.

### 2.4 Kanban y Gantt — portados de axon, adaptados al scope de Tablero
- `TableroKanbanBoard`: mismo patrón que `KanbanBoard.php` de axon
  (Livewire + SortableJS vía CDN), filtrado por `tablero_id` (acá
  `Tablero` ya es el nivel que scopea, no hace falta filtro de actividad
  suelto como en axon).
- `TableroGanttChart`: mismo patrón que `GanttChart.php` de axon
  (DHTMLX Gantt, dependencias vía `tarea_links`, zoom, dark mode).
  **Riesgo de licencia/CDN aceptado como no bloqueante** (ver §4) — se
  porta tal cual está en axon hoy.
- El theme custom (PR3, ADR 0007) ya cubre `Modules/Inspeccion/app/Filament`
  y `resources/views` — el Kanban (Tailwind puro) hereda estilos sin
  repetir el problema de flowforge. DHTMLX carga su propio CSS por CDN,
  independiente del pipeline de Tailwind.

### 2.5 Migración de datos existentes (234 hitos)
No se borra nada de entrada:
1. Comando de migración de datos: `TableroHito` -> `Actividad`/`Tarea`
   (cada `GrupoHito` se convierte en una `Actividad` por tablero, cada
   hito en una `Tarea` con `peso` preservado, `EstadoAvance.codigo`
   mapeado al `TaskStatus` equivalente).
2. `TableroHito`/`GrupoHito`/`EstadoAvance` quedan **deprecados, no
   borrados**, hasta validar en uso real — se sacan en un PR de limpieza
   aparte (PR9).

## 3. Recursos y páginas Filament

- `ActividadesRelationManager` bajo `Tablero` (reemplaza
  `TableroHitosRelationManager`), estilo accordion como el de axon.
- `TableroKanbanBoard` y `TableroGanttChart`: páginas adicionales de
  `TableroResource` (rutas `/kanban` y `/gantt`).

## 4. Matriz de permisos

| Ability | Roles |
|---|---|
| `tablero_actividad.gestionar` | super_admin, ingeniero |
| `tablero_tarea.actualizar` | super_admin, ingeniero, tecnico |
| `tablero_tarea.asignar` | super_admin, ingeniero, supervisor |

## 5. Hallazgo sobre DHTMLX (riesgo aceptado, no bloqueante)

El ADR-0010 de axon **rechazó explícitamente DHTMLX** ("comercial, viola
la regla open-source") y decidió frappe-gantt — pero el código real
(`REQ-0002-E`, posterior) usa DHTMLX de todos modos, sin un ADR que
documente el cambio de rumbo. Investigado: desde DHTMLX Gantt v10, la
edición "Community" es MIT (antes GPL) — no es necesariamente una
violación de la regla open-source, el ADR-0010 puede estar desactualizado.
Pero el CDN que usa axon (`cdn.dhtmlx.com/gantt/edge/...`) no fija
versión/edición explícita, y ese path puede sar un build de evaluación
PRO con marca de agua. El usuario decidió portarlo igual y resolver esto
después — **queda como deuda pendiente antes de llevar esto a
producción real**, no bloquea este PR.

## 6. Alternativas descartadas

| Decisión | Alternativa descartada | Por qué |
|---|---|---|
| Integración profunda (Opción A) | Independiente (Opción B) / híbrida (Opción C) | El usuario priorizó reutilizar el Kanban/Gantt ya maduro de axon sobre construir uno propio para `TableroHito` |
| `Actividad`/`Tarea` en español | `Activity`/`Task` en inglés | Consistencia con el resto del módulo (`Observacion`, `ControlCambio`, `Tablero`) por sobre cero-rename al integrar |
| `tablero_id` único en `Actividad` | `tablero_id` + `proyecto_id` duplicado | Menos redundancia; `proyecto_id` se deriva cuando hace falta |
| Generalizar `TransicionEstadoGuard` | Sin guard, como axon | Inspeccion ya construyó y probó una máquina de estados genérica — no tiene sentido perder esa validación para `Tarea` específicamente |

## 7. Riesgos y supuestos

- No hay forma de probar esto contra axon en vivo desde este entorno —
  el port se valida solo contra el comportamiento de Inspeccion (Pest,
  MariaDB real).
- axon sigue evolucionando mientras este port es una foto fija del
  commit `25f8fc4` — la integración final a axon va a necesitar
  reconciliación real, no un merge automático. Costo aceptado de la
  estrategia "standalone ahora, integrar al final" (CLAUDE.md §7).
- El PR4 original del ADR 0006 (autonumeración de `TableroHito.item`)
  queda **cancelado** — la entidad se depreca. La autonumeración de los
  otros 9 catálogos simples sigue en pie, sin relación con esto.
- Riesgo de licencia DHTMLX (§5): aceptado, no resuelto.

## 8. Plan de PRs (reemplaza el PR4 pendiente del ADR 0006)

1. **PR4** — Migraciones + modelos `Actividad`/`Tarea`/`TareaLink`,
   generalización de `TransicionEstadoGuard`, seeds de transición para
   `TaskStatus`.
2. **PR5** — Comando de migración de datos (234 hitos -> Actividad/Tarea).
3. **PR6** — `ActividadesRelationManager` + `CalculadorAvanceTablero`
   adaptado a `Tarea.peso`.
4. **PR7** — Kanban de `Tarea` (SortableJS portado de axon).
5. **PR8** — Gantt de `Tarea` (DHTMLX portado de axon).
6. **PR9** — Cleanup: drop de `TableroHito`/`GrupoHito`/`EstadoAvance`.

---

Con esto el diseño queda cerrado. Corresponde `/ingeniero` para
implementar **PR4 primero**, y registrar su propio ADR en `docs/adr/` al
cerrarlo — igual que se hizo con los PRs anteriores.
