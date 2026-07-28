<?php

namespace Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Support\AccionesBorradoLogico;
use Modules\Inspeccion\Models\VisitaInspeccion;

class VisitaInspeccionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('fecha')
                    ->label(__('inspeccion.visita_inspeccion.campos.fecha'))
                    ->date()
                    ->sortable(),
                TextColumn::make('proyecto.nombre')
                    ->label(__('inspeccion.visita_inspeccion.campos.proyecto'))
                    ->searchable(),
                TextColumn::make('inspector.name')
                    ->label(__('inspeccion.visita_inspeccion.campos.inspector'))
                    ->searchable(),
                TextColumn::make('tableros.tag')
                    ->label(__('inspeccion.visita_inspeccion.campos.tableros'))
                    ->badge(),
                TextColumn::make('estado_general')
                    ->label(__('inspeccion.visita_inspeccion.campos.estado_general'))
                    ->state(fn (VisitaInspeccion $record) => __('inspeccion.visita_inspeccion.estado_general.'.$record->estadoGeneral()))
                    ->badge(),
            ])
            ->filters(AccionesBorradoLogico::filtros())
            ->recordActions([
                AccionesBorradoLogico::editar(),
                ...AccionesBorradoLogico::registro(),
            ])
            ->toolbarActions([
                AccionesBorradoLogico::acciones(),
            ]);
    }
}
