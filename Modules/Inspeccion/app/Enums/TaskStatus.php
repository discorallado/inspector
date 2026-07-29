<?php

namespace Modules\Inspeccion\Enums;

/**
 * Portado de axon (app/Enums/TaskStatus.php) — mismos 5 valores, sin los
 * contratos HasColor/HasIcon/HasLabel de Filament (eso es UI, se agrega
 * cuando el Kanban/Gantt lleguen en PR7/PR8).
 */
enum TaskStatus: string
{
    case Pendiente = 'pendiente';
    case EnProgreso = 'en_progreso';
    case EnRevision = 'en_revision';
    case Completada = 'completada';
    case Bloqueada = 'bloqueada';

    public function isCompleted(): bool
    {
        return $this === self::Completada;
    }
}
