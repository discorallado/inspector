<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Filament\Resources\Observacions\Pages\ListObservacions;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\VisitaInspeccion;

/**
 * ADR 0008: la tabla de Observaciones reemplaza al kanban de PR1 (ADR
 * 0004) — el cambio de estado ahora es un SelectColumn inline en vez de
 * drag-and-drop.
 */
beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);

    $this->visita = VisitaInspeccion::factory()->for(Proyecto::factory())->create();
});

it('un usuario con observacion.cerrar puede cambiar el estado desde el select de la tabla', function () {
    $user = User::factory()->create(['role' => 'calidad']);
    $observacion = Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]);
    $destino = EstadoObservacion::query()->where('codigo', 'subsanada_ok')->value('id');

    $this->actingAs($user);
    Livewire::test(ListObservacions::class)
        ->call('updateTableColumnState', 'estado_observacion_id', $observacion->getKey(), $destino);

    expect($observacion->refresh()->estado_observacion_id)->toBe($destino);
});

it('un usuario sin observacion.cerrar no puede cambiar el estado desde el select (campo disabled server-side)', function () {
    $user = User::factory()->create(['role' => 'tecnico']); // sin observacion.cerrar
    $observacion = Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]);
    $destino = EstadoObservacion::query()->where('codigo', 'subsanada_ok')->value('id');

    $this->actingAs($user);
    Livewire::test(ListObservacions::class)
        ->call('updateTableColumnState', 'estado_observacion_id', $observacion->getKey(), $destino);

    expect($observacion->refresh()->estado_observacion_id)->not->toBe($destino);
});

it('el select no ofrece un estado inalcanzable según la máquina de estados', function () {
    $user = User::factory()->create(['role' => 'calidad']);
    // Terminal, sin transiciones salientes sembradas.
    $observacion = Observacion::withoutEvents(fn () => Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'subsanada_ok')->value('id'),
    ]));
    $destinoInalcanzable = EstadoObservacion::query()->where('codigo', 'pendiente')->value('id');

    $this->actingAs($user);
    Livewire::test(ListObservacions::class)
        ->call('updateTableColumnState', 'estado_observacion_id', $observacion->getKey(), $destinoInalcanzable);

    // No está en las opciones del select -> Rule::in([]) rechaza el valor,
    // no persiste (independiente de que TransicionEstadoGuard también lo
    // rechazaría si llegara a guardarse).
    expect($observacion->refresh()->estado_observacion_id)->not->toBe($destinoInalcanzable);
});

it('la acción Cerrar sigue disponible además del select, para capturar fecha_cierre y observacion_cierre', function () {
    $user = User::factory()->create(['role' => 'calidad']);
    $observacion = Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]);
    $destino = EstadoObservacion::query()->where('codigo', 'informativa')->value('id');

    $this->actingAs($user);
    Livewire::test(ListObservacions::class)
        ->mountTableAction('cerrar', $observacion)
        ->setTableActionData([
            'estado_observacion_id' => $destino,
            'fecha_cierre' => '2026-07-31',
            'observacion_cierre' => 'Cerrado desde la tabla.',
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    $observacion->refresh();
    expect($observacion->estado_observacion_id)->toBe($destino)
        ->and($observacion->observacion_cierre)->toBe('Cerrado desde la tabla.');
});
