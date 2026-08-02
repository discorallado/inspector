<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\TableroGanttChart;
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

    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create();
    $this->actividad = Actividad::factory()->for($this->tablero)->create();
});

it('arma filas de actividad (project) y tarea, con progreso vía TaskStatus::valor()', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $tarea = Tarea::factory()->for($this->actividad)->create([
        'status' => TaskStatus::Pendiente,
        'start_date' => '2026-01-01',
        'due_date' => '2026-01-05',
    ]);
    tap($tarea)->update(['status' => TaskStatus::EnProgreso]);

    $data = Livewire::test(TableroGanttChart::class, ['record' => $this->tablero->id])
        ->instance()
        ->getGanttData();

    $filaActividad = collect($data['data'])->firstWhere('id', 'act-'.$this->actividad->id);
    expect($filaActividad)->not->toBeNull()
        ->and($filaActividad['type'])->toBe('project')
        ->and($filaActividad['readonly'])->toBeTrue();

    $filaTarea = collect($data['data'])->firstWhere('id', $tarea->id);
    expect($filaTarea)->not->toBeNull()
        ->and($filaTarea['progress'])->toBe(TaskStatus::EnProgreso->valor())
        ->and($filaTarea['parent'])->toBe('act-'.$this->actividad->id);
});

it('tecnico (tablero_tarea.actualizar) puede arrastrar la barra de una tarea', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $this->actingAs($user);

    $tarea = Tarea::factory()->for($this->actividad)->create();

    Livewire::test(TableroGanttChart::class, ['record' => $this->tablero->id])
        ->call('updateTareaFechas', (string) $tarea->id, '2026-02-01', '2026-02-10');

    $tarea->refresh();
    expect($tarea->start_date->toDateString())->toBe('2026-02-01')
        ->and($tarea->due_date->toDateString())->toBe('2026-02-10');
});

it('calidad (sin tablero_tarea.actualizar) no puede mover fechas de una tarea', function () {
    $user = User::factory()->create(['role' => 'calidad']);
    $this->actingAs($user);

    $tarea = Tarea::factory()->for($this->actividad)->create(['start_date' => '2026-01-01', 'due_date' => '2026-01-05']);

    Livewire::test(TableroGanttChart::class, ['record' => $this->tablero->id])
        ->call('updateTareaFechas', (string) $tarea->id, '2026-02-01', '2026-02-10');

    expect($tarea->fresh()->start_date->toDateString())->toBe('2026-01-01');
});

it('ingeniero (tablero.gestionar) puede reordenar actividades y tareas por drag', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $otraActividad = Actividad::factory()->for($this->tablero)->create(['orden' => 1]);
    $this->actividad->update(['orden' => 2]);

    $tarea1 = Tarea::factory()->for($this->actividad)->create(['orden' => 1]);
    $tarea2 = Tarea::factory()->for($this->actividad)->create(['orden' => 2]);

    Livewire::test(TableroGanttChart::class, ['record' => $this->tablero->id])
        ->call('persistirOrden', [$this->actividad->id, $otraActividad->id], [
            ['actividadId' => (string) $this->actividad->id, 'tareaIds' => [(string) $tarea2->id, (string) $tarea1->id]],
        ]);

    expect($this->actividad->fresh()->orden)->toBe(1)
        ->and($otraActividad->fresh()->orden)->toBe(2)
        ->and($tarea2->fresh()->orden)->toBe(1)
        ->and($tarea1->fresh()->orden)->toBe(2);
});

it('tecnico (sin tablero.gestionar) no puede reordenar actividades', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $this->actingAs($user);

    $otraActividad = Actividad::factory()->for($this->tablero)->create(['orden' => 1]);
    $this->actividad->update(['orden' => 2]);

    Livewire::test(TableroGanttChart::class, ['record' => $this->tablero->id])
        ->call('persistirOrden', [$otraActividad->id, $this->actividad->id], []);

    expect($this->actividad->fresh()->orden)->toBe(2);
});

it('crea una dependencia Tarea-Tarea vía agregarLink()', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $origen = Tarea::factory()->for($this->actividad)->create();
    $destino = Tarea::factory()->for($this->actividad)->create();

    Livewire::test(TableroGanttChart::class, ['record' => $this->tablero->id])
        ->call('agregarLink', (string) $origen->id, (string) $destino->id, 0);

    expect(TareaLink::query()->where('source_id', $origen->id)->where('target_id', $destino->id)->exists())
        ->toBeTrue();
});

