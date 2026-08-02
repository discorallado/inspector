<?php

namespace Modules\Inspeccion\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Portado de axon (app/Enums/TaskPriority.php) — mismos 4 valores. Implementa
 * HasLabel, ver TaskStatus. HasColor/HasIcon (PR7): mismo criterio.
 */
enum TaskPriority: string implements HasColor, HasIcon, HasLabel
{
    case Baja = 'baja';
    case Media = 'media';
    case Alta = 'alta';
    case Critica = 'critica';

    public function getLabel(): string
    {
        return __("inspeccion.tarea.priority.{$this->value}");
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Baja => 'success',
            self::Media => 'info',
            self::Alta => 'warning',
            self::Critica => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Baja => 'heroicon-o-arrow-down',
            self::Media => 'heroicon-o-minus',
            self::Alta => 'heroicon-o-arrow-up',
            self::Critica => 'heroicon-o-fire',
        };
    }
}
