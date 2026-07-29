<?php

use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Enums\TaskPriority;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\Tarea;
use Modules\Inspeccion\Models\TareaLink;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
});

it('crea una Actividad colgada de un Tablero y se puede recorrer la relación en ambos sentidos', function () {
    $tablero = Tablero::factory()->for(Proyecto::factory())->create();
    $actividad = Actividad::factory()->for($tablero)->create();

    expect($actividad->tablero->is($tablero))->toBeTrue();
    expect($tablero->actividades)->toHaveCount(1);
    expect($tablero->actividades->first()->is($actividad))->toBeTrue();
});

it('crea una Tarea con status/priority casteados a enum', function () {
    $tarea = Tarea::factory()->create([
        'status' => TaskStatus::Pendiente,
        'priority' => TaskPriority::Alta,
    ]);

    expect($tarea->status)->toBe(TaskStatus::Pendiente);
    expect($tarea->priority)->toBe(TaskPriority::Alta);
    expect($tarea->actividad)->toBeInstanceOf(Actividad::class);
});

it('una Tarea puede tener subtareas vía parent_tarea_id', function () {
    $padre = Tarea::factory()->create();
    $hija = Tarea::factory()->for($padre->actividad)->create(['parent_tarea_id' => $padre->id]);

    expect($padre->subtareas)->toHaveCount(1);
    expect($padre->subtareas->first()->is($hija))->toBeTrue();
    expect($hija->parentTarea->is($padre))->toBeTrue();
});

it('crea un TareaLink entre dos tareas', function () {
    $origen = Tarea::factory()->create();
    $destino = Tarea::factory()->create();

    $link = TareaLink::factory()->create([
        'source_id' => $origen->id,
        'target_id' => $destino->id,
        'type' => 0,
    ]);

    expect($link->type)->toBe(0);
    expect($link->source_id)->toBe($origen->id);
});
