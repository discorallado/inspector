# 0002 — Módulo de Seguimiento de Fabricación e Inspección de Tableros Eléctricos

> Estado: propuesto. Este documento describe el modelo de datos y la lógica de
> negocio de una herramienta que hoy vive en 3 planillas Excel separadas, para
> que se diseñe e implemente sobre la base técnica del PMIS (sección 3 de
> CLAUDE.md). Es el insumo para `/arquitecto`.

## 1. Alcance

Reemplazar 3 planillas Excel manuales por un módulo del PMIS que cubra, para
proyectos de fabricación/integración de tableros eléctricos (ej. data centers):

1. **Seguimiento de avance ponderado** por tablero, con hitos/sub-actividades
   pesados y carta Gantt.
2. **Registro de observaciones de visitas de inspección de calidad** (no solo
   no conformidades: también consultas al integrador y sugerencias).
3. **Checklist de inspección en terreno** basado en IEC 61439.
4. **Control de cambios** de ingeniería/fabricación por tablero.

Las 3 planillas ya comparten el mismo set de tableros por proyecto (ej. `TP`,
`T_G2`, `BUS_A`, `BUS_B`, `CLIMA_A`, `CLIMA_B`), lo que confirma que `Tablero`
debe ser una entidad propia, no un campo de texto libre.

## 2. Contexto de negocio

- Cada `Proyecto` (ya existente en el PMIS) puede tener uno o más `Tablero`
  (paneles eléctricos) fabricados por un integrador/fabricante externo.
- Cada `Tablero` tiene un cronograma propio de hitos de fabricación
  (recepción de materiales, armado de estructura, cableado, pruebas FAT,
  despacho, etc.), cada uno con un **peso** relativo para calcular el avance
  ponderado global del tablero.
- Un supervisor de CS Energy visita periódicamente (hoy: semanal) la planta
  del integrador y levanta hallazgos. No todo hallazgo es una no conformidad:
  puede ser una **consulta** que el integrador plantea y necesita respuesta,
  una **sugerencia** de mejora, o una **observación a subsanar** (con
  severidad Crítica/Mayor/Menor).
- Además de esto, en la fabricación pueden proponerse **cambios de ingeniería**
  respecto del diseño original, que siguen su propio flujo de aprobación
  (Propuesto → Aprobado/Rechazado → Implementado).
- Todo esto debe verse consolidado en un dashboard por tablero y por proyecto.

## 3. Modelo de datos propuesto

> Nombres tentativos; `/arquitecto` puede ajustarlos a las convenciones del
> proyecto. Todas las entidades llevan `organization_id` (heredado o directo)
> según los principios de la sección 3 de CLAUDE.md.

### 3.1 `Tablero`
Cuelga de `Proyecto` (relación existente en el PMIS).

| Campo | Tipo | Notas |
|---|---|---|
| `proyecto_id` | FK | |
| `tag` | string | Ej. `TP`, `T_G2`, `BUS_A`. Único por proyecto. |
| `nombre` | string | Descripción legible |
| `fabricante` | string | Integrador/fabricante externo |
| `oc_contrato` | string | Referencia de OC/contrato |
| `avance_global` | **calculado**, no columna | Ver §4 |

### 3.2 `TableroHito` (sub-actividad del cronograma de fabricación)
Equivalente a las filas de las hojas `TP`, `T_G2`, etc. de la planilla de
avance.

| Campo | Tipo | Notas |
|---|---|---|
| `tablero_id` | FK | |
| `item` | string | Ej. `1.3` (numeración jerárquica) |
| `nombre` | string | Ej. "Montaje de perfilería DIN y placas de montaje" |
| `peso` | decimal | Peso relativo dentro de su grupo |
| `grupo` | string/FK | Ej. "1. Armado de Tablero" — agrupador de hitos |
| `estado_id` | FK a `estado_avance` (catálogo, ver §3.6) | Pendiente/En proceso/Completado/N.A. |
| `plan_inicio`, `plan_fin` | date | |
| `real_inicio`, `real_fin` | date nullable | |
| `responsable` | string o FK a usuario/contacto externo | |
| `observaciones` | text nullable | Nota libre del hito |

