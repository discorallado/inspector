<?php

use App\Models\User;
use Modules\Inspeccion\Filament\Resources\ControlCambios\ControlCambioResource;
use Modules\Inspeccion\Filament\Resources\Observacions\ObservacionResource;
use Modules\Inspeccion\Filament\Resources\Proyectos\ProyectoResource;
use Modules\Inspeccion\Filament\Resources\Pruebas\PruebaResource;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\VisitaInspeccionResource;

/**
 * ADR de reordenamiento de sidebar: Proyecto reactivado, ControlCambio y
 * VisitaInspeccion pierden su ítem propio (se llega a ellos vía relation
 * manager), Observacion pasa del cluster desarmado a un NavigationGroup
 * top-level nuevo. Estas propiedades static no dependen de una request
 * HTTP — se verifican directo contra la clase, sin necesidad de login.
 */
it('Proyecto tiene navegación activa', function () {
    expect(ProyectoResource::shouldRegisterNavigation())->toBeTrue();
});

it('ControlCambio y VisitaInspeccion ya no tienen ítem propio en el sidebar', function () {
    expect(ControlCambioResource::shouldRegisterNavigation())->toBeFalse()
        ->and(VisitaInspeccionResource::shouldRegisterNavigation())->toBeFalse();
});

it('Prueba (ex-ChecklistEjecucion) sigue sin ítem propio en el sidebar', function () {
    expect(PruebaResource::shouldRegisterNavigation())->toBeFalse();
});

it('Observacion vive en el grupo de sidebar Inspección, no en un cluster', function () {
    expect(ObservacionResource::getNavigationGroup())->toBe(__('inspeccion.navigation.grupo_inspeccion'))
        ->and(ObservacionResource::getCluster())->toBeNull();
});

it('el orden del sidebar es Proyecto -> Tablero -> (grupo Inspección) -> Configuración', function () {
    expect(ProyectoResource::getNavigationSort())->toBe(1)
        ->and(TableroResource::getNavigationSort())->toBe(2)
        ->and(ObservacionResource::getNavigationSort())->toBe(3);
});

it('carga el listado de Observaciones (aggregate cross-tablero) sin errores', function () {
    $user = User::factory()->create(['role' => 'calidad']);

    $this->actingAs($user)->get('/admin/observacions')->assertSuccessful();
});
