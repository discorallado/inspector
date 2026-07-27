<?php

use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Models\EstadoAvance;
use Modules\Inspeccion\Models\GrupoHito;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\TableroHito;
use Modules\Inspeccion\Services\CalculadorAvanceTablero;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
    $this->grupo = GrupoHito::factory()->create();
    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create();
    $this->calculador = new CalculadorAvanceTablero;
});

/**
 * Crea el hito directamente en el estado pedido, sin pasar por la máquina
 * de estados (esta suite prueba la fórmula de avance, no las transiciones
 * — eso ya lo cubre TransicionEstadoGuardTest / TableroHitoTransicionEstadoTest).
 */
function crearHito(Tablero $tablero, GrupoHito $grupo, string $codigoEstado, float $peso): TableroHito
{
    return TableroHito::withoutEvents(fn () => TableroHito::factory()->for($tablero)->for($grupo, 'grupoHito')->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', $codigoEstado)->value('id'),
        'peso' => $peso,
    ]));
}

it('calcula 0% si todos los hitos están pendientes', function () {
    crearHito($this->tablero, $this->grupo, 'pendiente', 10);
    crearHito($this->tablero, $this->grupo, 'pendiente', 20);

    expect($this->calculador->calcular($this->tablero))->toBe(0.0);
});

it('calcula 100% si todos los hitos están completados', function () {
    crearHito($this->tablero, $this->grupo, 'completado', 10);
    crearHito($this->tablero, $this->grupo, 'completado', 20);

    expect($this->calculador->calcular($this->tablero))->toBe(100.0);
});

it('pondera correctamente una mezcla de estados', function () {
    // peso 10 completado (valor 1) + peso 30 pendiente (valor 0) = 10/40 = 25%
    crearHito($this->tablero, $this->grupo, 'completado', 10);
    crearHito($this->tablero, $this->grupo, 'pendiente', 30);

    expect($this->calculador->calcular($this->tablero))->toBe(25.0);
});

it('excluye del cálculo los hitos marcados como N/A', function () {
    // peso 10 completado (valor 1) + peso 90 N/A (excluido) => 10/10 = 100%
    crearHito($this->tablero, $this->grupo, 'completado', 10);
    crearHito($this->tablero, $this->grupo, 'na', 90);

    expect($this->calculador->calcular($this->tablero))->toBe(100.0);
});

it('retorna null si no hay hitos con peso computable', function () {
    crearHito($this->tablero, $this->grupo, 'na', 10);

    expect($this->calculador->calcular($this->tablero))->toBeNull();
});

it('recalcula y cachea el avance en el tablero al pasar un hito de Pendiente a En proceso', function () {
    $hito = TableroHito::factory()->for($this->tablero)->for($this->grupo, 'grupoHito')->create([
        'estado_avance_id' => EstadoAvance::query()->where('codigo', 'pendiente')->value('id'),
        'peso' => 10,
    ]);

    $hito->update(['estado_avance_id' => EstadoAvance::query()->where('codigo', 'en_proceso')->value('id')]);

    $this->tablero->refresh();

    expect((float) $this->tablero->avance_global)->toBe(50.0)
        ->and($this->tablero->avance_calculado_at)->not->toBeNull();
});
