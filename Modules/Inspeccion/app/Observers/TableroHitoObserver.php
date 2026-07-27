<?php

namespace Modules\Inspeccion\Observers;

use Modules\Inspeccion\Models\TableroHito;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\CalculadorAvanceTablero;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

class TableroHitoObserver
{
    public function __construct(
        private readonly TransicionEstadoGuard $guard,
        private readonly CalculadorAvanceTablero $calculador,
    ) {}

    public function saving(TableroHito $hito): void
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

    public function saved(TableroHito $hito): void
    {
        $this->calculador->recalcularYGuardar($hito->tablero);
    }

    public function deleted(TableroHito $hito): void
    {
        $this->calculador->recalcularYGuardar($hito->tablero);
    }
}
