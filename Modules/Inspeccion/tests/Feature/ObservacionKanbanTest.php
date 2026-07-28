<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Exceptions\TransicionEstadoInvalidaException;
use Modules\Inspeccion\Filament\Resources\Observacions\ObservacionResource;
use Modules\Inspeccion\Filament\Resources\Observacions\Pages\ObservacionesBoard;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\VisitaInspeccion;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);

    $this->visita = VisitaInspeccion::factory()->for(Proyecto::factory())->create();
});

it('un usuario con tablero.ver puede cargar el kanban de observaciones', function () {
    $user = User::factory()->create(['role' => 'calidad']);

    $this->actingAs($user)->get(ObservacionResource::getUrl('board'))->assertSuccessful();
});

it('un usuario sin rol asignado no puede cargar el kanban de observaciones', function () {
    $user = User::factory()->create(['role' => null]);

    $this->actingAs($user)->get(ObservacionResource::getUrl('board'))->assertForbidden();
});

it('mover una card a un estado permitido persiste el nuevo estado', function () {
    $user = User::factory()->create(['role' => 'calidad']);
    $observacion = Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]);
    $destino = EstadoObservacion::query()->where('codigo', 'subsanada_ok')->first();

    $this->actingAs($user);
    Livewire::test(ObservacionesBoard::class)
        ->call('moveCard', (string) $observacion->id, (string) $destino->id);

    expect($observacion->refresh()->estado_observacion_id)->toBe($destino->id);
});

it('mover una card a un estado no permitido por la máquina de estados se rechaza server-side', function () {
    $user = User::factory()->create(['role' => 'calidad']);
    // Estado terminal creado directamente (sin pasar por la transición), igual que en ObservacionFlowTest.
    $observacion = Observacion::withoutEvents(fn () => Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'subsanada_ok')->value('id'),
    ]));
    $destino = EstadoObservacion::query()->where('codigo', 'pendiente')->first();

    $this->actingAs($user);

    expect(fn () => Livewire::test(ObservacionesBoard::class)
        ->call('moveCard', (string) $observacion->id, (string) $destino->id))
        ->toThrow(TransicionEstadoInvalidaException::class);

    expect($observacion->refresh()->estado_observacion_id)->not->toBe($destino->id);
});

it('un usuario sin permiso de cerrar observaciones no puede mover cards del kanban aunque el board le cargue', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $observacion = Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]);
    $destino = EstadoObservacion::query()->where('codigo', 'subsanada_ok')->first();

    $this->actingAs($user);

    // Livewire::test() maneja AuthorizationException como una respuesta 403
    // en vez de dejarla burbujear como excepción de PHP (a diferencia de
    // TransicionEstadoInvalidaException, que sí burbujea) — por eso acá se
    // verifica el efecto (nada se persistió) y no un throw.
    Livewire::test(ObservacionesBoard::class)
        ->call('moveCard', (string) $observacion->id, (string) $destino->id);

    expect($observacion->refresh()->estado_observacion_id)->not->toBe($destino->id);
});

it('no se puede mover una card que ya fue borrada lógicamente', function () {
    $user = User::factory()->create(['role' => 'calidad']);
    $observacion = Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]);
    $observacion->delete();
    $destino = EstadoObservacion::query()->where('codigo', 'subsanada_ok')->first();

    $this->actingAs($user);

    // El SoftDeletes de Observacion ya excluye trashed del scope por defecto
    // que usa el board, así que Flowforge no encuentra la card y rechaza el
    // movimiento en vez de revivirla o moverla igual.
    expect(fn () => Livewire::test(ObservacionesBoard::class)
        ->call('moveCard', (string) $observacion->id, (string) $destino->id))
        ->toThrow(InvalidArgumentException::class);
});

it('mover un cardId inexistente lanza una excepción en vez de fallar en silencio', function () {
    $user = User::factory()->create(['role' => 'calidad']);
    $destino = EstadoObservacion::query()->where('codigo', 'subsanada_ok')->first();

    $this->actingAs($user);

    expect(fn () => Livewire::test(ObservacionesBoard::class)
        ->call('moveCard', '999999', (string) $destino->id))
        ->toThrow(InvalidArgumentException::class);
});

it('una Observacion nueva entra a su columna con una posición base, no null', function () {
    $pendiente = EstadoObservacion::query()->where('codigo', 'pendiente')->value('id');

    $primera = Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => $pendiente,
    ]);

    expect($primera->posicion)->not->toBeNull();

    $segunda = Observacion::factory()->for($this->visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => $pendiente,
    ]);

    // La segunda queda después de la primera dentro de la misma columna.
    expect((float) $segunda->posicion)->toBeGreaterThan((float) $primera->posicion);
});
