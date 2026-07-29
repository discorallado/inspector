<?php

namespace Modules\Inspeccion\Enums;

/**
 * Portado de axon (app/Enums/TaskPriority.php) — mismos 4 valores, sin
 * los contratos de UI de Filament (ver TaskStatus).
 */
enum TaskPriority: string
{
    case Baja = 'baja';
    case Media = 'media';
    case Alta = 'alta';
    case Critica = 'critica';
}
