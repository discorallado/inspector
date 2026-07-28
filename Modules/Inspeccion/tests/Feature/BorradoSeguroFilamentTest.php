<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\ListTableros;
use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
    $this->admin = User::factory()->create(['role' => 'super_admin']);
});

it('borrar en bulk un Tablero con Control de Cambios asociado no revienta la página, solo falla con aviso', function () {
    $tablero = Tablero::factory()->for(Proyecto::factory())->create();
    ControlCambio::factory()->for($tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListTableros::class)
        ->callTableBulkAction('delete', [$tablero])
        ->assertSuccessful();

    expect(Tablero::query()->find($tablero->id))->not->toBeNull();
});
