<?php

namespace Modules\Inspeccion\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Modules\Inspeccion\Models\ControlCambio;

class CambiosPendientesWidget extends TableWidget
{
    protected function getTableHeading(): string
    {
        return __('inspeccion.control_cambio.plural').' pendientes';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ControlCambio::query()->whereHas(
                    'estadoCambio',
                    fn ($query) => $query->whereIn('codigo', ['propuesto', 'aprobado'])
                )
            )
            ->columns([
                TextColumn::make('tablero.tag')
                    ->label(__('inspeccion.control_cambio.campos.tablero')),
                TextColumn::make('descripcion')
                    ->label(__('inspeccion.control_cambio.campos.descripcion'))
                    ->limit(60),
                TextColumn::make('estadoCambio.nombre')
                    ->label(__('inspeccion.control_cambio.campos.estado_cambio'))
                    ->badge(),
                TextColumn::make('fecha')
                    ->label(__('inspeccion.control_cambio.campos.fecha'))
                    ->date(),
            ]);
    }
}
