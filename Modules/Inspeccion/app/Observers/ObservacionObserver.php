<?php

namespace Modules\Inspeccion\Observers;

use Modules\Inspeccion\Exceptions\SeveridadRequeridaException;
use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

class ObservacionObserver
{
    public function __construct(private readonly TransicionEstadoGuard $guard) {}

    public function saving(Observacion $observacion): void
    {
        if ($observacion->isDirty('estado_observacion_id')) {
            $this->guard->validar(
                TransicionEstadoPermitida::TIPO_ESTADO_OBSERVACION,
                $observacion->exists ? $observacion->getOriginal('estado_observacion_id') : null,
                $observacion->estado_observacion_id,
            );
        }

        // Regla de negocio del requerimiento (§3.4): la severidad solo es
        // obligatoria para observaciones "a subsanar". El form de Filament
        // ya lo exige de forma reactiva, pero esto se valida también acá
        // para que ninguna otra vía de escritura (seeder, import, tinker)
        // pueda dejar el dato inconsistente.
        if ($observacion->tipoObservacion?->requiere_severidad && $observacion->severidad_id === null) {
            throw new SeveridadRequeridaException;
        }
    }
}