### 3.3 `VisitaInspeccion`
Equivalente a la hoja "Registro Visitas".

| Campo | Tipo | Notas |
|---|---|---|
| `proyecto_id` | FK | |
| `fecha` | date | |
| `inspector` | FK a usuario | |
| `tableros_visitados` | relación N:N con `Tablero` (o derivarlo desde las observaciones asociadas) |
| `observaciones_generales` | text nullable | |
| `estado_general` | **calculado** | Derivado del conteo de observaciones pendientes asociadas |

### 3.4 `Observacion`
Equivalente a la hoja "Observaciones" — **la entidad central**, reemplaza y
generaliza a lo que hoy son planillas separadas de "No Conformidades".

| Campo | Tipo | Notas |
|---|---|---|
| `visita_id` | FK a `VisitaInspeccion` | |
| `tablero_id` | FK nullable | Nullable = observación general/varios |
| `tablero_hito_id` | FK nullable | Referencia opcional al hito del cronograma (trazabilidad) |
| `especialidad` | FK a catálogo (`Eléctrico`, `Mecánico`, `Control`, `Documentación`, `HSE`, `Otro`) | |
| `tipo` | enum/FK | `consulta_integrador` \| `sugerencia` \| `observacion_subsanar` |
| `clasificacion` | FK a catálogo, nullable | `Crítica`/`Mayor`/`Menor` — **solo aplica si `tipo = observacion_subsanar`** |
| `descripcion` | text | |
| `responsable` | string o FK | |
| `fecha_compromiso` | date nullable | |
| `estado` | FK a catálogo | `Pendiente` / `Subsanada (OK)` / `Informativa` — ver §3.6 |
| `fecha_cierre` | date nullable | |
| `observacion_cierre` | text nullable | |
| `dias_abierta` | **calculado** | `fecha_cierre - fecha_deteccion` o `hoy - fecha_deteccion` |

Nota de diseño: **no crear un modelo `NoConformidad` separado.** Una NC es
simplemente `Observacion` con `tipo = observacion_subsanar`. Esto evita
duplicar el tracking que hoy existe repartido en 2 planillas distintas.

### 3.5 `ControlCambio`

| Campo | Tipo | Notas |
|---|---|---|
| `tablero_id` | FK | |
| `descripcion` | text | |
| `estado` | FK a catálogo | `Propuesto`/`Aprobado`/`Rechazado`/`Implementado` |
| `responsable` | string o FK | |
| `fecha` | date | |

### 3.6 Catálogos configurables (tabla, NO enum de PHP)

