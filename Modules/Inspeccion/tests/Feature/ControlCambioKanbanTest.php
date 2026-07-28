<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Exceptions\TransicionEstadoInvalidaException;
use Modules\Inspeccion\Filament\Resources\ControlCambios\ControlCambioResource;
use Modules\Inspeccion\Filament\Resources\ControlCambios\Pages\ControlCambiosBoard;
use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);

    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create();
});

it('un usuario con tablero.ver puede cargar el kanban de control de cambios', function () {
    $user = User::factory()->create(['role' => 'supervisor']);

    $this->actingAs($user)->get(ControlCambioResource::getUrl('board'))->assertSuccessful();
});

it('un usuario sin rol asignado no puede cargar el kanban de control de cambios', function () {
    $user = User::factory()->create(['role' => null]);

    $this->actingAs($user)->get(ControlCambioResource::getUrl('board'))->assertForbidden();
});

it('mover una card a un estado permitido persiste el nuevo estado', function () {
    $user = User::factory()->create(['role' => 'supervisor']); // control_cambio.decidir
    $cambio = ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);
    $destino = EstadoCambio::query()->where('codigo', 'aprobado')->first();

    $this->actingAs($user);
    Livewire::test(ControlCambiosBoard::class)
        ->call('moveCard', (string) $cambio->id, (string) $destino->id);

    expect($cambio->refresh()->estado_cambio_id)->toBe($destino->id);
});

it('mover una card a un estado no permitido por la máquina de estados se rechaza server-side', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    // Rechazado es terminal (sin transiciones salientes sembradas).
    $cambio = ControlCambio::withoutEvents(fn () => ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'rechazado')->value('id'),
    ]));
    $destino = EstadoCambio::query()->where('codigo', 'propuesto')->first();

    $this->actingAs($user);

    expect(fn () => Livewire::test(ControlCambiosBoard::class)
        ->call('moveCard', (string) $cambio->id, (string) $destino->id))
        ->toThrow(TransicionEstadoInvalidaException::class);

    expect($cambio->refresh()->estado_cambio_id)->not->toBe($destino->id);
});

it('un usuario sin ninguna ability de control de cambios no puede mover cards aunque el board le cargue', function () {
    $user = User::factory()->create(['role' => 'calidad']); // no tiene proponer/decidir/implementar
    $cambio = ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);
    $destino = EstadoCambio::query()->where('codigo', 'aprobado')->first();

    $this->actingAs($user);

    // AuthorizationException queda convertida en una respuesta 403 por
    // Livewire::test(), no burbujea como excepción de PHP — se verifica
    // el efecto (nada se persistió), igual que en ObservacionKanbanTest.
    Livewire::test(ControlCambiosBoard::class)
        ->call('moveCard', (string) $cambio->id, (string) $destino->id);

    expect($cambio->refresh()->estado_cambio_id)->not->toBe($destino->id);
});

it('no se puede mover una card que ya fue borrada lógicamente', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $cambio = ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);
    $cambio->delete();
    $destino = EstadoCambio::query()->where('codigo', 'aprobado')->first();

    $this->actingAs($user);

    expect(fn () => Livewire::test(ControlCambiosBoard::class)
        ->call('moveCard', (string) $cambio->id, (string) $destino->id))
        ->toThrow(InvalidArgumentException::class);
});

it('mover un cardId inexistente lanza una excepción en vez de fallar en silencio', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $destino = EstadoCambio::query()->where('codigo', 'aprobado')->first();

    $this->actingAs($user);

    expect(fn () => Livewire::test(ControlCambiosBoard::class)
        ->call('moveCard', '999999', (string) $destino->id))
        ->toThrow(InvalidArgumentException::class);
});

it('un ControlCambio nuevo entra a su columna con una posición base, no null', function () {
    $propuesto = EstadoCambio::query()->where('codigo', 'propuesto')->value('id');

    $primero = ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => $propuesto,
    ]);

    expect($primero->posicion)->not->toBeNull();

    $segundo = ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => $propuesto,
    ]);

    expect((float) $segundo->posicion)->toBeGreaterThan((float) $primero->posicion);
});

it('la acción Aprobar reutilizada en una card del board persiste el nuevo estado', function () {
    $user = User::factory()->create(['role' => 'supervisor']); // control_cambio.decidir
    $cambio = ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);

    $this->actingAs($user);

    Livewire::test(ControlCambiosBoard::class)
        ->mountAction('aprobar', ['recordKey' => (string) $cambio->id])
        ->assertActionMounted('aprobar')
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($cambio->refresh()->estadoCambio->codigo)->toBe('aprobado');
});

it('un usuario sin control_cambio.decidir no puede mountear la acción Aprobar sobre una card puntual', function () {
    $user = User::factory()->create(['role' => 'calidad']);
    $cambio = ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);

    $this->actingAs($user);

    Livewire::test(ControlCambiosBoard::class)
        ->mountAction('aprobar', ['recordKey' => (string) $cambio->id])
        ->assertActionNotMounted('aprobar');
});

it('la acción Implementar (ability distinta a Aprobar) también funciona desde una card del board', function () {
    $user = User::factory()->create(['role' => 'ingeniero']); // control_cambio.implementar
    $cambio = ControlCambio::withoutEvents(fn () => ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'aprobado')->value('id'),
    ]));

    $this->actingAs($user);

    Livewire::test(ControlCambiosBoard::class)
        ->mountAction('implementar', ['recordKey' => (string) $cambio->id])
        ->assertActionMounted('implementar')
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($cambio->refresh()->estadoCambio->codigo)->toBe('implementado');
});

it('supervisor (decidir, sin implementar) no puede mountear la acción Implementar', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $cambio = ControlCambio::withoutEvents(fn () => ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'aprobado')->value('id'),
    ]));

    $this->actingAs($user);

    Livewire::test(ControlCambiosBoard::class)
        ->mountAction('implementar', ['recordKey' => (string) $cambio->id])
        ->assertActionNotMounted('implementar');
});

it('la acción Rechazar también funciona desde una card del board', function () {
    $user = User::factory()->create(['role' => 'supervisor']); // control_cambio.decidir
    $cambio = ControlCambio::factory()->for($this->tablero)->create([
        'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'propuesto')->value('id'),
    ]);

    $this->actingAs($user);

    Livewire::test(ControlCambiosBoard::class)
        ->mountAction('rechazar', ['recordKey' => (string) $cambio->id])
        ->assertActionMounted('rechazar')
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($cambio->refresh()->estadoCambio->codigo)->toBe('rechazado');
});

it('el board incluye el asset JS de flowforge y muestra las 4 columnas del catálogo', function () {
    $user = User::factory()->create(['role' => 'supervisor']);

    $response = $this->actingAs($user)->get(ControlCambioResource::getUrl('board'));

    $response->assertSuccessful()
        ->assertSee('Propuesto')
        ->assertSee('Aprobado')
        ->assertSee('Rechazado')
        ->assertSee('Implementado');

    expect($response->getContent())->toContain('relaticle/flowforge');
});

it('el listado de Control de Cambios muestra el botón Ver Kanban hacia el board', function () {
    $user = User::factory()->create(['role' => 'supervisor']);

    $this->actingAs($user)
        ->get(ControlCambioResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee(__('inspeccion.control_cambio.acciones.ver_kanban'));
});
