<?php

use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
});

it('borrar un ControlCambio no lo destruye físicamente, solo lo oculta', function () {
    $tablero = Tablero::factory()->for(Proyecto::factory())->create();
    $cambio = ControlCambio::factory()->for($tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);

    $cambio->delete();

    expect(ControlCambio::query()->find($cambio->id))->toBeNull()
        ->and(ControlCambio::withTrashed()->find($cambio->id))->not->toBeNull()
        ->and(ControlCambio::withTrashed()->find($cambio->id)->deleted_at)->not->toBeNull();
});

it('restaurar un ControlCambio soft-deleted lo vuelve a mostrar', function () {
    $tablero = Tablero::factory()->create();
    $cambio = ControlCambio::factory()->for($tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);
    $cambio->delete();

    $cambio->restore();

    expect(ControlCambio::query()->find($cambio->id))->not->toBeNull();
});
