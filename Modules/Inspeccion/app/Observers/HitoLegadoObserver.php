<?php

namespace Modules\Inspeccion\Observers;

use Modules\Inspeccion\Models\HitoLegado;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

/**
 * Desde PR6 (ADR 0013), avance_global se calcula exclusivamente sobre Tarea
 * — este observer ya no dispara ningún recálculo, solo valida la máquina
 * de estados de HitoLegado (referencia histórica congelada, ver ADR 0012).
 */
class HitoLegadoObserver
{
    public function __construct(private readonly TransicionEstadoGuard $guard) {}

    public function saving(HitoLegado $hito): void
    {
        if (! $hito->isDirty('estado_avance_id')) {
            return;
        }

        $this->guard->validar(
            TransicionEstadoPermitida::TIPO_ESTADO_AVANCE,
            $hito->exists ? $hito->getOriginal('estado_avance_id') : null,
            $hito->estado_avance_id,
        );
    }
}
