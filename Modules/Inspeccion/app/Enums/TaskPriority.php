<?php

namespace Modules\Inspeccion\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Portado de axon (app/Enums/TaskPriority.php) — mismos 4 valores. Implementa
 * HasLabel, ver TaskStatus.
 */
enum TaskPriority: string implements HasLabel
{
    case Baja = 'baja';
    case Media = 'media';
    case Alta = 'alta';
    case Critica = 'critica';

    public function getLabel(): string
    {
        return __("inspeccion.tarea.priority.{$this->value}");
    }
}
