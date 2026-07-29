<?php

namespace Modules\Inspeccion\Exceptions;

use RuntimeException;

class TransicionEstadoInvalidaException extends RuntimeException
{
    public function __construct(string $tipoCatalogo, int|string|null $origenId, int|string $destinoId)
    {
        parent::__construct(__('inspeccion.errores.transicion_no_permitida', [
            'origen' => $origenId ?? '—',
            'destino' => $destinoId,
        ])." [{$tipoCatalogo}]");
    }
}
