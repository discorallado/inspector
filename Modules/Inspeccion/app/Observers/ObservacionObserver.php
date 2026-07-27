<?php

namespace Modules\Inspeccion\Observers;

use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

class ObservacionObserver
{
    public function __construct(private readonly TransicionEstadoGuard $guard) {}

    public function saving(Observacion $observacion): void
    {
        if (! $observacion->isDirty('estado_observacion_id')) {
            return;
        }

        $this->guard->validar(
            TransicionEstadoPermitida::TIPO_ESTADO_OBSERVACION,
            $observacion->exists ? $observacion->getOriginal('estado_observacion_id') : null,
            $observacion->estado_observacion_id,
        );
    }
}
