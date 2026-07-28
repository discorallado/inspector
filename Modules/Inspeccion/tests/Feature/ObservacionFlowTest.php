<?php

use App\Models\User;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\SeveridadSeeder;
use Modules\Inspeccion\Database\Seeders\TipoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Exceptions\SeveridadRequeridaException;
use Modules\Inspeccion\Exceptions\TransicionEstadoInvalidaException;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Severidad;
use Modules\Inspeccion\Models\TipoObservacion;
use Modules\Inspeccion\Models\VisitaInspeccion;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(SeveridadSeeder::class);
    $this->seed(TipoObservacionSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);

    $this->inspector = User::factory()->create(['role' => 'calidad']);
    $this->visita = VisitaInspeccion::factory()->for(Proyecto::factory())->create(['inspector_id' => $this->inspector->id]);
});

/**
 * Crea la observación directamente en el estado pedido, sin pasar por la
 * máquina de estados — para dejar el fixture listo en un estado que no
 * necesariamente es alcanzable como estado inicial (ej. terminal).
 */
function crearObservacionEnEstado(VisitaInspeccion $visita, array $atributos): Observacion
{
    return Observacion::withoutEvents(fn () => Observacion::factory()->for($visita, 'visitaInspeccion')->create($atributos));
}

it('crea una observación de tipo Observación a Subsanar con severidad y la cierra correctamente', function () {
    $observacion = Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'tipo_observacion_id' => TipoObservacion::query()->where('codigo', 'observacion_subsanar')->value('id'),
        'severidad_id' => Severidad::query()->where('codigo', 'mayor')->value('id'),
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]);

    $observacion->update([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'subsanada_ok')->value('id'),
        'fecha_cierre' => now(),
        'observacion_cierre' => 'Corregido en terreno.',
    ]);

    expect($observacion->refresh()->estadoObservacion->codigo)->toBe('subsanada_ok');
});

it('no permite crear una Observación a Subsanar sin severidad, ni siquiera saltándose el form de Filament', function () {
    expect(fn () => Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'tipo_observacion_id' => TipoObservacion::query()->where('codigo', 'observacion_subsanar')->value('id'),
        'severidad_id' => null,
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]))->toThrow(SeveridadRequeridaException::class);
});

it('no permite reabrir una observación ya subsanada (estado terminal sin retorno)', function () {
    $observacion = crearObservacionEnEstado($this->visita, [
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'subsanada_ok')->value('id'),
    ]);

    expect(fn () => $observacion->update(['estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id')]))
        ->toThrow(TransicionEstadoInvalidaException::class);
});

it('marca como vencida una observación pendiente con fecha de compromiso pasada', function () {
    $observacion = Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
        'fecha_compromiso' => now()->subDays(3),
    ]);

    expect($observacion->estaVencida())->toBeTrue();
});

it('no marca como vencida una observación ya cerrada aunque su fecha de compromiso haya pasado', function () {
    $observacion = crearObservacionEnEstado($this->visita, [
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'informativa')->value('id'),
        'fecha_compromiso' => now()->subDays(3),
    ]);

    expect($observacion->estaVencida())->toBeFalse();
});

it('filtra correctamente las observaciones vencidas de una lista mixta', function () {
    Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
        'fecha_compromiso' => now()->subDay(),
    ]);
    Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
        'fecha_compromiso' => now()->addDays(5),
    ]);
    crearObservacionEnEstado($this->visita, [
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'subsanada_ok')->value('id'),
        'fecha_compromiso' => now()->subDay(),
    ]);

    $vencidas = Observacion::query()->vencidas()->get();

    expect($vencidas)->toHaveCount(1);
});
