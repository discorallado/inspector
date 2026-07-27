<?php

use App\Models\User;
use Modules\Inspeccion\Database\Seeders\InspeccionDatabaseSeeder;

it('carga el dashboard con los widgets del módulo', function () {
    $this->seed(InspeccionDatabaseSeeder::class);
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin)->get('/admin')->assertSuccessful();
});
