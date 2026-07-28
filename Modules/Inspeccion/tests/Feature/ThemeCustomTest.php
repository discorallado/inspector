<?php

use App\Models\User;
use Modules\Inspeccion\Filament\Resources\Observacions\ObservacionResource;

/**
 * PR3 (ADR 0006/0007): antes de este theme, el panel admin usaba solo el
 * CSS ya compilado de Filament, que no incluye las clases Tailwind que
 * usan las vistas de relaticle/flowforge (por eso el kanban se veía sin
 * estilos). Estos tests verifican que el theme custom quede realmente
 * enganchado — no alcanza con que compile, tiene que estar referenciado
 * en el HTML que el navegador recibe.
 */
it('el panel admin referencia el theme custom compilado, no solo el CSS default de Filament', function () {
    $user = User::factory()->create(['role' => 'super_admin']);

    $response = $this->actingAs($user)->get('/admin');

    $response->assertSuccessful();
    expect($response->getContent())->toContain('build/assets/theme-');
});

it('el kanban de Observaciones también carga el theme custom (no solo la página de login/dashboard)', function () {
    $user = User::factory()->create(['role' => 'calidad']);

    $response = $this->actingAs($user)->get(ObservacionResource::getUrl('board'));

    $response->assertSuccessful();
    expect($response->getContent())->toContain('build/assets/theme-');
});
