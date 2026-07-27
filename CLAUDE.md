# CLAUDE.md — Inspección y Avance de Tableros (standalone → futuro módulo de Axon)

> Instrucciones persistentes para Claude Code en este repositorio. Léelas al
> inicio de cada sesión.

## 1. Qué es este proyecto

Aplicación Laravel + Filament, **acotada a un solo alcance**: reemplazar 3
planillas Excel (avance ponderado de fabricación de tableros eléctricos,
registro de observaciones de visitas de inspección de calidad, y control de
cambios de ingeniería) por un módulo funcional.

Este repo es temporal/aislado a propósito. Se construye como un **módulo
autocontenido** (`nwidart/laravel-modules`) para que, cuando esté validado,
se integre completo al PMIS real (`axon`, repo hermano) copiando la carpeta
`Modules/InspeccionTableros/` y registrando el módulo ahí. Por eso:

- Todo el código (modelos, migraciones, recursos Filament, rutas, config)
  vive DENTRO de `Modules/InspeccionTableros/`. Nada suelto en `app/` de la
  raíz.
- El documento de requerimiento completo (modelo de datos, lógica de negocio,
  criterios de aceptación) está en
  `Modules/InspeccionTableros/docs/requerimiento.md`. Léelo antes de proponer
  arquitectura — es el equivalente a un ADR + spec funcional en uno.

## 2. Stack

- PHP 8.2+ · Laravel 12 · Filament 4/5 (el que instale `filament/filament`
  actual).
- `maatwebsite/excel` para el import inicial de datos históricos desde los
  `.xlsx` originales.
- `nwidart/laravel-modules` para el aislamiento del módulo.
- Pest (tests), Pint (estilo), Larastan (análisis estático).
- SQLite para desarrollo local (no necesitas MySQL para este alcance acotado).
- UI en español (es-CL).

NO instales `spatie/laravel-permission` ni `filament-shield` acá — eso lo
aporta `axon` cuando se integre. Si algo del módulo necesita permisos,
diséñalo asumiendo que existirán roles/policies estándar de Laravel más
adelante (Gates simples por ahora, o Policies vacías/permisivas marcadas con
un `// TODO: reemplazar por policy real al integrar a axon`).

## 3. Principios de diseño (pensando en la integración futura a axon)

1. **`Tablero` cuelga de un `Proyecto`.** En este repo standalone, `Proyecto`
   es un modelo mínimo (id, nombre) — un stub. Al integrar a `axon`, la idea
   es que `Tablero.proyecto_id` apunte al `Proyecto` real de ese PMIS sin
   cambiar el esquema, solo re-apuntando la FK/seed.
2. **Catálogos configurables en BD, nunca enums de PHP hardcodeados**:
   estados de avance, tipo de observación, severidad, estado de cambio. Son
   tablas simples (`id`, `nombre`, `valor`/orden), administrables desde
   Filament.
3. **`organization_id` nullable en toda tabla nueva**, aunque no se use todavía
   en este repo standalone (siempre `null` o un solo valor fijo). Es gratis
   agregarlo ahora y evita una migración dolorosa al integrar a `axon`, que sí
   es multi-tenant-ready.
4. **Snapshot para el checklist de inspección** (catálogo de ítems IEC 61439 →
   ejecución por visita), no una tabla plana editable — así el histórico de
   una visita no cambia si después se edita el catálogo de ítems.
5. Prefiere paquetes maduros del ecosistema Laravel/Filament antes de construir
   a mano.

## 4. Método de trabajo

Igual que en `axon`:

- **Propón antes de implementar.** `/arquitecto` primero (diseño, sin
  código), esperas aprobación, luego `/ingeniero`.
- **Una unidad de trabajo = un commit/PR** con migraciones, modelos, recursos
  Filament, factories y tests Pest.
- **No cierres nada sin tests en verde** (`./vendor/bin/pest`) y Pint limpio.
- Commits pequeños y descriptivos. Nunca `git push --force`.
- Dado que este repo es transicional, evita over-engineering: resuelve el
  alcance del `requerimiento.md`, no el PMIS completo.

## 5. Roles por etapa

- **/arquitecto** — diseña modelo de datos, catálogos, recursos Filament. No
  escribe código de implementación.
- **/ingeniero** — implementa lo aprobado.
- **/qa** — solo pruebas (Pest), casos borde.
- **/revisor** — revisa el diff: N+1, validaciones faltantes, cobertura de
  tests.
- **/release** — changelog y checklist antes de "empaquetar" el módulo para
  llevarlo a `axon`.

## 6. Primer paso al iniciar

Si el repo está recién creado, adopta `/arquitecto` y:

1. Lee `Modules/InspeccionTableros/docs/requerimiento.md` completo.
2. Propón el diagrama de entidades (Tablero, TableroHito, VisitaInspeccion,
   Observacion, ControlCambio, catálogos, Checklist).
3. Propón la máquina de estados de `Observacion` y la fórmula de avance
   ponderado como método/servicio (no como lógica inline en el modelo).
4. Propón los recursos Filament necesarios dentro del módulo.

Lo revisamos juntos y, con visto bueno, implementamos.

## 7. Al terminar (empaquetado para integrar a axon)

Cuando el módulo esté funcional y probado:

1. `/release` prepara un resumen de qué se construyó y cómo se importan datos
   históricos.
2. Copiar `Modules/InspeccionTableros/` completo al repo `axon`.
3. En `axon`: registrar el módulo, ajustar el `proyecto_id` de `Tablero` para
   que apunte al `Proyecto` real (quitar el modelo stub de este repo),
   conectar `organization_id` al scope de tenant real, y reemplazar los
   Gates temporales por Policies reales con Shield.
4. Ese paso de integración se documenta como un ADR nuevo en `axon`
   (`docs/adr/000X-integracion-modulo-inspeccion-tableros.md`).
