<?php

namespace Modules\Inspeccion\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\Inspeccion\Models\EstadoObservacion;

class ObservacionesPorEstadoWidget extends ChartWidget
{
    protected ?string $heading = 'Observaciones por estado';

    protected function getData(): array
    {
        $estados = EstadoObservacion::query()->withCount('observaciones')->orderBy('orden')->get();

        return [
            'datasets' => [
                [
                    'label' => 'Observaciones',
                    'data' => $estados->pluck('observaciones_count')->all(),
                    'backgroundColor' => ['#f59e0b', '#22c55e', '#94a3b8'],
                ],
            ],
            'labels' => $estados->pluck('nombre')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
