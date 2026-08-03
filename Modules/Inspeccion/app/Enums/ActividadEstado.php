<?php

namespace Modules\Inspeccion\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Portado de axon (app/Enums/ActivityStatus.php). Nunca se persiste — se
 * calcula desde el status de las Tareas de la Actividad, ver
 * Actividad::estadoCalculado().
 */
enum ActividadEstado: string implements HasColor, HasIcon, HasLabel
{
    case Pendiente = 'pendiente';
    case EnProgreso = 'en_progreso';
    case Completada = 'completada';

    public function getLabel(): string
    {
        return __("inspeccion.actividad.estado.{$this->value}");
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::EnProgreso => 'info',
            self::Completada => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pendiente => 'heroicon-o-clock',
            self::EnProgreso => 'heroicon-o-play',
            self::Completada => 'heroicon-o-check-circle',
        };
    }
}
