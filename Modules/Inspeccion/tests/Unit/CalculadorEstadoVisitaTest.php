<?php

use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\SeveridadSeeder;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Severidad;
use Modules\Inspeccion\Models\VisitaInspeccion;
use Modules\Inspeccion\Services\CalculadorEstadoVisita;

beforeEach(function () {
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(SeveridadSeeder::class);
    $this->visita = VisitaInspeccion::factory()->for(Proyecto::factory())->create();
    $this->calculador = new CalculadorEstadoVisita;
});

/**
 * Crea la observación directamente en el estado pedido, sin pasar por la
 * máquina de estados (esta suite prueba la agregación de CalculadorEstadoVisita,
 * no las transiciones — eso ya lo cubre ObservacionFlowTest).
 */
function crearObservacionParaVisita(VisitaInspeccion $visita, string $codigoEstado, ?string $codigoSeveridad = null): void
{
    Observacion::withoutEvents(fn () => Observacion::factory()->for($visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', $codigoEstado)->value('id'),
        'severidad_id' => $codigoSeveridad ? Severidad::query()->where('codigo', $codigoSeveridad)->value('id') : null,
    ]));
}

it('retorna sin_observaciones cuando la visita no tiene observaciones', function () {
    expect($this->calculador->calcular($this->visita))->toBe(CalculadorEstadoVisita::SIN_OBSERVACIONES);
});

it('retorna todo_cerrado cuando todas las observaciones están en estado terminal', function () {
    crearObservacionParaVisita($this->visita, 'subsanada_ok');
    crearObservacionParaVisita($this->visita, 'informativa');

    expect($this->calculador->calcular($this->visita))->toBe(CalculadorEstadoVisita::TODO_CERRADO);
});

it('retorna con_pendientes cuando hay pendientes sin severidad crítica', function () {
    crearObservacionParaVisita($this->visita, 'pendiente', 'mayor');

    expect($this->calculador->calcular($this->visita))->toBe(CalculadorEstadoVisita::CON_PENDIENTES);
});

it('retorna pendientes_criticos cuando hay al menos un pendiente de severidad crítica', function () {
    crearObservacionParaVisita($this->visita, 'pendiente', 'mayor');
    crearObservacionParaVisita($this->visita, 'pendiente', 'critica');

    expect($this->calculador->calcular($this->visita))->toBe(CalculadorEstadoVisita::PENDIENTES_CRITICOS);
});
