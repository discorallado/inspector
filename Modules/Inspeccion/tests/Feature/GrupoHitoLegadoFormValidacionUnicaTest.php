<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Filament\Resources\GrupoHitoLegados\Pages\CreateGrupoHitoLegado;
use Modules\Inspeccion\Filament\Resources\GrupoHitoLegados\Pages\EditGrupoHitoLegado;
use Modules\Inspeccion\Models\GrupoHitoLegado;

/**
 * Hallazgo de /revisor sobre PR5 (ADR 0011): MigrarHitosATareasCommand usa
 * grupo_hitos.nombre como clave natural para no duplicar Actividad — sin
 * esta validación, dos GrupoHitoLegado con el mismo nombre se fusionarían
 * en silencio bajo una sola Actividad.
 */
it('rechaza crear un GrupoHitoLegado con un nombre ya usado', function () {
    $user = User::factory()->create(['role' => 'super_admin']); // catalogo.gestionar
    GrupoHitoLegado::factory()->create(['nombre' => 'Armado de Tablero']);

    $this->actingAs($user);

    Livewire::test(CreateGrupoHitoLegado::class)
        ->fillForm([
            'nombre' => 'Armado de Tablero',
            'orden' => 1,
        ])
        ->call('create')
        ->assertHasFormErrors(['nombre']);
});

it('permite editar un GrupoHitoLegado manteniendo su propio nombre (unique ignora el registro actual)', function () {
    $user = User::factory()->create(['role' => 'super_admin']);
    $grupo = GrupoHitoLegado::factory()->create(['nombre' => 'Armado de Tablero']);

    $this->actingAs($user);

    Livewire::test(EditGrupoHitoLegado::class, ['record' => $grupo->getRouteKey()])
        ->fillForm(['nombre' => 'Armado de Tablero'])
        ->call('save')
        ->assertHasNoFormErrors();
});

it('permite crear un GrupoHitoLegado con un nombre nuevo', function () {
    $user = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($user);

    Livewire::test(CreateGrupoHitoLegado::class)
        ->fillForm([
            'nombre' => 'Fase nueva sin duplicar',
            'orden' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(GrupoHitoLegado::query()->where('nombre', 'Fase nueva sin duplicar')->exists())->toBeTrue();
});
