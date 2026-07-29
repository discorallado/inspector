<?php

use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\Tarea;
use Modules\Inspeccion\Services\CalculadorAvanceTablero;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create();
    $this->actividad = Actividad::factory()->for($this->tablero)->create();
    $this->calculador = new CalculadorAvanceTablero;
});

/**
 * Crea la tarea directamente en el estado pedido, sin pasar por la máquina
 * de estados (esta suite prueba la fórmula de avance, no las transiciones
 * — eso ya lo cubre TransicionEstadoGuardTest).
 */
function crearTarea(Actividad $actividad, TaskStatus $status, float $peso, bool $excluyeCalculo = false): Tarea
{
    return Tarea::withoutEvents(fn () => Tarea::factory()->for($actividad)->create([
        'status' => $status,
        'peso' => $peso,
        'excluye_calculo' => $excluyeCalculo,
    ]));
}

it('calcula 0% si todas las tareas están pendientes', function () {
    crearTarea($this->actividad, TaskStatus::Pendiente, 10);
    crearTarea($this->actividad, TaskStatus::Pendiente, 20);

    expect($this->calculador->calcular($this->tablero))->toBe(0.0);
});

it('calcula 100% si todas las tareas están completadas', function () {
    crearTarea($this->actividad, TaskStatus::Completada, 10);
    crearTarea($this->actividad, TaskStatus::Completada, 20);

    expect($this->calculador->calcular($this->tablero))->toBe(100.0);
});

it('pondera correctamente una mezcla de estados', function () {
    // peso 10 completada (valor 1) + peso 30 pendiente (valor 0) = 10/40 = 25%
    crearTarea($this->actividad, TaskStatus::Completada, 10);
    crearTarea($this->actividad, TaskStatus::Pendiente, 30);

    expect($this->calculador->calcular($this->tablero))->toBe(25.0);
});

it('excluye del cálculo las tareas marcadas con excluye_calculo', function () {
    // peso 10 completada (valor 1) + peso 90 excluida => 10/10 = 100%
    crearTarea($this->actividad, TaskStatus::Completada, 10);
    crearTarea($this->actividad, TaskStatus::Bloqueada, 90, excluyeCalculo: true);

    expect($this->calculador->calcular($this->tablero))->toBe(100.0);
});

it('retorna null si no hay tareas con peso computable', function () {
    crearTarea($this->actividad, TaskStatus::Bloqueada, 10, excluyeCalculo: true);

    expect($this->calculador->calcular($this->tablero))->toBeNull();
});

it('recalcula y cachea el avance en el tablero al pasar una tarea de Pendiente a En Progreso', function () {
    $tarea = Tarea::factory()->for($this->actividad)->create([
        'status' => TaskStatus::Pendiente,
        'peso' => 10,
    ]);

    $tarea->update(['status' => TaskStatus::EnProgreso]);

    $this->tablero->refresh();

    expect((float) $this->tablero->avance_global)->toBe(50.0)
        ->and($this->tablero->avance_calculado_at)->not->toBeNull();
});

it('recalcula el avance del tablero al eliminar una tarea', function () {
    crearTarea($this->actividad, TaskStatus::Completada, 10);
    $tarea = crearTarea($this->actividad, TaskStatus::Pendiente, 30);
    $this->calculador->recalcularYGuardar($this->tablero);
    expect((float) $this->tablero->refresh()->avance_global)->toBe(25.0);

    $tarea->delete();

    expect((float) $this->tablero->refresh()->avance_global)->toBe(100.0);
});
