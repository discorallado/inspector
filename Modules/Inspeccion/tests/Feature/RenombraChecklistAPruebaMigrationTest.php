<?php

use Illuminate\Support\Facades\Artisan;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Prueba;
use Modules\Inspeccion\Models\PruebaTemplate;
use Modules\Inspeccion\Models\Tablero;

/**
 * Hallazgo de /revisor: el down() original de la migración de rename
 * forzaba visita_inspeccion_id de vuelta a NOT NULL — pero ese es
 * justo el estado normal de una Prueba creada desde su Tablero (el
 * flujo que este mismo cambio habilita). En cuanto existía una sola
 * fila real así, migrate:rollback tronaba con un QueryException
 * (constraint NOT NULL). Reproducido antes de arreglar.
 */
it('migrate:rollback no truena aunque exista una Prueba real con visita_inspeccion_id null', function () {
    $tablero = Tablero::factory()->for(Proyecto::factory())->create();
    $template = PruebaTemplate::factory()->create();
    Prueba::crearDesdeTemplate(['tablero_id' => $tablero->id], $template);

    Artisan::call('migrate:rollback', ['--step' => 1]);
    $rollbackOutput = Artisan::output();

    expect($rollbackOutput)->not->toContain('Exception')
        ->toContain('DONE');

    Artisan::call('migrate');
    $migrateOutput = Artisan::output();

    expect($migrateOutput)->not->toContain('Exception')
        ->toContain('DONE');
});
