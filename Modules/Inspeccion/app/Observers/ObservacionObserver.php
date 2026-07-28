<?php

namespace Modules\Inspeccion\Observers;

use Modules\Inspeccion\Exceptions\SeveridadRequeridaException;
use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;
use Relaticle\Flowforge\Services\DecimalPosition;

class ObservacionObserver
{
    public function __construct(private readonly TransicionEstadoGuard $guard) {}

    /**
     * Toda Observacion nueva entra a su columna del kanban en la misma
     * posición base en la que Flowforge dejaría una card recién creada
     * ahí: al final de las que ya existen en ese estado (o la posición
     * inicial si la columna está vacía). Sin esto, una Observacion creada
     * por el form/seeder/tinker (cualquier vía que no sea arrastrar una
     * card) queda con posicion NULL y su orden dentro de la columna es
     * indefinido hasta que alguien la mueva una vez a mano.
     */
    public function creating(Observacion $observacion): void
    {
        if ($observacion->posicion !== null) {
            return;
        }

        $ultimaPosicion = Observacion::query()
            ->where('estado_observacion_id', $observacion->estado_observacion_id)
            ->max('posicion');

        $observacion->posicion = $ultimaPosicion !== null
            ? DecimalPosition::after($ultimaPosicion)
            : DecimalPosition::forEmptyColumn();
    }

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
