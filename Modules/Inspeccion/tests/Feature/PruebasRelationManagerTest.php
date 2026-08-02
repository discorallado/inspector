<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\EditTablero;
use Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers\PruebasRelationManager;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Prueba;
use Modules\Inspeccion\Models\PruebaItemLibrary;
use Modules\Inspeccion\Models\PruebaTemplate;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\VisitaInspeccion;

beforeEach(function () {
    $this->tablero = Tablero::factory()->for(Proyecto::factory())->create();

    $this->template = PruebaTemplate::factory()->create();
    $item = PruebaItemLibrary::factory()->create();
    $this->template->items()->attach($item->id, ['orden' => 1]);
});

it('supervisor (prueba.completar) crea una Prueba desde el relation manager de Tablero, con snapshot de ítems', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $this->actingAs($user);

    Livewire::test(PruebasRelationManager::class, [
        'ownerRecord' => $this->tablero,
        'pageClass' => EditTablero::class,
    ])
        ->call('mountTableAction', 'create')
        ->fillForm([
            'prueba_template_id' => $this->template->id,
        ])
        ->call('callMountedTableAction')
        ->assertHasNoTableActionErrors();

    $prueba = Prueba::query()->where('tablero_id', $this->tablero->id)->first();

    expect($prueba)->not->toBeNull()
        ->and($prueba->visita_inspeccion_id)->toBeNull()
        ->and($prueba->items)->toHaveCount(1);
});

it('permite elegir una visita del tablero al crear la Prueba, pero no es obligatorio', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $this->actingAs($user);

    $visita = VisitaInspeccion::factory()->create();
    $visita->tableros()->attach($this->tablero);

    Livewire::test(PruebasRelationManager::class, [
        'ownerRecord' => $this->tablero,
        'pageClass' => EditTablero::class,
    ])
        ->call('mountTableAction', 'create')
        ->fillForm([
            'visita_inspeccion_id' => $visita->id,
            'prueba_template_id' => $this->template->id,
        ])
        ->call('callMountedTableAction')
        ->assertHasNoTableActionErrors();

    $prueba = Prueba::query()->where('tablero_id', $this->tablero->id)->first();

    expect($prueba->visita_inspeccion_id)->toBe($visita->id);
});

it('tecnico (sin prueba.completar) no ve la acción de crear Prueba', function () {
    $user = User::factory()->create(['role' => 'tecnico']);
    $this->actingAs($user);

    Livewire::test(PruebasRelationManager::class, [
        'ownerRecord' => $this->tablero,
        'pageClass' => EditTablero::class,
    ])
        ->assertTableActionHidden('create');
});

/**
 * Filament no saca el campo del DOM cuando ->visible() es false — le
 * agrega la clase fi-hidden al wrapper (necesita seguir trackeando el
 * campo para el estado de Livewire). Por eso la aserción real es sobre
 * esa clase en el bloque que envuelve al campo, no sobre si el string
 * "prueba_template_id" aparece o no en el HTML (aparece siempre, oculto
 * o no) — un test contra eso habría pasado igual sin probar nada real.
 */
it('el campo prueba_template_id del form real de PruebaResource solo aparece al crear, no al editar (guardrail $operation)', function () {
    $user = User::factory()->create(['role' => 'super_admin']);

    $prueba = Prueba::crearDesdeTemplate([
        'tablero_id' => $this->tablero->id,
    ], $this->template);

    $htmlCreate = $this->actingAs($user)->get('/admin/pruebas/create')->getContent();
    $htmlEdit = $this->actingAs($user)->get("/admin/pruebas/{$prueba->id}/edit")->getContent();

    // strpos() a secas encuentra primero el wire:snapshot (JSON con todos
    // los atributos del modelo, antes que el HTML del campo en sí) — hay
    // que buscar el wire:key del campo puntual, no el string suelto.
    $marcadorCreate = strpos($htmlCreate, 'form.prueba_template_id');
    $marcadorEdit = strpos($htmlEdit, 'form.prueba_template_id');

    $bloqueCreate = substr($htmlCreate, max(0, $marcadorCreate - 200), 400);
    $bloqueEdit = substr($htmlEdit, max(0, $marcadorEdit - 200), 400);

    expect($bloqueCreate)->not->toContain('fi-hidden');
    expect($bloqueEdit)->toContain('fi-hidden');
});
