<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Enums\TaskPriority;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Filament\Resources\Actividades\ActividadResource;
use Modules\Inspeccion\Filament\Resources\Actividades\Pages\EditActividad;
use Modules\Inspeccion\Filament\Resources\Actividades\RelationManagers\TareasRelationManager;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\Tarea;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);

    $tablero = Tablero::factory()->for(Proyecto::factory())->create();
    $this->actividad = Actividad::factory()->for($tablero)->create();
});

it('ingeniero puede crear una Tarea desde el relation manager de la Actividad', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    Livewire::test(TareasRelationManager::class, [
        'ownerRecord' => $this->actividad,
        'pageClass' => EditActividad::class,
    ])
        ->call('mountTableAction', 'create')
        ->fillForm([
            'code' => 'TP-01-1',
            'nombre' => 'Montaje de riel DIN',
            'status' => TaskStatus::Pendiente->value,
            'priority' => TaskPriority::Media->value,
            'peso' => 10,
        ])
        ->call('callMountedTableAction')
        ->assertHasNoTableActionErrors();

    expect(Tarea::query()->where('actividad_id', $this->actividad->id)->where('code', 'TP-01-1')->exists())
        ->toBeTrue();
});

it('rechaza un code duplicado dentro de la misma Actividad', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    Tarea::factory()->for($this->actividad)->create(['code' => 'TP-01-1']);
    $this->actingAs($user);

    Livewire::test(TareasRelationManager::class, [
        'ownerRecord' => $this->actividad,
        'pageClass' => EditActividad::class,
    ])
        ->call('mountTableAction', 'create')
        ->fillForm([
            'code' => 'TP-01-1',
            'nombre' => 'Duplicado',
            'status' => TaskStatus::Pendiente->value,
            'priority' => TaskPriority::Media->value,
        ])
        ->call('callMountedTableAction')
        ->assertHasTableActionErrors(['code']);
});

it('tecnico (sin tablero_actividad.gestionar) no puede crear una Tarea', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $this->actingAs($user);

    Livewire::test(TareasRelationManager::class, [
        'ownerRecord' => $this->actividad,
        'pageClass' => EditActividad::class,
    ])
        ->assertTableActionHidden('create');
});

it('tecnico (tablero_tarea.actualizar) sí puede editar una Tarea', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $tarea = Tarea::factory()->for($this->actividad)->create();
    $this->actingAs($user);

    Livewire::test(TareasRelationManager::class, [
        'ownerRecord' => $this->actividad,
        'pageClass' => EditActividad::class,
    ])
        ->assertTableActionVisible('edit', record: $tarea);
});

it('ActividadResource está oculto de la navegación (solo se llega vía ActividadesRelationManager)', function () {
    expect(ActividadResource::shouldRegisterNavigation())->toBeFalse();
});
