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

beforeEach(function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);
});

it('un rol sin ningún permiso sobre Observacion NO puede borrarla en bulk (deleteAny)', function () {
    // 'tecnico' no aparece en ninguna ability de observacion en config/inspeccion.php
    $user = User::factory()->create(['role' => 'tecnico']);
    $visita = VisitaInspeccion::factory()->for(Proyecto::factory())->create(['inspector_id' => $user->id]);
    $observacion = Observacion::factory()->for($visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]);

    Livewire::actingAs($user)
        ->test(ListObservacions::class)
        ->assertTableBulkActionHidden('delete');

    expect(Observacion::query()->find($observacion->id))->not->toBeNull();
});

it('un rol con permiso sobre Observacion sí puede borrarla en bulk', function () {
    $user = User::factory()->create(['role' => 'calidad']);
    $visita = VisitaInspeccion::factory()->for(Proyecto::factory())->create(['inspector_id' => $user->id]);
    $observacion = Observacion::factory()->for($visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]);

    Livewire::actingAs($user)
        ->test(ListObservacions::class)
        ->assertTableBulkActionVisible('delete')
        ->callTableBulkAction('delete', [$observacion]);

    expect(Observacion::query()->find($observacion->id))->toBeNull();
});

it('solo super_admin puede purgar definitivamente (forceDeleteAny) una Observacion', function () {
    $calidad = User::factory()->create(['role' => 'calidad']);
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $visita = VisitaInspeccion::factory()->for(Proyecto::factory())->create(['inspector_id' => $calidad->id]);
    $observacion = Observacion::factory()->for($visita, 'visitaInspeccion')->create([
        'estado_observacion_id' => EstadoObservacion::query()->where('codigo', 'pendiente')->value('id'),
    ]);
    $observacion->delete();

    Livewire::actingAs($calidad)
        ->test(ListObservacions::class, ['tableFilters' => ['trashed' => ['value' => '1']]])
        ->assertTableBulkActionHidden('forceDelete');

    Livewire::actingAs($superAdmin)
        ->test(ListObservacions::class, ['tableFilters' => ['trashed' => ['value' => '1']]])
        ->assertTableBulkActionVisible('forceDelete');
});
