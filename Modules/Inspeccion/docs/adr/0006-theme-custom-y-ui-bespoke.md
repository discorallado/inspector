# 0006 — Theme custom, UI bespoke y autonumeración (amplía el ADR 0003)

> Estado: aprobado (arquitectura). Pendiente de implementación.
> No reabre PR1 (ADR 0004) ni PR2 (ADR 0005), ya cerrados. Amplía y
> renumera el plan de PRs de
> [0003-rediseno-ux-seguimiento-terreno.md](0003-rediseno-ux-seguimiento-terreno.md) §6.

## 1. Contexto

Con PR1 y PR2 implementados, el usuario probó el panel y encontró dos problemas:

1. **El kanban se ve "solo texto"** pese a correr `npm run build`.
2. **El resto de la UI es el CRUD por defecto de Filament** (relation
   managers con tablas y modales para todo, campos de orden que se
   escriben a mano) — pidió explícitamente algo "increíble", moderno,
   fácil de usar en cualquier dispositivo, sin depender de relation
   managers para todo.

## 2. Diagnóstico (verificado, no hipótesis)

`app/Providers/Filament/AdminPanelProvider.php` no tiene `->viteTheme(...)`.
Filament sirve su CSS propio precompilado (`public/css/filament/filament/app.css`),
que solo contiene las clases que usan **sus propios componentes core**.
`relaticle/flowforge` trae vistas Blade con clases Tailwind arbitrarias que
nunca se compilaron en ningún stylesheet — de ahí el texto plano.
`npm run build` compila `resources/css/app.css`, pero el panel no sabe que
ese build existe. Esto afecta a **cualquier** vista custom futura (Vista
de Tablero, checklist táctil), no solo al kanban.

Confirmado en el código instalado: existe `Panel::viteTheme(string|array
$theme, ?string $buildDirectory = null)` (`vendor/filament/filament/src/Panel/Concerns/HasTheme.php`)
y el entrypoint estándar `vendor/filament/filament/resources/css/theme.css`
que trae consigo support/forms/tables/actions/infolists/schemas — es el
mecanismo soportado, no hay que inventar nada.

## 3. Decisiones

### 3.1 Theme custom (PR3, prerequisito)
`resources/css/filament/admin/theme.css` que importa el `theme.css` de
Filament y agrega directivas `@source` (Tailwind 4) apuntando a
`app/Filament`, `Modules/Inspeccion/app/Filament`, las vistas Blade
custom del módulo, y `vendor/relaticle/flowforge/resources/views`.
Registrado con `->viteTheme(...)` en `AdminPanelProvider`. **Retroactivo**:
arregla la apariencia de PR1/PR2 sin tocar su código.

### 3.2 Paleta de color — diferida a propósito
El usuario prefirió no fijar colores todavía. El theme se construye con
**tokens de diseño** (variables CSS semánticas: `--color-primario`,
`--color-exito`, `--color-alerta`, `--color-peligro`, no clases Tailwind
de color hardcodeadas dispersas en cada vista) para que definir la
paleta después sea cambiar un archivo, no perseguir cada componente.

### 3.3 Lenguaje visual — bespoke marcado
Aplica a las pantallas custom del ADR 0003 (Centro de Seguimiento, Vista
de Tablero, checklist táctil, cards del kanban vía `cardSchema`): cards
con elevación real (sombra, no solo borde), indicadores de progreso
circulares (no solo barras), tipografía grande or touch targets ≥44px,
codificación de color por estado consistente en toda la app (mismo verde
= completado/aprobado/cumple en cualquier pantalla), espaciado generoso
pensado para uso con guantes/dedo en tablet, no mouse de escritorio.

### 3.4 Checklist táctil (PR8, redefine el checklist del ADR 0003)
Reemplaza `ChecklistEjecucions/RelationManagers/ItemsRelationManager.php`
(hoy: modal por ítem) por una página propia: lista scrolleable de todos
los ítems agrupados por `categoria`, cada uno con 3 botones grandes
(Cumple/No Cumple/N.A.) inline — sin modal. Permite saltar libremente
entre ítems (una inspección real no siempre se revisa en orden estricto).
Al marcar "No Cumple" se despliega inline el campo de observación (no
un form separado).

### 3.5 Autonumeración — por campo

