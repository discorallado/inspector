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

// -------------------------------------------------------------------
// Peso ponderado por Actividad (ADR de peso ponderado)
// -------------------------------------------------------------------

it('sin peso en ninguna Actividad, calcular() cae a la fórmula plana de Tareas (fallback)', function () {
    // $this->actividad (del beforeEach) no tiene peso — mismo escenario que
    // todos los tests de arriba, acá se nombra explícito el comportamiento.
    crearTarea($this->actividad, TaskStatus::Completada, 10);
    crearTarea($this->actividad, TaskStatus::Pendiente, 30);

    expect($this->actividad->peso)->toBeNull();
    expect($this->calculador->calcular($this->tablero))->toBe(25.0);
});

it('con peso en las Actividades, calcular() pondera entre Actividades usando su propio avance()', function () {
    $actividadA = Actividad::factory()->for($this->tablero)->create(['peso' => 10]);
    $actividadB = Actividad::factory()->for($this->tablero)->create(['peso' => 30]);

    // avance(A) = 100% (única tarea completada)
    crearTarea($actividadA, TaskStatus::Completada, 5);
    // avance(B) = 0% (única tarea pendiente)
    crearTarea($actividadB, TaskStatus::Pendiente, 5);

    // (10×100 + 30×0) / 40 = 25%
    expect($this->calculador->calcular($this->tablero))->toBe(25.0);
});

it('una Actividad sin peso asignado no participa del ponderado por Actividad, aunque otras sí tengan peso', function () {
    $actividadConPeso = Actividad::factory()->for($this->tablero)->create(['peso' => 10]);
    $actividadSinPeso = Actividad::factory()->for($this->tablero)->create(['peso' => null]);

    crearTarea($actividadConPeso, TaskStatus::Completada, 5);
    // Esta actividad tiene peso de sobra en sus Tareas, pero al no tener
    // actividad.peso asignado queda afuera del ponderado por Actividad.
    crearTarea($actividadSinPeso, TaskStatus::Pendiente, 1000);

    expect($this->calculador->calcular($this->tablero))->toBe(100.0);
});

it('una Actividad con peso pero sin avance() propio (sin tareas con peso) no participa del ponderado', function () {
    $actividadConAvance = Actividad::factory()->for($this->tablero)->create(['peso' => 10]);
    $actividadSinAvance = Actividad::factory()->for($this->tablero)->create(['peso' => 20]);

    crearTarea($actividadConAvance, TaskStatus::Completada, 5);
    // Sin tareas -> avance() es null -> se excluye pese a tener peso.

    expect($actividadSinAvance->avance())->toBeNull();
    expect($this->calculador->calcular($this->tablero))->toBe(100.0);
});
