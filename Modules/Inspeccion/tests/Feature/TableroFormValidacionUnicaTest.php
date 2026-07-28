<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\CreateTablero;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;

it('crea un Tablero desde el form sin que la regla unique del campo tag rompa', function () {
    $user = User::factory()->create(['role' => 'ingeniero']); // tablero.gestionar
    $proyecto = Proyecto::factory()->create();

    $this->actingAs($user);

    Livewire::test(CreateTablero::class)
        ->fillForm([
            'proyecto_id' => $proyecto->id,
            'tag' => 'TP-QA',
            'nombre' => 'Tablero de prueba',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Tablero::query()->where('tag', 'TP-QA')->exists())->toBeTrue();
});

it('la regla unique del tag está scopeada por proyecto: mismo tag en otro proyecto es válido', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $proyectoA = Proyecto::factory()->create();
    $proyectoB = Proyecto::factory()->create();
    Tablero::factory()->for($proyectoA)->create(['tag' => 'TP-DUP']);

    $this->actingAs($user);

    Livewire::test(CreateTablero::class)
        ->fillForm([
            'proyecto_id' => $proyectoB->id,
            'tag' => 'TP-DUP',
            'nombre' => 'Tablero en otro proyecto',
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('la regla unique del tag rechaza un tag repetido dentro del mismo proyecto', function () {
    $user = User::factory()->create(['role' => 'ingeniero']);
    $proyecto = Proyecto::factory()->create();
    Tablero::factory()->for($proyecto)->create(['tag' => 'TP-DUP']);

    $this->actingAs($user);

    Livewire::test(CreateTablero::class)
        ->fillForm([
            'proyecto_id' => $proyecto->id,
            'tag' => 'TP-DUP',
            'nombre' => 'Duplicado',
        ])
        ->call('create')
        ->assertHasFormErrors(['tag']);
});
