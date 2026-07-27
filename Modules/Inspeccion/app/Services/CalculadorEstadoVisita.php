<?php

namespace Modules\Inspeccion\Services;

use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\VisitaInspeccion;

class CalculadorEstadoVisita
{
    public const SIN_OBSERVACIONES = 'sin_observaciones';

    public const TODO_CERRADO = 'todo_cerrado';

    public const CON_PENDIENTES = 'con_pendientes';

    public const PENDIENTES_CRITICOS = 'pendientes_criticos';

    public function calcular(VisitaInspeccion $visita): string
    {
        $observaciones = $visita->observaciones()
            ->with(['estadoObservacion', 'severidad'])
            ->get();

        if ($observaciones->isEmpty()) {
            return self::SIN_OBSERVACIONES;
        }

        $pendientes = $observaciones->reject(fn (Observacion $o) => $o->estadoObservacion->es_terminal);

        if ($pendientes->isEmpty()) {
            return self::TODO_CERRADO;
        }

        $hayCriticaPendiente = $pendientes->contains(fn (Observacion $o) => $o->severidad?->codigo === 'critica');

        return $hayCriticaPendiente ? self::PENDIENTES_CRITICOS : self::CON_PENDIENTES;
    }
}