it('rechaza un link que involucre una fila de Actividad (prefijo act-)', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $tarea = Tarea::factory()->for($this->actividad)->create();

    Livewire::test(TableroGanttChart::class, ['record' => $this->tablero->id])
        ->call('agregarLink', 'act-'.$this->actividad->id, (string) $tarea->id, 0)
        ->assertStatus(422);

    expect(TareaLink::query()->count())->toBe(0);
});

it('elimina un link existente del tablero vía eliminarLink()', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $origen = Tarea::factory()->for($this->actividad)->create();
    $destino = Tarea::factory()->for($this->actividad)->create();
    $link = TareaLink::factory()->create(['source_id' => $origen->id, 'target_id' => $destino->id]);

    Livewire::test(TableroGanttChart::class, ['record' => $this->tablero->id])
        ->call('eliminarLink', (string) $link->id);

    expect(TareaLink::query()->find($link->id))->toBeNull();
});

it('no elimina un link de otro tablero (404)', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $otroTablero = Tablero::factory()->for(Proyecto::factory())->create();
    $otraActividad = Actividad::factory()->for($otroTablero)->create();
    $origen = Tarea::factory()->for($otraActividad)->create();
    $destino = Tarea::factory()->for($otraActividad)->create();
    $link = TareaLink::factory()->create(['source_id' => $origen->id, 'target_id' => $destino->id]);

    Livewire::test(TableroGanttChart::class, ['record' => $this->tablero->id])
        ->call('eliminarLink', (string) $link->id)
        ->assertStatus(404);

    expect(TareaLink::query()->find($link->id))->not->toBeNull();
});

it('la ruta /gantt del tablero responde 200 y referencia el contenedor dhtmlx pinneado a la edición Community', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    Tarea::factory()->for($this->actividad)->create();

    $html = $this->actingAs($user)
        ->get("/admin/tableros/{$this->tablero->id}/gantt")
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('id="dhx-gantt"')
        ->toContain('dhtmlx-gantt@10.0.0/codebase/dhtmlxgantt.js')
        ->not->toContain('cdn.dhtmlx.com/gantt/edge');
});

it('el botón Ver Gantt del listado de Tableros enlaza a la ruta /gantt', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);

    $html = $this->actingAs($user)->get('/admin/tableros')->getContent();

    expect($html)->toContain("/admin/tableros/{$this->tablero->id}/gantt");
});

it('persistirOrden no reordena tareas de un actividadId que pertenece a OTRO tablero (bug de scope encontrado por /revisor)', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $otroTablero = Tablero::factory()->for(Proyecto::factory())->create();
    $actividadAjena = Actividad::factory()->for($otroTablero)->create();
    $tareaAjena1 = Tarea::factory()->for($actividadAjena)->create(['orden' => 1]);
    $tareaAjena2 = Tarea::factory()->for($actividadAjena)->create(['orden' => 2]);

    Livewire::test(TableroGanttChart::class, ['record' => $this->tablero->id])
        ->call('persistirOrden', [], [
            ['actividadId' => (string) $actividadAjena->id, 'tareaIds' => [(string) $tareaAjena2->id, (string) $tareaAjena1->id]],
        ]);

    expect($tareaAjena1->fresh()->orden)->toBe(1)
        ->and($tareaAjena2->fresh()->orden)->toBe(2);
});

it('agregarLink rechaza type fuera de 0-3', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $origen = Tarea::factory()->for($this->actividad)->create();
    $destino = Tarea::factory()->for($this->actividad)->create();

    Livewire::test(TableroGanttChart::class, ['record' => $this->tablero->id])
        ->call('agregarLink', (string) $origen->id, (string) $destino->id, 99)
        ->assertStatus(422);

    expect(TareaLink::query()->count())->toBe(0);
});

it('agregarLink rechaza una tarea vinculada consigo misma', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $tarea = Tarea::factory()->for($this->actividad)->create();

    Livewire::test(TableroGanttChart::class, ['record' => $this->tablero->id])
        ->call('agregarLink', (string) $tarea->id, (string) $tarea->id, 0)
        ->assertStatus(422);

    expect(TareaLink::query()->count())->toBe(0);
});

it('la ruta /gantt responde 200 para un tablero SIN tareas (bug de gantt.init() en el estado vacío encontrado por /revisor)', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $tableroVacio = Tablero::factory()->for(Proyecto::factory())->create();

    $html = $this->actingAs($user)
        ->get("/admin/tableros/{$tableroVacio->id}/gantt")
        ->assertSuccessful()
        ->getContent();

    expect($html)->not->toContain('id="dhx-gantt"');
});
