<?php

namespace Modules\Inspeccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inspeccion\Models\EstadoAvance;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;

class TransicionEstadoPermitidaSeeder extends Seeder
{
    public function run(): void
    {
        $avance = EstadoAvance::query()->pluck('id', 'codigo');
        $this->crear(TransicionEstadoPermitida::TIPO_ESTADO_AVANCE, [
            [null, $avance['pendiente']],
            [$avance['pendiente'], $avance['en_proceso']],
            [$avance['en_proceso'], $avance['completado']],
            [$avance['pendiente'], $avance['na']],
            [$avance['en_proceso'], $avance['na']],
        ]);

        $observacion = EstadoObservacion::query()->pluck('id', 'codigo');
        $this->crear(TransicionEstadoPermitida::TIPO_ESTADO_OBSERVACION, [
            [null, $observacion['pendiente']],
            [$observacion['pendiente'], $observacion['subsanada_ok']],
            [$observacion['pendiente'], $observacion['informativa']],
        ]);

        $cambio = EstadoCambio::query()->pluck('id', 'codigo');
        $this->crear(TransicionEstadoPermitida::TIPO_ESTADO_CAMBIO, [
            [null, $cambio['propuesto']],
            [$cambio['propuesto'], $cambio['aprobado']],
            [$cambio['propuesto'], $cambio['rechazado']],
            [$cambio['aprobado'], $cambio['implementado']],
            [$cambio['aprobado'], $cambio['rechazado']],
        ]);
    }

    /**
     * @param  list<array{0: int|null, 1: int}>  $pares
     */
    private function crear(string $tipoCatalogo, array $pares): void
    {
        foreach ($pares as [$origenId, $destinoId]) {
            TransicionEstadoPermitida::query()->firstOrCreate([
                'tipo_catalogo' => $tipoCatalogo,
                'estado_origen_id' => $origenId,
                'estado_destino_id' => $destinoId,
            ]);
        }
    }
}
