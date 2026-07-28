<?php

namespace Modules\Inspeccion\Filament\Resources\ControlCambios\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Support\AccionesBorradoLogico;
use Modules\Inspeccion\Filament\Support\ControlCambioActions;
use Modules\Inspeccion\Models\EstadoCambio;

class ControlCambiosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('tablero.tag')
                    ->label(__('inspeccion.control_cambio.campos.tablero')),
                TextColumn::make('descripcion')
                    ->label(__('inspeccion.control_cambio.campos.descripcion'))
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('estadoCambio.nombre')
                    ->label(__('inspeccion.control_cambio.campos.estado_cambio'))
                    ->badge(),
                TextColumn::make('responsable')
                    ->label(__('inspeccion.control_cambio.campos.responsable')),
                TextColumn::make('fecha')
                    ->label(__('inspeccion.control_cambio.campos.fecha'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('estado_cambio_id')
                    ->label(__('inspeccion.control_cambio.campos.estado_cambio'))
                    ->options(EstadoCambio::query()->pluck('nombre', 'id')),
                ...AccionesBorradoLogico::filtros(),
            ])
            ->recordActions([
                ...ControlCambioActions::todas(),
                AccionesBorradoLogico::editar(),
                ...AccionesBorradoLogico::registro(),
            ])
            ->toolbarActions([
                AccionesBorradoLogico::acciones(),
            ]);
    }
}
