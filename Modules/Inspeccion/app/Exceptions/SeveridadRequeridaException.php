<?php

namespace Modules\Inspeccion\Exceptions;

use RuntimeException;

class SeveridadRequeridaException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('inspeccion.errores.severidad_requerida'));
    }
}
