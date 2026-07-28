<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Filament\Resources\Observacions\Pages\ListObservacions;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\VisitaInspeccion;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
    $this->admin = User::factory()->create(['role' => 'super_admin']);
});

it('oculta Editar y muestra Restaurar cuando la Observacion está en la papelera', function () {
    $visita = VisitaInspeccion::factory()->for(Proyecto::factory())->create(['inspector_id' => $this->admin->id]);
    $observacion = Observacion::factory()->for($visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]);
    $observacion->delete();

    Livewire::actingAs($this->admin)
        ->test(ListObservacions::class, ['tableFilters' => ['trashed' => ['value' => '1']]])
        ->assertTableActionHidden('edit', $observacion)
        ->assertTableActionVisible('restore', $observacion)
        ->assertTableActionVisible('forceDelete', $observacion);
});

it('muestra Editar y oculta Restaurar/Eliminar definitivo para una Observacion normal', function () {
    $visita = VisitaInspeccion::factory()->for(Proyecto::factory())->create(['inspector_id' => $this->admin->id]);
    $observacion = Observacion::factory()->for($visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListObservacions::class)
        ->assertTableActionVisible('edit', $observacion)
        ->assertTableActionHidden('restore', $observacion)
        ->assertTableActionHidden('forceDelete', $observacion);
});
