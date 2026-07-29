<?php

namespace Modules\Inspeccion\Filament\Resources\Actividades\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Support\AccionesBorradoLogico;

class ActividadesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('orden')
            ->columns([
                TextColumn::make('tablero.tag')
                    ->label(__('inspeccion.tablero.campos.tag')),
                TextColumn::make('nombre')
                    ->label(__('inspeccion.actividad.campos.nombre'))
                    ->searchable(),
                TextColumn::make('orden')
                    ->label(__('inspeccion.actividad.campos.orden')),
                TextColumn::make('tareas_count')
                    ->label(__('inspeccion.actividad.campos.cantidad_tareas'))
                    ->counts('tareas'),
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
