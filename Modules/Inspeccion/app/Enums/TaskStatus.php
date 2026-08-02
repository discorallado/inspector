<?php

namespace Modules\Inspeccion\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Portado de axon (app/Enums/TaskStatus.php) — mismos 5 valores. Implementa
 * HasLabel para que los Select/badges de Filament (ActividadesRelationManager,
 * PR6) muestren la etiqueta traducida en vez del value crudo. HasColor/HasIcon
 * (PR7): colores semánticos de Filament (gray/info/warning/success/danger),
 * ya registrados por el panel — no hace falta un tema de colores paralelo
 * para las columnas del kanban.
 */
enum TaskStatus: string implements HasColor, HasIcon, HasLabel
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

    public function getLabel(): string
    {
        return __("inspeccion.tarea.status.{$this->value}");
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pendiente => 'gray',
            self::EnProgreso => 'info',
            self::EnRevision => 'warning',
            self::Completada => 'success',
            self::Bloqueada => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pendiente => 'heroicon-o-clock',
            self::EnProgreso => 'heroicon-o-play',
            self::EnRevision => 'heroicon-o-eye',
            self::Completada => 'heroicon-o-check-circle',
            self::Bloqueada => 'heroicon-o-x-circle',
        };
    }

    /**
     * Equivalente al campo `valor` de EstadoAvance (catálogo), para que
     * CalculadorAvanceTablero pondere sobre Tarea con la misma fórmula.
     * EnRevision no tiene equivalente histórico (ningún HitoLegado
     * migrado usó ese estado) — 0.9 es una estimación razonable ("el
     * trabajo está prácticamente hecho, pendiente de confirmar"), ajustable
     * sin impacto en datos ya migrados si no encaja en la práctica.
     */
    public function valor(): float
    {
        return match ($this) {
            self::Pendiente, self::Bloqueada => 0.0,
            self::EnProgreso => 0.5,
            self::EnRevision => 0.9,
            self::Completada => 1.0,
        };
    }
}
