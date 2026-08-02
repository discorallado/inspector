<?php

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\TableroKanbanBoard;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\Tarea;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);

    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create();
    $this->actividad = Actividad::factory()->for($this->tablero)->create();
});

it('agrupa las tareas del tablero por status, en las 5 columnas del enum', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    Tarea::factory()->for($this->actividad)->create(['status' => TaskStatus::Pendiente]);
    // Vía update(), no create(): la máquina de estados solo permite
    // [null -> pendiente] al crear (TransicionEstadoPermitidaSeeder) —
    // una Tarea nueva no puede nacer directamente en en_progreso.
    tap(Tarea::factory()->for($this->actividad)->create(['status' => TaskStatus::Pendiente]))
        ->update(['status' => TaskStatus::EnProgreso]);

    // Tarea de OTRO tablero: no debe aparecer acá — filtrado real vía
    // actividad->tablero_id, no solo "todas las tareas del sistema".
    $otroTablero = Tablero::factory()->for(Proyecto::factory())->create();
    Tarea::factory()->for(Actividad::factory()->for($otroTablero))->create(['status' => TaskStatus::Pendiente]);

    $columns = Livewire::test(TableroKanbanBoard::class, ['record' => $this->tablero->id])
        ->instance()
        ->getColumns();

    expect($columns)->toHaveCount(5);

    $pendientes = collect($columns)->firstWhere('status', TaskStatus::Pendiente);
    expect($pendientes['tareas'])->toHaveCount(1);

    $enProgreso = collect($columns)->firstWhere('status', TaskStatus::EnProgreso);
    expect($enProgreso['tareas'])->toHaveCount(1);

    $completadas = collect($columns)->firstWhere('status', TaskStatus::Completada);
    expect($completadas['tareas'])->toHaveCount(0);
});

it('un salto de estado válido mueve la tarea vía el drag del kanban', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $tarea = Tarea::factory()->for($this->actividad)->create(['status' => TaskStatus::Pendiente]);

    Livewire::test(TableroKanbanBoard::class, ['record' => $this->tablero->id])
        ->call('updateTareaStatus', (string) $tarea->id, TaskStatus::EnProgreso->value);

    expect($tarea->fresh()->status)->toBe(TaskStatus::EnProgreso);
});

it('un salto de estado inválido no mueve la tarea y avisa por notificación', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    // Pendiente -> Completada no está en la matriz sembrada por
    // TransicionEstadoPermitidaSeeder (TaskStatus_status): salto directo
    // sin pasar por en_progreso/en_revision.
    $tarea = Tarea::factory()->for($this->actividad)->create(['status' => TaskStatus::Pendiente]);

    Livewire::test(TableroKanbanBoard::class, ['record' => $this->tablero->id])
        ->call('updateTareaStatus', (string) $tarea->id, TaskStatus::Completada->value)
        ->assertNotified();

    expect($tarea->fresh()->status)->toBe(TaskStatus::Pendiente);
});

it('tecnico (con tablero_tarea.actualizar) puede mover tareas en el kanban', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $this->actingAs($user);

    $tarea = Tarea::factory()->for($this->actividad)->create(['status' => TaskStatus::Pendiente]);

    Livewire::test(TableroKanbanBoard::class, ['record' => $this->tablero->id])
        ->call('updateTareaStatus', (string) $tarea->id, TaskStatus::EnProgreso->value);

    expect($tarea->fresh()->status)->toBe(TaskStatus::EnProgreso);
});

/**
 * Livewire::test() deja pasar AuthorizationException por su propio manejador
 * de excepciones en vez de relanzarla al test (ver
 * Livewire\Features\SupportTesting\RequestBroker::temporarilyDisableExceptionHandlingAndMiddleware,
 * que la excluye explícitamente de "sin manejo") — por eso la aserción real
 * acá es sobre el efecto (el estado no cambió), no sobre una excepción
 * capturada.
 */
it('calidad (sin tablero_tarea.actualizar) no puede mover tareas en el kanban', function () {
    $user = User::factory()->create(['role' => 'calidad']);
    $this->actingAs($user);

    $tarea = Tarea::factory()->for($this->actividad)->create(['status' => TaskStatus::Pendiente]);

    Livewire::test(TableroKanbanBoard::class, ['record' => $this->tablero->id])
        ->call('updateTareaStatus', (string) $tarea->id, TaskStatus::EnProgreso->value);

    expect($tarea->fresh()->status)->toBe(TaskStatus::Pendiente);
});

it('el filtro por actividad limita las columnas a las tareas de esa actividad', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $otraActividad = Actividad::factory()->for($this->tablero)->create();
    Tarea::factory()->for($this->actividad)->create(['status' => TaskStatus::Pendiente]);
    Tarea::factory()->for($otraActividad)->create(['status' => TaskStatus::Pendiente]);

    $columns = Livewire::test(TableroKanbanBoard::class, ['record' => $this->tablero->id])
        ->set('filterActividad', $otraActividad->id)
        ->instance()
        ->getColumns();

    $pendientes = collect($columns)->firstWhere('status', TaskStatus::Pendiente);
    expect($pendientes['tareas'])->toHaveCount(1)
        ->and($pendientes['tareas']->first()->actividad_id)->toBe($otraActividad->id);
});

it('la ruta /kanban del tablero responde 200 y su HTML referencia las columnas del enum', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);

    $html = $this->actingAs($user)
        ->get("/admin/tableros/{$this->tablero->id}/kanban")
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('kanban-col-pendiente')
        ->toContain('kanban-col-en_progreso')
        ->toContain('kanban-col-completada')
        ->toContain('data-status="bloqueada"');
});

it('el botón Ver Kanban del listado de Tableros enlaza a la ruta /kanban', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);

    $html = $this->actingAs($user)->get('/admin/tableros')->getContent();

    expect($html)->toContain("/admin/tableros/{$this->tablero->id}/kanban");
});

/**
 * A diferencia de AuthorizationException (ver el comentario más abajo en
 * este archivo), Livewire::test() SÍ deja propagar ModelNotFoundException
 * tal cual al test — solo excluye Http/AuthorizationException de "sin
 * manejo" (RequestBroker::temporarilyDisableExceptionHandlingAndMiddleware).
 * En producción esto se resuelve normal a un 404 (el ExceptionHandler de
 * Laravel sí la maneja fuera del contexto de test).
 */
it('no mueve una tarea que pertenece a OTRO tablero (bug de scope encontrado por /revisor)', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $otroTablero = Tablero::factory()->for(Proyecto::factory())->create();
    $otraActividad = Actividad::factory()->for($otroTablero)->create();
    $tareaAjena = Tarea::factory()->for($otraActividad)->create(['status' => TaskStatus::Pendiente]);

    expect(fn () => Livewire::test(TableroKanbanBoard::class, ['record' => $this->tablero->id])
        ->call('updateTareaStatus', (string) $tareaAjena->id, TaskStatus::EnProgreso->value))
        ->toThrow(ModelNotFoundException::class);

    expect($tareaAjena->fresh()->status)->toBe(TaskStatus::Pendiente);
});

it('un status inexistente en el enum responde 422 en vez de un 500', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    $tarea = Tarea::factory()->for($this->actividad)->create(['status' => TaskStatus::Pendiente]);

    Livewire::test(TableroKanbanBoard::class, ['record' => $this->tablero->id])
        ->call('updateTareaStatus', (string) $tarea->id, 'no-existe')
        ->assertStatus(422);

    expect($tarea->fresh()->status)->toBe(TaskStatus::Pendiente);
});
