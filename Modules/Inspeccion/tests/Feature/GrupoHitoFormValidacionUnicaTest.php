<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Filament\Resources\GrupoHitos\Pages\CreateGrupoHito;
use Modules\Inspeccion\Filament\Resources\GrupoHitos\Pages\EditGrupoHito;
use Modules\Inspeccion\Models\GrupoHito;

/**
 * Hallazgo de /revisor sobre PR5 (ADR 0011): MigrarHitosATareasCommand usa
 * grupo_hitos.nombre como clave natural para no duplicar Actividad — sin
 * esta validación, dos GrupoHito con el mismo nombre se fusionarían en
 * silencio bajo una sola Actividad.
 */
it('rechaza crear un GrupoHito con un nombre ya usado', function () {
    $user = User::factory()->create(['role' => 'super_admin']); // catalogo.gestionar
    GrupoHito::factory()->create(['nombre' => 'Armado de Tablero']);

    $this->actingAs($user);

    Livewire::test(CreateGrupoHito::class)
        ->fillForm([
            'nombre' => 'Armado de Tablero',
            'orden' => 1,
        ])
        ->call('create')
        ->assertHasFormErrors(['nombre']);
});

it('permite editar un GrupoHito manteniendo su propio nombre (unique ignora el registro actual)', function () {
    $user = User::factory()->create(['role' => 'super_admin']);
    $grupo = GrupoHito::factory()->create(['nombre' => 'Armado de Tablero']);

    $this->actingAs($user);

    Livewire::test(EditGrupoHito::class, ['record' => $grupo->getRouteKey()])
        ->fillForm(['nombre' => 'Armado de Tablero'])
        ->call('save')
        ->assertHasNoFormErrors();
});

it('permite crear un GrupoHito con un nombre nuevo', function () {
    $user = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($user);

    Livewire::test(CreateGrupoHito::class)
        ->fillForm([
            'nombre' => 'Fase nueva sin duplicar',
            'orden' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(GrupoHito::query()->where('nombre', 'Fase nueva sin duplicar')->exists())->toBeTrue();
});
