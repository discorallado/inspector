<?php

use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Exceptions\TransicionEstadoInvalidaException;
use Modules\Inspeccion\Models\EstadoAvance;
use Modules\Inspeccion\Models\GrupoHito;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\TableroHito;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create();
    $this->grupo = GrupoHito::factory()->create();
});

it('permite avanzar un hito de Pendiente a En proceso y recalcula el avance del tablero', function () {
    $hito = TableroHito::factory()->for($this->tablero)->for($this->grupo, 'grupoHito')->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'pendiente')->value('id'),
        'peso' => 10,
    ]);

    $hito->update(['estado_avance_id' => EstadoAvance::query()->where('codigo', 'en_proceso')->value('id')]);

    expect((float) $this->tablero->refresh()->avance_global)->toBe(50.0);
});

it('rechaza saltar de Pendiente directo a Completado sin pasar por En proceso', function () {
    $hito = TableroHito::factory()->for($this->tablero)->for($this->grupo, 'grupoHito')->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'pendiente')->value('id'),
        'peso' => 10,
    ]);

    expect(fn () => $hito->update(['estado_avance_id' => EstadoAvance::query()->where('codigo', 'completado')->value('id')]))
        ->toThrow(TransicionEstadoInvalidaException::class);
});
