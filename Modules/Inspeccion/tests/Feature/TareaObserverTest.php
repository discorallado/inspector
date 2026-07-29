<?php

use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Exceptions\TransicionEstadoInvalidaException;
use Modules\Inspeccion\Models\Tarea;

/**
 * ADR 0009/0010: Tarea.status queda validado por TransicionEstadoGuard
 * (vía TareaObserver::saving()), igual que Observacion/ControlCambio —
 * axon no valida esto, Inspeccion sí.
 */
beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
});

it('crea una Tarea en Pendiente sin error (transición sembrada null -> Pendiente)', function () {
    $tarea = Tarea::factory()->create(['status' => TaskStatus::Pendiente]);

    expect($tarea->status)->toBe(TaskStatus::Pendiente);
});

it('permite avanzar Pendiente -> EnProgreso -> EnRevision -> Completada', function () {
    $tarea = Tarea::factory()->create(['status' => TaskStatus::Pendiente]);

    $tarea->update(['status' => TaskStatus::EnProgreso]);
    $tarea->update(['status' => TaskStatus::EnRevision]);
    $tarea->update(['status' => TaskStatus::Completada]);

    expect($tarea->refresh()->status)->toBe(TaskStatus::Completada);
});

it('permite el rebote EnRevision -> EnProgreso', function () {
    $tarea = Tarea::factory()->create(['status' => TaskStatus::Pendiente]);
    $tarea->update(['status' => TaskStatus::EnProgreso]);
    $tarea->update(['status' => TaskStatus::EnRevision]);

    $tarea->update(['status' => TaskStatus::EnProgreso]);

    expect($tarea->refresh()->status)->toBe(TaskStatus::EnProgreso);
});

it('rechaza un salto directo Pendiente -> Completada', function () {
    $tarea = Tarea::factory()->create(['status' => TaskStatus::Pendiente]);

    $tarea->update(['status' => TaskStatus::Completada]);
})->throws(TransicionEstadoInvalidaException::class);

it('rechaza reabrir una Tarea Completada', function () {
    $tarea = Tarea::factory()->create(['status' => TaskStatus::Pendiente]);
    $tarea->update(['status' => TaskStatus::EnProgreso]);
    $tarea->update(['status' => TaskStatus::EnRevision]);
    $tarea->update(['status' => TaskStatus::Completada]);

    $tarea->update(['status' => TaskStatus::EnProgreso]);
})->throws(TransicionEstadoInvalidaException::class);

it('no valida nada si status no cambia en el update', function () {
    $tarea = Tarea::factory()->create(['status' => TaskStatus::Pendiente]);

    $tarea->update(['nombre' => 'Nombre actualizado']);

    expect($tarea->refresh()->nombre)->toBe('Nombre actualizado');
    expect($tarea->status)->toBe(TaskStatus::Pendiente);
});