| Campo | Decisión |
|---|---|
| `TableroHito.peso` | Sin cambios — es un valor de negocio (peso relativo para avance ponderado), no un orden. |
| `TableroHito.item` | **Recalcular los 234 existentes + autogenerar los nuevos.** Algoritmo: para cada `grupo_hito` (ordenado por `orden`), tomar sus hitos ordenados por su **valor actual** de `item` (preserva la secuencia relativa ya correcta) y renumerar como `{grupo.orden}.{posición secuencial}`. Es una migración de datos (backfill), no solo un cambio de UI — corre una vez, después el campo queda de solo lectura en el form. Verificado: ningún test hardcodea un código de hito específico; el algoritmo reproduce exactamente "1.1".."8.4" para los datos ya importados (mismo orden de grupo + posición que ya tienen), así que el backfill es un no-op visible para los datos actuales — el valor cambia de "escrito a mano" a "calculado", no de contenido. |
| `ChecklistTemplateItems.orden` | Autocalcular (max+1) al adjuntar + `->reorderable('orden')` en la tabla. Sacar el `TextInput` del form de `AttachAction`. |
| `orden` en los 9 catálogos simples (GrupoHito, Severidad, EstadoObservacion, EstadoAvance, EstadoCambio, TipoObservacion, Especialidad, ResultadoChecklist, ChecklistItemLibrary) | Mismo tratamiento: autocalcular en creación, `->reorderable('orden')` en cada tabla, sacar el `TextInput` de los forms. |

## 4. Plan de PRs (reemplaza al del ADR 0003 §6 de PR3 en adelante)

1. ~~PR1~~ — Kanban Observaciones. **Cerrado** (ADR 0004).
2. ~~PR2~~ — Kanban Control de Cambios. **Cerrado** (ADR 0005).
3. **PR3** — Theme custom Filament + Tailwind 4 (§3.1, §3.2).
4. **PR4** — Autonumeración de los 10 campos de §3.5, incluyendo el
   backfill de `TableroHito.item`.
5. **PR5** — Vista de Tablero (sin Gantt).
6. **PR6** — Gantt interactivo.
7. **PR7** — Centro de Seguimiento (mayor superficie para el lenguaje
   visual bespoke de §3.3).
8. **PR8** — Checklist táctil (§3.4).
9. **PR9** — Ajustes de navegación/CSS terreno — probablemente se achica,
   buena parte queda cubierta por PR3.
10. **PR10** — Reescritura de smoke tests existentes.

## 5. Alternativas descartadas

| Decisión | Alternativa descartada | Por qué |
|---|---|---|
| Checklist táctil | Wizard tipo app (un ítem a la vez, avance automático) | El usuario lo prefirió por permitir saltar libremente entre ítems — una inspección real no siempre es lineal |
| `TableroHito.item` | Dejarlo manual sin tocar | El usuario pidió explícitamente que la numeración sea automática, incluso a costa de recalcular el histórico |
| `TableroHito.item` | Autocalcular solo hitos nuevos, dejar los 234 históricos tal cual | El usuario prefirió consistencia total del sistema por sobre preservar el dato "tal cual venía de la planilla" |
| Paleta de color | Definir colores de marca ahora | Usuario prefirió diferir — se diseña con tokens para no bloquear el resto del trabajo |

## 6. Riesgos y supuestos

- El backfill de `TableroHito.item` debe correr dentro de una migración
  (o comando) idempotente y probarse contra MariaDB real antes de
  cerrarse, no solo SQLite — este proyecto ya se cruzó dos veces con
  bugs que solo aparecen en MariaDB.
- El theme custom (PR3) es career-path para todo lo demás: si algo sale
  mal ahí (paths de `@source` incompletos, por ejemplo), todas las
  pantallas custom posteriores heredan el problema. Vale la pena
  verificar visualmente (no solo que compile) antes de dar PR3 por
  cerrado.
- La paleta de color queda como placeholder/tokens hasta que el usuario
  la defina — no bloquea el resto del plan, pero alguien tiene que
  acordarse de volver a este punto.

---

Con esto el diseño queda cerrado. Corresponde `/ingeniero` para implementar
**PR3 primero** (es el prerequisito de todo lo demás), y registrar su
propio ADR en `docs/adr/` al cerrarlo — igual que se hizo con PR1/PR2.
