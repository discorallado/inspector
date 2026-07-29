<?php

use Illuminate\Support\Facades\Artisan;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\EstadoAvance;
use Modules\Inspeccion\Models\GrupoHito;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\TableroHito;
use Modules\Inspeccion\Models\Tarea;

/**
 * ADR 0009 §2.5 / PR5: comando inspeccion:migrar-hitos-a-tareas.
 */
beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);

    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create(['tag' => 'T1']);
    $this->grupo = GrupoHito::factory()->create(['nombre' => 'Fase 1', 'orden' => 1]);
});

it('convierte un GrupoHito usado por el Tablero en una Actividad', function () {
    TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'pendiente')->value('id'),
        'item' => '1.1',
    ]));

    Artisan::call('inspeccion:migrar-hitos-a-tareas');

    $actividad = Actividad::query()->where('tablero_id', $this->tablero->id)->first();
    expect($actividad)->not->toBeNull();
    expect($actividad->nombre)->toBe('Fase 1');
    expect($actividad->orden)->toBe(1);
});

it('convierte un TableroHito en una Tarea, preservando peso y fechas reales', function () {
    TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'en_proceso')->value('id'),
        'item' => '1.1',
        'nombre' => 'Fabricación de estructura',
        'peso' => 12.5,
        'real_inicio' => '2026-01-10',
        'real_fin' => null,
    ]));

    Artisan::call('inspeccion:migrar-hitos-a-tareas');

    $tarea = Tarea::query()->where('code', 'T1-1.1')->first();
    expect($tarea)->not->toBeNull();
    expect($tarea->nombre)->toBe('Fabricación de estructura');
    expect($tarea->status)->toBe(TaskStatus::EnProgreso);
    expect((float) $tarea->peso)->toBe(12.5);
    expect($tarea->real_inicio->toDateString())->toBe('2026-01-10');
    expect($tarea->real_fin)->toBeNull();
});

it('mapea cada EstadoAvance.codigo al TaskStatus correspondiente, incluyendo na -> Bloqueada', function () {
    $casos = [
        '1.1' => ['pendiente', TaskStatus::Pendiente],
        '1.2' => ['en_proceso', TaskStatus::EnProgreso],
        '1.3' => ['completado', TaskStatus::Completada],
        '1.4' => ['na', TaskStatus::Bloqueada],
    ];

    foreach ($casos as $item => [$codigo, $_]) {
        TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
            'estado_avance_id' => EstadoAvance::query()->where('codigo', $codigo)->value('id'),
            'item' => $item,
        ]));
    }

    Artisan::call('inspeccion:migrar-hitos-a-tareas');

    foreach ($casos as $item => [$codigo, $esperado]) {
        $tarea = Tarea::query()->where('code', "T1-{$item}")->first();
        expect($tarea->status)->toBe($esperado);
    }
});

it('no revienta con un hito ya Completado (bypassa TareaObserver en el import histórico)', function () {
    TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'completado')->value('id'),
        'item' => '1.1',
    ]));

    Artisan::call('inspeccion:migrar-hitos-a-tareas');

    $tarea = Tarea::query()->where('code', 'T1-1.1')->first();
    expect($tarea->status)->toBe(TaskStatus::Completada);
});

it('es idempotente: correrlo dos veces no duplica Actividades ni Tareas', function () {
    TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'pendiente')->value('id'),
        'item' => '1.1',
    ]));

    Artisan::call('inspeccion:migrar-hitos-a-tareas');
    $tareaId = Tarea::query()->where('code', 'T1-1.1')->value('id');

    Artisan::call('inspeccion:migrar-hitos-a-tareas');

    expect(Actividad::query()->count())->toBe(1);
    expect(Tarea::query()->count())->toBe(1);
    expect(Tarea::query()->where('code', 'T1-1.1')->value('id'))->toBe($tareaId);
});

it('no borra ni modifica TableroHito/GrupoHito/EstadoAvance (quedan deprecados, no eliminados)', function () {
    $hito = TableroHito::withoutEvents(fn () => TableroHito::factory()->for($this->tablero)->for($this->grupo)->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'pendiente')->value('id'),
        'item' => '1.1',
    ]));

    Artisan::call('inspeccion:migrar-hitos-a-tareas');

    expect(TableroHito::query()->find($hito->id))->not->toBeNull();
    expect(GrupoHito::query()->find($this->grupo->id))->not->toBeNull();
    expect(EstadoAvance::query()->count())->toBeGreaterThan(0);
});
