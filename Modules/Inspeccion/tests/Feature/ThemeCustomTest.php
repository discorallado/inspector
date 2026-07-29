<?php

use App\Models\User;
use Modules\Inspeccion\Filament\Resources\ControlCambios\ControlCambioResource;
use Modules\Inspeccion\Filament\Resources\Observacions\ObservacionResource;

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
