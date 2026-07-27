# Inspección y Avance de Tableros

Aplicación Laravel + Filament que reemplaza 3 planillas Excel de un proyecto
de fabricación/integración de tableros eléctricos:

1. Avance ponderado de fabricación por tablero (hitos + Gantt).
2. Registro de observaciones de visitas de inspección de calidad (no
   conformidades, consultas al integrador, sugerencias).
3. Control de cambios de ingeniería/fabricación.

Todo el código vive dentro de `Modules/Inspeccion/` (paquete
`nwidart/laravel-modules`). Este repo es **temporal/aislado a propósito**: una
vez validado, el módulo se copia completo al PMIS real (`axon`). El contexto
completo de esta decisión y las convenciones de trabajo están en
[`CLAUDE.md`](CLAUDE.md).

## Documentación

- Requerimiento funcional original: [`Modules/Inspeccion/docs/0002-seguimiento-inspeccion-tableros.md`](Modules/Inspeccion/docs/0002-seguimiento-inspeccion-tableros.md)
- Decisiones de arquitectura (ADR): [`docs/adr/`](docs/adr/)

## Stack

- PHP 8.3+ · Laravel 13 · Filament 5
- `nwidart/laravel-modules` para el aislamiento del módulo
- Pest (tests), Pint (estilo)
- SQLite para desarrollo local

## Puesta en marcha

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

El panel de administración queda en `/admin`.

## Navegación del panel

| Menú | Contenido |
|---|---|
| **Tableros** | Avance ponderado por tablero, hitos, cambios y observaciones asociadas. `Proyecto` (stub) se crea inline desde el selector. |
| **Inspección de Calidad** | Observaciones/sugerencias/consultas (vista principal) y Visitas de Inspección con su checklist IEC 61439. |
| **Control de Cambios** | Flujo Propuesto → Aprobado/Rechazado → Implementado. |
| **Configuración** | Catálogos del sistema (estados, tipos, severidades, checklist maestro, transiciones permitidas). Solo `super_admin`. |

## Roles

`super_admin`, `ingeniero`, `supervisor`, `tecnico`, `calidad`. La matriz de
permisos vive en `Modules/Inspeccion/config/inspeccion.php` (Gates simples,
no hay paquete de permisos en este repo — ver `CLAUDE.md` §2).

## Tests y estilo

```bash
./vendor/bin/pest
./vendor/bin/pint
```
