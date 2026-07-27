<?php

use App\Models\User;
use Modules\Inspeccion\Database\Seeders\InspeccionDatabaseSeeder;

beforeEach(function () {
    $this->seed(InspeccionDatabaseSeeder::class);
    $this->admin = User::factory()->create(['role' => 'super_admin']);
});

it('carga cada página de listado del panel sin errores', function (string $url) {
    $this->actingAs($this->admin)->get($url)->assertSuccessful();
})->with([
    '/admin/proyectos',
    '/admin/tableros',
    '/admin/visita-inspeccions',
    '/admin/observacions',
    '/admin/control-cambios',
    '/admin/grupo-hitos',
    '/admin/estado-avances',
    '/admin/especialidads',
    '/admin/tipo-observacions',
    '/admin/severidads',
    '/admin/estado-observacions',
    '/admin/estado-cambios',
    '/admin/resultado-checklists',
    '/admin/transicion-estado-permitidas',
    '/admin/checklist-item-libraries',
    '/admin/checklist-templates',
    '/admin/checklist-ejecucions',
]);
