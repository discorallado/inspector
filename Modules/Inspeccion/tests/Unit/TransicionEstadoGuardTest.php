<?php

use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Exceptions\TransicionEstadoInvalidaException;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
    $this->guard = new TransicionEstadoGuard;
    $this->propuesto = EstadoCambio::query()->where('codigo', 'propuesto')->value('id');
    $this->aprobado = EstadoCambio::query()->where('codigo', 'aprobado')->value('id');
    $this->rechazado = EstadoCambio::query()->where('codigo', 'rechazado')->value('id');
    $this->implementado = EstadoCambio::query()->where('codigo', 'implementado')->value('id');
});

it('permite la transición sembrada Propuesto -> Aprobado', function () {
    expect($this->guard->puedeTransicionar(TransicionEstadoPermitida::TIPO_ESTADO_CAMBIO, $this->propuesto, $this->aprobado))
        ->toBeTrue();
});

it('permite revertir Aprobado -> Rechazado (decisión de negocio confirmada)', function () {
    expect($this->guard->puedeTransicionar(TransicionEstadoPermitida::TIPO_ESTADO_CAMBIO, $this->aprobado, $this->rechazado))
        ->toBeTrue();
});

it('rechaza un salto no sembrado como Propuesto -> Implementado', function () {
    expect($this->guard->puedeTransicionar(TransicionEstadoPermitida::TIPO_ESTADO_CAMBIO, $this->propuesto, $this->implementado))
        ->toBeFalse();
});

it('lanza una excepción al validar una transición inválida', function () {
    $this->guard->validar(TransicionEstadoPermitida::TIPO_ESTADO_CAMBIO, $this->propuesto, $this->implementado);
})->throws(TransicionEstadoInvalidaException::class);
