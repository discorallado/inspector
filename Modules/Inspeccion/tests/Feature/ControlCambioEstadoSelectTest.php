<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Filament\Resources\ControlCambios\Pages\ListControlCambios;
use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;

/**
 * ADR 0008: la tabla de Control de Cambios reemplaza al kanban de PR2
 * (ADR 0005) — el cambio de estado ahora es un SelectColumn inline, y
 * aprobar/rechazar/implementar/desimplementar quedan agrupadas en un
 * ActionGroup.
 */
beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);

    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create();
});

it('supervisor (decidir) puede aprobar un cambio desde el select', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $cambio = ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);
    $destino = EstadoCambio::query()->where('codigo', 'aprobado')->value('id');

    $this->actingAs($user);
    Livewire::test(ListControlCambios::class)
        ->call('updateTableColumnState', 'estado_cambio_id', $cambio->getKey(), $destino);

    expect($cambio->refresh()->estado_cambio_id)->toBe($destino);
});

it('un rol con solo control_cambio.proponer NO puede aprobar desde el select (el destino no está en las opciones)', function () {
    $user = User::factory()->create(['role' => 'tecnico']); // solo proponer
    $cambio = ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);
    $destino = EstadoCambio::query()->where('codigo', 'aprobado')->value('id');

    $this->actingAs($user);
    Livewire::test(ListControlCambios::class)
        ->call('updateTableColumnState', 'estado_cambio_id', $cambio->getKey(), $destino);

    expect($cambio->refresh()->estado_cambio_id)->not->toBe($destino);
});

it('supervisor (decidir, sin implementar) NO puede marcar implementado desde el select', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $cambio = ControlCambio::withoutEvents(fn () => ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'aprobado')->value('id'),
    ]));
    $destino = EstadoCambio::query()->where('codigo', 'implementado')->value('id');

    $this->actingAs($user);
    Livewire::test(ListControlCambios::class)
        ->call('updateTableColumnState', 'estado_cambio_id', $cambio->getKey(), $destino);

    expect($cambio->refresh()->estado_cambio_id)->not->toBe($destino);
});

it('ingeniero (implementar) sí puede marcar implementado desde el select', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $cambio = ControlCambio::withoutEvents(fn () => ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'aprobado')->value('id'),
    ]));
    $destino = EstadoCambio::query()->where('codigo', 'implementado')->value('id');

    $this->actingAs($user);
    Livewire::test(ListControlCambios::class)
        ->call('updateTableColumnState', 'estado_cambio_id', $cambio->getKey(), $destino);

    expect($cambio->refresh()->estado_cambio_id)->toBe($destino);
});

it('la acción Desimplementar revierte Implementado -> Aprobado', function () {
    $user = User::factory()->create(['role' => 'ingeniero']); // control_cambio.implementar
    $cambio = ControlCambio::withoutEvents(fn () => ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'implementado')->value('id'),
    ]));
    $aprobado = EstadoCambio::query()->where('codigo', 'aprobado')->value('id');

    $this->actingAs($user);
    Livewire::test(ListControlCambios::class)
        ->mountTableAction('desimplementar', $cambio)
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    expect($cambio->refresh()->estado_cambio_id)->toBe($aprobado);
});

it('supervisor (decidir, sin implementar) no puede mountear Desimplementar', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $cambio = ControlCambio::withoutEvents(fn () => ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'implementado')->value('id'),
    ]));

    $this->actingAs($user);
    Livewire::test(ListControlCambios::class)
        ->mountTableAction('desimplementar', $cambio)
        ->assertTableActionNotMounted('desimplementar');
});

it('supervisor (decidir, sin implementar) NO puede revertir Implementado -> Aprobado desde el select (hallazgo /revisor)', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $cambio = ControlCambio::withoutEvents(fn () => ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'implementado')->value('id'),
    ]));
    $aprobado = EstadoCambio::query()->where('codigo', 'aprobado')->value('id');

    $this->actingAs($user);
    Livewire::test(ListControlCambios::class)
        ->call('updateTableColumnState', 'estado_cambio_id', $cambio->getKey(), $aprobado);

    expect($cambio->refresh()->estado_cambio_id)->not->toBe($aprobado);
});
