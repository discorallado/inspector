<?php

use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Exceptions\TransicionEstadoInvalidaException;
use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create();
});

function crearControlCambio(Tablero $tablero): ControlCambio
{
    return ControlCambio::factory()->for($tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);
}

it('recorre el flujo completo Propuesto -> Aprobado -> Implementado', function () {
    $cambio = crearControlCambio($this->tablero);

    $cambio->update(['estado_cambio_id' => EstadoCambio::query()->where('codigo', 'aprobado')->value('id')]);
    $cambio->update(['estado_cambio_id' => EstadoCambio::query()->where('codigo', 'implementado')->value('id')]);

    expect($cambio->refresh()->estadoCambio->codigo)->toBe('implementado');
});

it('permite rechazar un cambio ya aprobado', function () {
    $cambio = crearControlCambio($this->tablero);
    $cambio->update(['estado_cambio_id' => EstadoCambio::query()->where('codigo', 'aprobado')->value('id')]);

    $cambio->update(['estado_cambio_id' => EstadoCambio::query()->where('codigo', 'rechazado')->value('id')]);

    expect($cambio->refresh()->estadoCambio->codigo)->toBe('rechazado');
});

it('no permite implementar un cambio que sigue Propuesto', function () {
    $cambio = crearControlCambio($this->tablero);

    expect(fn () => $cambio->update(['estado_cambio_id' => EstadoCambio::query()->where('codigo', 'implementado')->value('id')]))
        ->toThrow(TransicionEstadoInvalidaException::class);
});
