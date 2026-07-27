<?php

namespace Modules\Inspeccion\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\Inspeccion\Models\Tablero;

class AvancePorTableroWidget extends ChartWidget
{
    protected ?string $heading = 'Avance por tablero';

    protected function getData(): array
    {
        $tableros = Tablero::query()->orderBy('tag')->get();

        return [
            'datasets' => [
                [
                    'label' => 'Avance (%)',
                    'data' => $tableros->pluck('avance_global')->map(fn ($valor) => (float) ($valor ?? 0))->all(),
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => $tableros->pluck('tag')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
