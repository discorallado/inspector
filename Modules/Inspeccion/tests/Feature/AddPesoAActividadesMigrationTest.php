<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\Tarea;

/**
 * Backfill de la migración 2026_08_08_090000_add_peso_a_actividades_table:
 * cada Actividad existente recibe como peso la SUMA del peso de sus
 * Tareas no excluidas (no un valor parejo) — es lo único que deja el
 * avance_global sin saltar el día del deploy (ver el ADR de peso
 * ponderado). Se prueba haciendo rollback + re-migrate con datos reales
 * ya creados, mismo patrón que RenombraChecklistAPruebaMigrationTest.
 */
it('el backfill de actividades.peso suma el peso de las Tareas no excluidas de cada Actividad', function () {
    $tablero = Tablero::factory()->for(Proyecto::factory())->create();
    $actividad = Actividad::factory()->for($tablero)->create();

    Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create(['peso' => 10]));
    Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create(['peso' => 15]));
    Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create(['peso' => 100, 'excluye_calculo' => true]));
    Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create(['peso' => null]));

    $actividadSinTareas = Actividad::factory()->for($tablero)->create();

    Artisan::call('migrate:rollback', ['--step' => 1]);
    expect(Artisan::output())->not->toContain('Exception');

    // El rollback dropea la columna — sin ella el fillable 'peso' del
    // modelo no aplica, hay que consultar la tabla directo para el "antes".
    expect(Schema::hasColumn('actividades', 'peso'))->toBeFalse();

    Artisan::call('migrate');
    expect(Artisan::output())->not->toContain('Exception');

    expect((float) DB::table('actividades')->find($actividad->id)->peso)->toBe(25.0);
    // Sin Tareas con peso computable: cae al default de la migración (1),
    // no afecta el cálculo igual (Actividad::avance() da null y se
    // excluye del ponderado por Actividad, ver CalculadorAvanceTableroTest).
    expect((float) DB::table('actividades')->find($actividadSinTareas->id)->peso)->toBe(1.0);
});
