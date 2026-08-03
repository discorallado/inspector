<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Livewire\Livewire;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Filament\Resources\Proyectos\Pages\EditProyecto;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\EditTablero;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\VisitaInspeccion;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
});

it('no permite borrar físicamente un Tablero que todavía tiene Control de Cambios', function () {
    $tablero = Tablero::factory()->for(Proyecto::factory())->create();
    ControlCambio::factory()->for($tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);

    expect(fn () => $tablero->delete())->toThrow(QueryException::class);
});

it('no permite borrar físicamente un Proyecto que todavía tiene Visitas de Inspección', function () {
    $proyecto = Proyecto::factory()->create();
    VisitaInspeccion::factory()->for($proyecto)->create();

    expect(fn () => $proyecto->delete())->toThrow(QueryException::class);
});

it('sí permite borrar un Tablero vacío, sin historial asociado', function () {
    $tablero = Tablero::factory()->for(Proyecto::factory())->create();

    $tablero->delete();

    expect(Tablero::query()->find($tablero->id))->toBeNull();
});

it('el DeleteAction de EditTablero no revienta con un 500: avisa y no borra si hay Actividades asociadas', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $tablero = Tablero::factory()->for(Proyecto::factory())->create();
    Actividad::factory()->for($tablero)->create();
    $this->actingAs($user);

    Livewire::test(EditTablero::class, ['record' => $tablero->getRouteKey()])
        ->callAction('delete');

    expect(Tablero::query()->find($tablero->id))->not->toBeNull();
});

it('el DeleteAction de EditProyecto no revienta con un 500: avisa y no borra si hay Visitas asociadas', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $proyecto = Proyecto::factory()->create();
    VisitaInspeccion::factory()->for($proyecto)->create();
    $this->actingAs($user);

    Livewire::test(EditProyecto::class, ['record' => $proyecto->getRouteKey()])
        ->callAction('delete');

    expect(Proyecto::query()->find($proyecto->id))->not->toBeNull();
});
