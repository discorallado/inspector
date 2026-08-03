<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\TableroActividadesResumen;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;
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
});

function testResumen(Tablero $tablero)
{
    return Livewire::test(TableroActividadesResumen::class, ['record' => $tablero->getRouteKey()]);
}

it('carga la página con las Tareas agrupadas por Actividad', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create(['nombre' => 'Cableado', 'peso' => 3]);
    Tarea::factory()->for($actividad)->create(['nombre' => 'Montaje riel DIN', 'peso' => 10]);
    $this->actingAs($user);

    $this->get(TableroResource::getUrl('actividades-resumen', ['record' => $this->tablero]))
        ->assertSuccessful()
        ->assertSee('Cableado')
        ->assertSee('Montaje riel DIN');
});

it('la columna avance refleja TaskStatus::valor() x 100', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $tarea = Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create(['status' => TaskStatus::EnProgreso]));
    $this->actingAs($user);

    testResumen($this->tablero)->assertTableColumnStateSet('avance', 50.0, record: $tarea);
});

it('el SelectColumn de estado solo ofrece transiciones válidas desde el estado actual', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $tarea = Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create(['status' => TaskStatus::Pendiente]));
    $this->actingAs($user);

    testResumen($this->tablero)->assertTableSelectColumnHasOptions('status', [
        TaskStatus::Pendiente->value => TaskStatus::Pendiente->getLabel(),
        TaskStatus::EnProgreso->value => TaskStatus::EnProgreso->getLabel(),
        TaskStatus::Bloqueada->value => TaskStatus::Bloqueada->getLabel(),
    ], record: $tarea);
});

it('ingeniero puede cambiar el estado de una Tarea inline y el avance_global del Tablero se recalcula', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create(['peso' => 1]);
    $tarea = Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create([
        'status' => TaskStatus::Pendiente,
        'peso' => 10,
    ]));
    $this->actingAs($user);

    testResumen($this->tablero)->call('updateTableColumnState', 'status', (string) $tarea->id, TaskStatus::EnProgreso->value);

    expect($tarea->fresh()->status)->toBe(TaskStatus::EnProgreso);
    expect((float) $this->tablero->fresh()->avance_global)->toBe(50.0);
});

it('un salto de estado inválido forzado igual se rechaza a nivel modelo (defensa en profundidad)', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $tarea = Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create(['status' => TaskStatus::Pendiente]));
    $this->actingAs($user);

    testResumen($this->tablero)->call('updateTableColumnState', 'status', (string) $tarea->id, TaskStatus::Completada->value);

    expect($tarea->fresh()->status)->toBe(TaskStatus::Pendiente);
});

it('tecnico (con tablero_tarea.actualizar) puede cambiar el estado de una Tarea', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $tarea = Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create(['status' => TaskStatus::Pendiente]));
    $this->actingAs($user);

    testResumen($this->tablero)->call('updateTableColumnState', 'status', (string) $tarea->id, TaskStatus::EnProgreso->value);

    expect($tarea->fresh()->status)->toBe(TaskStatus::EnProgreso);
});

it('calidad (sin tablero_tarea.actualizar) no puede cambiar el estado de una Tarea', function () {
    $user = User::factory()->create(['role' => 'calidad']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    $tarea = Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create(['status' => TaskStatus::Pendiente]));
    $this->actingAs($user);

    testResumen($this->tablero)->call('updateTableColumnState', 'status', (string) $tarea->id, TaskStatus::EnProgreso->value);

    expect($tarea->fresh()->status)->toBe(TaskStatus::Pendiente);
});

it('ingeniero puede editar el peso de la Actividad inline desde la fila de Tarea', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create(['peso' => 3]);
    $tarea = Tarea::factory()->for($actividad)->create();
    $this->actingAs($user);

    testResumen($this->tablero)->call('updateTableColumnState', 'actividad.peso', (string) $tarea->id, '7.5');

    expect((float) $actividad->fresh()->peso)->toBe(7.5);
});

it('tecnico (sin tablero_actividad.gestionar) no puede editar el peso de la Actividad', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $actividad = Actividad::factory()->for($this->tablero)->create(['peso' => 3]);
    $tarea = Tarea::factory()->for($actividad)->create();
    $this->actingAs($user);

    testResumen($this->tablero)->call('updateTableColumnState', 'actividad.peso', (string) $tarea->id, '7.5');

    expect((float) $actividad->fresh()->peso)->toBe(3.0);
});
