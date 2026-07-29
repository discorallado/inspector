<?php

use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Exceptions\TransicionEstadoInvalidaException;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

/**
 * ADR 0009/0010: variante *PorCodigo del guard, para catálogos basados en
 * un enum PHP (Tarea.status) en vez de una tabla de catálogo con id.
 */
beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
    $this->guard = new TransicionEstadoGuard;
});

it('permite la transición sembrada null -> Pendiente', function () {
    expect($this->guard->puedeTransicionarPorCodigo(
        TransicionEstadoPermitida::TIPO_TAREA_STATUS,
        null,
        TaskStatus::Pendiente->value,
    ))->toBeTrue();
});

it('permite la transición sembrada EnRevision -> EnProgreso (rebote)', function () {
    expect($this->guard->puedeTransicionarPorCodigo(
        TransicionEstadoPermitida::TIPO_TAREA_STATUS,
        TaskStatus::EnRevision->value,
        TaskStatus::EnProgreso->value,
    ))->toBeTrue();
});

it('rechaza un salto directo no sembrado Pendiente -> Completada', function () {
    expect($this->guard->puedeTransicionarPorCodigo(
        TransicionEstadoPermitida::TIPO_TAREA_STATUS,
        TaskStatus::Pendiente->value,
        TaskStatus::Completada->value,
    ))->toBeFalse();
});

it('rechaza reabrir una tarea Completada', function () {
    expect($this->guard->puedeTransicionarPorCodigo(
        TransicionEstadoPermitida::TIPO_TAREA_STATUS,
        TaskStatus::Completada->value,
        TaskStatus::EnProgreso->value,
    ))->toBeFalse();
});

it('lanza una excepción al validar una transición inválida por código', function () {
    $this->guard->validarPorCodigo(
        TransicionEstadoPermitida::TIPO_TAREA_STATUS,
        TaskStatus::Pendiente->value,
        TaskStatus::Completada->value,
    );
})->throws(TransicionEstadoInvalidaException::class);

it('no interfiere con las transiciones basadas en id de otros catálogos', function () {
    // Mismo string usado como "código" acá no debería colar como id real
    // de ningún catálogo — tipo_catalogo distinto alcanza para aislarlos,
    // pero lo confirmamos con un test explícito.
    expect($this->guard->puedeTransicionarPorCodigo(
        TransicionEstadoPermitida::TIPO_ESTADO_CAMBIO,
        null,
        TaskStatus::Pendiente->value,
    ))->toBeFalse();
});
