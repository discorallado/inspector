<?php

use Illuminate\Support\Facades\Artisan;
use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;

/**
 * ADR 0010 / hallazgo de /revisor: el down() de
 * 2026_08_01_090000_add_codigo_a_transiciones_estado_permitidas intenta
 * devolver estado_destino_id a NOT NULL, pero TransicionEstadoPermitidaSeeder
 * ya siembra 8 filas tipo_catalogo='tarea_status' con estado_destino_id
 * NULL (son basadas en código, no en id) — sin borrar esas filas antes,
 * el ALTER fallaba con una violación de NOT NULL en cuanto el seeder
 * hubiera corrido. La primera validación manual de esta migración (sin
 * seeder de por medio) no lo detectó.
 */
it('el rollback de la migración de columnas de código funciona con filas tarea_status ya sembradas', function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);

    $exitCode = Artisan::call('migrate:rollback', [
        '--step' => 1,
        '--path' => 'Modules/Inspeccion/database/migrations/2026_08_01_090300_create_tarea_links_table.php',
        '--force' => true,
    ]);
    expect($exitCode)->toBe(0);

    $exitCode = Artisan::call('migrate:rollback', [
        '--step' => 1,
        '--path' => 'Modules/Inspeccion/database/migrations/2026_08_01_090200_create_tareas_table.php',
        '--force' => true,
    ]);
    expect($exitCode)->toBe(0);

    $exitCode = Artisan::call('migrate:rollback', [
        '--step' => 1,
        '--path' => 'Modules/Inspeccion/database/migrations/2026_08_01_090100_create_actividades_table.php',
        '--force' => true,
    ]);
    expect($exitCode)->toBe(0);

    $exitCode = Artisan::call('migrate:rollback', [
        '--step' => 1,
        '--path' => 'Modules/Inspeccion/database/migrations/2026_08_01_090000_add_codigo_a_transiciones_estado_permitidas.php',
        '--force' => true,
    ]);
    expect($exitCode)->toBe(0);
});