Siguiendo el principio 4 de CLAUDE.md ("entidades extensibles... nunca
hardcodeadas"), estos catálogos van en BD, administrables desde Filament:

- `estado_avance`: `Pendiente` (0%), `En proceso` (50%), `Completado` (100%),
  `N/A` (excluido del cálculo). Value = peso de avance usado en la fórmula.
- `estado_observacion`: `Pendiente` / `Subsanada (OK)` / `Informativa`.
- `tipo_observacion`: `Consulta al Integrador` / `Sugerencia` / `Observación a Subsanar`.
- `severidad`: `Crítica` / `Mayor` / `Menor`.
- `estado_cambio`: `Propuesto` / `Aprobado` / `Rechazado` / `Implementado`.

### 3.7 Checklist de inspección (IEC 61439)

Reutilizar el **mismo patrón ya construido en Axon PMS para el FAT
checklist** (arquitectura snapshot: librería → plantilla → ejecución). Acá:

- `ChecklistItemLibrary`: catálogo maestro de ítems (categoría, ítem,
  referencia normativa IEC 61439).
- `ChecklistTemplate`: selección de ítems aplicable (podría ser único, ya que
  hoy es un solo checklist estándar).
- `ChecklistEjecucion`: snapshot al momento de una `VisitaInspeccion`
  (tablero, resultado por ítem: Cumple/No Cumple/N.A., observación).

## 4. Lógica de negocio

**Avance ponderado de un tablero:**
```
avance_tablero = Σ(peso_hito × valor_estado_hito) / Σ(peso_hito)
                 [excluyendo hitos con estado = N/A]
```
Igual fórmula ya existe en la planilla actual (`TP!G7` y análogas); se debe
recalcular en tiempo real (accessor/observer) o cachear con invalidación al
guardar un `TableroHito`.

**Estado general de una visita:**
`Sin Observaciones` / `Todo Cerrado` / `Con Pendientes` / `Pendientes
Críticos`, derivado del conteo de `Observacion` con `estado = Pendiente`
asociadas a esa visita (igual lógica que hoy en "Registro Visitas").

**Resumen de subsanadas:** vista/reporte (no requiere modelo nuevo) que
filtra `Observacion` por `estado = Subsanada (OK)`.

**Dashboard por tablero:** total observaciones, pendientes, vencidas
(`fecha_compromiso < hoy` y `estado = Pendiente`), subsanadas, consultas
pendientes, sugerencias pendientes, cambios pendientes — todo por `COUNT`/
`filter` sobre `Observacion` y `ControlCambio` agrupado por `tablero_id`.

## 5. Pantallas Filament sugeridas

- Recurso `Tablero` (dentro del contexto de `Proyecto`), con tab/relation
  manager de `TableroHito` (editable como tabla, similar a la hoja actual).
- Recurso `VisitaInspeccion` con relation manager de `Observacion` (alta
  rápida en terreno, idealmente usable desde PWA/móvil a futuro).
- Recurso `Observacion` con filtros por tipo/estado/tablero/especialidad y
  kanban o tabla agrupada.
- Widget de dashboard por proyecto: avance por tablero (barra), observaciones
  por tipo/estado, cambios pendientes.
- Recurso `ControlCambio`.

## 6. Fuera de alcance (por ahora)

- Vínculo dependiente Tablero→Hito en el dropdown de `Observacion` (Excel no
  lo tenía tampoco; el campo `tablero_hito_id` puede llenarse manual al
  inicio).
- Portal externo para que el integrador vea/responda consultas directamente
  (podría ser un requerimiento futuro que reuse el patrón de portal por token
  ya contemplado en el roadmap del PMIS).
- Firma/hash de integridad en observaciones (si se decide más adelante, seguir
  el mismo patrón ya usado en el FAT checklist de Axon PMS).

## 7. Criterios de aceptación

- [ ] Un `Tablero` puede crearse dentro de un `Proyecto` con sus `TableroHito`.
- [ ] El avance ponderado del tablero se recalcula solo al cambiar el estado
      de un hito.
- [ ] Se puede registrar una `VisitaInspeccion` y agregar N `Observacion`
      asociadas, cada una con su tipo (consulta/sugerencia/observación) y,
      si aplica, severidad.
- [ ] Un dashboard por proyecto muestra, por tablero: avance %, observaciones
      pendientes por tipo, vencidas, y cambios pendientes.
- [ ] Los catálogos (estados, tipos, severidad) son editables desde Filament,
      no están hardcodeados.
- [ ] Import inicial de datos históricos desde los `.xlsx` actuales (usar
      `maatwebsite/excel`; puede ser un comando artisan de una sola vez, no
      una feature permanente).
- [ ] Tests Pest para: cálculo de avance ponderado, cálculo de estado general
      de visita, filtrado de observaciones vencidas.

## 8. Referencia de archivos fuente

Los 3 archivos Excel que originan este requerimiento (para consulta del
detalle exacto de columnas/fórmulas al implementar el importador):

- `Control_Inspecciones_Calidad_IFX.xlsx` — checklist + NC (versión inicial).
- `Control_Observaciones_Visitas_IFX.xlsx` — registro de observaciones con
  tipo Consulta/Sugerencia/Observación.
- `Seguimiento_Integracion_Tableros_Integrado.xlsx` — avance ponderado por
  tablero + Gantt + el registro de observaciones ya integrado.
