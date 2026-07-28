<?php

namespace Modules\Inspeccion\Observers;

use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;
use Relaticle\Flowforge\Services\DecimalPosition;

class ControlCambioObserver
{
    public function __construct(private readonly TransicionEstadoGuard $guard) {}

    /**
     * Todo ControlCambio nuevo entra a su columna del kanban en la misma
     * posición base en la que Flowforge dejaría una card recién creada
     * ahí (ver ObservacionObserver::creating(), mismo criterio).
     */
    public function creating(ControlCambio $controlCambio): void
    {
        if ($controlCambio->posicion !== null) {
            return;
        }

        $ultimaPosicion = ControlCambio::query()
            ->where('estado_cambio_id', $controlCambio->estado_cambio_id)
            ->max('posicion');

        $controlCambio->posicion = $ultimaPosicion !== null
            ? DecimalPosition::after($ultimaPosicion)
            : DecimalPosition::forEmptyColumn();
    }

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
