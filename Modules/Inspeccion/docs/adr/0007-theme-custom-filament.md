# 0007 — Theme custom de Filament (PR3 del ADR 0006)

> Estado: implementado. Tercer PR del plan de 10 definido en
> [0006-theme-custom-y-ui-bespoke.md](0006-theme-custom-y-ui-bespoke.md) §4.

## Contexto

El panel admin renderizaba el kanban de Observaciones/Control de Cambios
(PR1/PR2) "solo texto" — las clases Tailwind que usan las vistas Blade de
`relaticle/flowforge` nunca se compilaban a ningún stylesheet, porque
`AdminPanelProvider` nunca configuró un theme custom (`->viteTheme(...)`)
y Filament sirve por defecto solo su propio CSS precompilado (que
contiene únicamente las clases que sus propios componentes core usan).
Diagnóstico completo en el ADR 0006 §2.

## Decisión

- **`resources/css/filament/admin/theme.css`**: importa
  `vendor/filament/filament/resources/css/theme.css` (trae consigo
  support/forms/tables/actions/infolists/schemas/widgets) y agrega
  directivas `@source` (Tailwind 4) apuntando a `app/Filament`,
  `Modules/Inspeccion/app/Filament`, `Modules/Inspeccion/resources/views`
  (para páginas custom futuras del ADR 0006, aunque hoy esa carpeta casi
  no tiene contenido) y `vendor/relaticle/flowforge/resources/views`.
- **`AdminPanelProvider::panel()`**: agrega
  `->viteTheme('resources/css/filament/admin/theme.css')`.
- **`vite.config.js`**: se agrega `resources/css/filament/admin/theme.css`
  al array `input` de `laravel-vite-plugin` — sin esto, Vite nunca lo
  compila aunque el PanelProvider lo referencie.
- **Paleta de color**: sin cambios, sigue en `->colors(['primary' =>
  Color::Amber])`. Es intencional (ADR 0006 §3.2) — Filament ya genera
  variables CSS (`--primary-500`, etc.) a partir de ahí, que es
  exactamente el mecanismo de "un solo punto de cambio" que se necesita;
  no se creó un sistema de tokens paralelo.

## Bug encontrado durante la implementación (no de diseño, de ejecución)

El primer intento de build falló con `CssSyntaxError: Invalid custom
property, expected a value`. Causa: un comentario CSS propio contenía la
secuencia literal `--primary-*/--success-*` — el `*/` ahí adentro **cierra
el comentario antes de tiempo**, y el resto de esa línea se parseó como
CSS real. Se reescribió el comentario para no usar `*/` como separador de
ejemplos. Se aisló la causa incrementalmente (build de un archivo mínimo
solo con el `@import`, después agregando cada `@source` de a uno) antes
de asumir que el problema era de configuración — no lo era, era un error
de redacción propio dentro de un comentario.

## Verificación

- `npm run build` compila `resources/css/filament/admin/theme.css` a
  ~632KB (vs. ~621KB sin los `@source` del módulo/flowforge — confirma
  que sí está escaneando esos paths y no solo reimportando el CSS base
  de Filament).
- 2 tests nuevos (`ThemeCustomTest.php`) verifican que el HTML real que
  devuelve el servidor —no solo que el build no falle— referencia el
  asset compilado (`build/assets/theme-*.css`), tanto en `/admin` como
  específicamente en el kanban de Observaciones (el caso que originó el
  reporte). Sin este segundo nivel de verificación, un `->viteTheme()`
  mal registrado (o un theme que compila pero nunca se linkea) habría
  pasado desapercibido.
- Suite completa: 99 passed, 3 risky (preexistentes, sin fallas), Pint
  limpio.

## Alternativas descartadas

| Alternativa | Por qué se descartó |
|---|---|
| Definir un sistema de tokens CSS propio (`--color-primario`, etc.) para la paleta diferida | Filament ya expone exactamente ese mecanismo vía `->colors()`; duplicarlo hubiera sido indirección sin beneficio |
| `@source` apuntando solo a `flowforge` (sin incluir `Modules/Inspeccion/resources/views`) | El ADR 0006 ya planea páginas custom (checklist táctil, Vista de Tablero) que van a necesitar ese path — agregarlo ahora evita un PR de "agregar un `@source` que faltaba" más adelante |

## Riesgos y supuestos

- Cualquier vista Blade nueva del módulo que use clases Tailwind fuera de
  los paths ya cubiertos por `@source` (por ejemplo, si se decide poner
  vistas custom en otra carpeta) va a repetir el mismo síntoma — hay que
  acordarse de este archivo al agregar rutas de vistas nuevas.
- El build de producción (`npm run build`) hay que volver a correrlo
  después de este cambio en cualquier ambiente además de este; no se
  automatizó como parte del deploy en este PR.

## Siguiente paso

Correr `/revisor` sobre este diff antes de abrir el PR. Luego, PR4 del
ADR 0006: autonumeración de los 10 campos de orden manual + backfill de
`TableroHito.item`.
