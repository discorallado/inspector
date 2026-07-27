<?php

namespace Modules\Inspeccion\Observers;

use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

class ControlCambioObserver
{
    public function __construct(private readonly TransicionEstadoGuard $guard) {}

    public function saving(ControlCambio $controlCambio): void
    {
        if (! $controlCambio->isDirty('estado_cambio_id')) {
            return;
        }

        $this->guard->validar(
            TransicionEstadoPermitida::TIPO_ESTADO_CAMBIO,
            $controlCambio->exists ? $controlCambio->getOriginal('estado_cambio_id') : null,
            $controlCambio->estado_cambio_id,
        );
    }
}
