<?php

use Illuminate\Database\QueryException;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\Tarea;

/**
 * Hallazgo de /revisor sobre PR4 (ADR 0010): Actividad/Tarea van a
 * acumular trabajo real del usuario (Kanban/Gantt en PR7/PR8) — mismo
 * riesgo de cascada de borrado física que ya se corrigió para el
 * historial de calidad (Observacion/ControlCambio/etc).
 */
beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
});

it('el borrado de una Actividad es lógico, no físico', function () {
    $actividad = Actividad::factory()->create();

    $actividad->delete();

    expect($actividad->trashed())->toBeTrue();
    expect(Actividad::withTrashed()->find($actividad->id))->not->toBeNull();
});

it('el borrado de una Tarea es lógico, no físico', function () {
    $tarea = Tarea::factory()->create();

    $tarea->delete();

    expect($tarea->trashed())->toBeTrue();
    expect(Tarea::withTrashed()->find($tarea->id))->not->toBeNull();
});

it('no se puede borrar físicamente un Tablero con Actividades, ni soft-deleted', function () {
    $tablero = Tablero::factory()->for(Proyecto::factory())->create();
    $actividad = Actividad::factory()->for($tablero)->create();
    $actividad->delete();

    expect(fn () => $tablero->delete())->toThrow(QueryException::class);
});

it('no se puede borrar físicamente (forceDelete) una Actividad con Tareas, ni soft-deleted', function () {
    // Actividad también tiene SoftDeletes: delete() normal jamás dispara
    // la FK (es un UPDATE de deleted_at, no un DELETE real) — la
    // protección real se prueba contra forceDelete().
    $actividad = Actividad::factory()->create();
    $tarea = Tarea::factory()->for($actividad)->create();
    $tarea->delete();

    expect(fn () => $actividad->forceDelete())->toThrow(QueryException::class);
});
