<?php

use Modules\Inspeccion\Database\Seeders\EstadoAvanceSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoCambioSeeder;
use Modules\Inspeccion\Database\Seeders\EstadoObservacionSeeder;
use Modules\Inspeccion\Database\Seeders\TransicionEstadoPermitidaSeeder;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

/**
 * PR3 del ADR 0008 (fix de N+1): transicionesValidasDesde() cachea por
 * request usando una key (tipo_catalogo, origen_id). Los callers actuales
 * (ObservacionsTable/ControlCambiosTable::opcionesEstadoDestino) hacen
 * ->push() sobre el resultado para agregar el estado actual del registro
 * a las opciones del <select> — sin clonar antes de devolver, ese push()
 * mutaba el objeto cacheado y lo iba inflando fila a fila dentro del
 * mismo render de la tabla.
 */
it('mutar el resultado de una llamada no afecta a llamadas posteriores con la misma key', function () {
    $this->seed(EstadoAvanceSeeder::class);
    $this->seed(EstadoObservacionSeeder::class);
    $this->seed(EstadoCambioSeeder::class);
    $this->seed(TransicionEstadoPermitidaSeeder::class);

    $propuestoId = EstadoCambio::query()->where('codigo', 'propuesto')->value('id');
    $guard = app(TransicionEstadoGuard::class);

    $r1 = $guard->transicionesValidasDesde(TransicionEstadoPermitida::TIPO_ESTADO_CAMBIO, $propuestoId);
    $countAntes = $r1->count();
    $r1->push(99999);

    $r2 = $guard->transicionesValidasDesde(TransicionEstadoPermitida::TIPO_ESTADO_CAMBIO, $propuestoId);

    expect($r2->count())->toBe($countAntes);
    expect($r2->contains(99999))->toBeFalse();
});
