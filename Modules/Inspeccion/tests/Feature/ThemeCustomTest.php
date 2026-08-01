<?php

use App\Models\User;
use Modules\Inspeccion\Filament\Resources\ControlCambios\ControlCambioResource;
use Modules\Inspeccion\Filament\Resources\Observacions\ObservacionResource;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;

/**
 * PR3 (ADR 0006/0007): antes de este theme, el panel admin usaba solo el
 * CSS ya compilado de Filament, que no incluye clases Tailwind de vistas
 * fuera de Filament core (relaticle/flowforge, páginas custom del módulo).
 * Estos tests verifican que el theme custom quede realmente enganchado —
 * no alcanza con que compile, tiene que estar referenciado en el HTML que
 * el navegador recibe.
 */
it('el panel admin referencia el theme custom compilado, no solo el CSS default de Filament', function () {
    $user = User::factory()->create(['role' => 'super_admin']);

    $response = $this->actingAs($user)->get('/admin');

    $response->assertSuccessful();
    expect($response->getContent())->toContain('build/assets/theme-');
});

it('las páginas del módulo Inspeccion también cargan el theme custom', function () {
    $user = User::factory()->create(['role' => 'calidad']);

    $this->actingAs($user);

    expect($this->get(ObservacionResource::getUrl('index'))->getContent())->toContain('build/assets/theme-');
    expect($this->get(ControlCambioResource::getUrl('index'))->getContent())->toContain('build/assets/theme-');
});

/**
 * Fork de layout/index.blade.php (ver el archivo para el porqué): el
 * topbar se calcula los breadcrumbs reales de la página activa
 * ($livewire->getBreadcrumbs(), $livewire ahí es la página, no el
 * topbar) para inyectarlos en su propia fila vía CSS — un topbar sin
 * breadcrumbs de verdad adentro sería un regresión silenciosa si algún
 * día se toca esa vista y se rompe el cálculo.
 */
it('los breadcrumbs reales de la página se inyectan en el wrapper del topbar', function () {
    $user = User::factory()->create(['role' => 'super_admin']);
    $tablero = Tablero::factory()->for(Proyecto::factory())->create();

    $html = $this->actingAs($user)->get("/admin/tableros/{$tablero->id}/edit")->getContent();

    expect($html)->toContain('fi-topbar-with-breadcrumbs');

    $start = strpos($html, 'fi-topbar-injected-breadcrumbs');
    expect($start)->not->toBeFalse();

    $chunk = substr($html, $start, 600);
    expect($chunk)->toContain('/admin/tableros')
        ->toContain('fi-breadcrumbs-item-label');
});
