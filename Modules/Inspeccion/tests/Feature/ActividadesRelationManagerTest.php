<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\EditTablero;
use Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers\ActividadesRelationManager;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\Tarea;

beforeEach(function () {
    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create();
});

it('ingeniero (tablero_actividad.gestionar) puede crear una Actividad desde el relation manager', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $this->actingAs($user);

    Livewire::test(ActividadesRelationManager::class, [
        'ownerRecord' => $this->tablero,
        'pageClass' => EditTablero::class,
    ])
        ->call('mountTableAction', 'create')
        ->fillForm([
            'nombre' => 'Cableado de potencia',
            'orden' => 1,
        ])
        ->call('callMountedTableAction')
        ->assertHasNoTableActionErrors();

    expect(Actividad::query()->where('tablero_id', $this->tablero->id)->where('nombre', 'Cableado de potencia')->exists())
        ->toBeTrue();
});

it('tecnico (sin tablero_actividad.gestionar) no ve la acción de crear Actividad', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $this->actingAs($user);

    Livewire::test(ActividadesRelationManager::class, [
        'ownerRecord' => $this->tablero,
        'pageClass' => EditTablero::class,
    ])
        ->assertTableActionHidden('create');
});

it('la columna avance refleja el ponderado de las tareas de la actividad', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $actividad = Actividad::factory()->for($this->tablero)->create();
    Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create([
        'status' => TaskStatus::Completada,
        'peso' => 10,
    ]));
    Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create([
        'status' => TaskStatus::Pendiente,
        'peso' => 30,
    ]));

    $this->actingAs($user);

    Livewire::test(ActividadesRelationManager::class, [
        'ownerRecord' => $this->tablero,
        'pageClass' => EditTablero::class,
    ])->assertSuccessful();

    expect($actividad->avance())->toBe(25.0);
});
