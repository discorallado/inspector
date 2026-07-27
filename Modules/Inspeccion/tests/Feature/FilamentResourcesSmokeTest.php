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
    '/admin/control-cambios',
    '/admin/inspeccion-calidad/visita-inspeccions',
    '/admin/inspeccion-calidad/observacions',
    '/admin/inspeccion-calidad/checklist-ejecucions',
    '/admin/configuracion/grupo-hitos',
    '/admin/configuracion/estado-avances',
    '/admin/configuracion/especialidads',
    '/admin/configuracion/tipo-observacions',
    '/admin/configuracion/severidads',
    '/admin/configuracion/estado-observacions',
    '/admin/configuracion/estado-cambios',
    '/admin/configuracion/resultado-checklists',
    '/admin/configuracion/transicion-estado-permitidas',
    '/admin/configuracion/checklist-item-libraries',
    '/admin/configuracion/checklist-templates',
]);
