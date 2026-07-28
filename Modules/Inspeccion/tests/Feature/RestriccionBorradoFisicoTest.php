<?php

use Illuminate\Database\QueryException;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
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
