<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Inspeccion\Filament\Resources\Proyectos\Pages\EditProyecto;
use Modules\Inspeccion\Filament\Resources\Proyectos\RelationManagers\VisitasRelationManager;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\VisitaInspeccion;

beforeEach(function () {
    $this->proyecto = Proyecto::factory()->create();
    $this->tablero = Tablero::factory()->for($this->proyecto)->create();
});

it('supervisor crea una VisitaInspeccion desde el relation manager de Proyecto', function () {
    $user = User::factory()->create(['role' => 'supervisor']);
    $this->actingAs($user);

    Livewire::test(VisitasRelationManager::class, [
        'ownerRecord' => $this->proyecto,
        'pageClass' => EditProyecto::class,
    ])
        ->call('mountTableAction', 'create')
        ->fillForm([
            'inspector_id' => $user->id,
            'fecha' => now()->toDateString(),
            'tableros' => [$this->tablero->id],
        ])
        ->call('callMountedTableAction')
        ->assertHasNoTableActionErrors();

    expect(VisitaInspeccion::query()->where('proyecto_id', $this->proyecto->id)->exists())->toBeTrue();
});

it('no permite adjuntar un tablero de OTRO proyecto al crear la Visita', function () {
    $user = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($user);

    $otroProyecto = Proyecto::factory()->create();
    $tableroAjeno = Tablero::factory()->for($otroProyecto)->create();

    Livewire::test(VisitasRelationManager::class, [
        'ownerRecord' => $this->proyecto,
        'pageClass' => EditProyecto::class,
    ])
        ->call('mountTableAction', 'create')
        ->fillForm([
            'inspector_id' => $user->id,
            'fecha' => now()->toDateString(),
            // El tablero ajeno no está en options() (acotado a
            // $this->getOwnerRecord()->tableros()) — Filament valida el
            // submit contra esas opciones, mismo criterio que el
            // hallazgo de /revisor sobre SelectColumn en ADR 0008.
            'tableros' => [$tableroAjeno->id],
        ])
        ->call('callMountedTableAction')
        ->assertHasTableActionErrors(['tableros.0']);

    $visita = VisitaInspeccion::query()->where('proyecto_id', $this->proyecto->id)->first();
    expect($visita)->toBeNull();
});

it('no ve la acción de crear Visita sin el permiso visita_inspeccion.gestionar', function () {
    // tecnico no está en la lista de roles de visita_inspeccion.gestionar
    // (config/inspeccion.php) — el form/acción sigue las Policies de
    // VisitaInspeccion, que ya validaban esto antes del reordenamiento.
    $user = User::factory()->create(['role' => 'tecnico']);
    $this->actingAs($user);

    Livewire::test(VisitasRelationManager::class, [
        'ownerRecord' => $this->proyecto,
        'pageClass' => EditProyecto::class,
    ])
        ->assertTableActionHidden('create');
});
