<?php

namespace Modules\Inspeccion\Observers;

use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Models\Tarea;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\CalculadorAvanceTablero;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

class TareaObserver
{
    public function __construct(
        private readonly TransicionEstadoGuard $guard,
        private readonly CalculadorAvanceTablero $calculador,
    ) {}

    public function saving(Tarea $tarea): void
    {
        if (! $tarea->isDirty('status')) {
            return;
        }

        // getOriginal() sí aplica el cast a enum (transformModelValue), no
        // devuelve el string crudo — por eso el ->value acá también.
        /** @var TaskStatus|null $original */
        $original = $tarea->exists ? $tarea->getOriginal('status') : null;

        $this->guard->validarPorCodigo(
            TransicionEstadoPermitida::TIPO_TAREA_STATUS,
            $original?->value,
            $tarea->status->value,
        );
    }

    public function saved(Tarea $tarea): void
    {
        $this->calculador->recalcularYGuardar($tarea->actividad->tablero);
    }

    public function deleted(Tarea $tarea): void
    {
        $this->calculador->recalcularYGuardar($tarea->actividad->tablero);
    }
}
