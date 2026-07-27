<?php

namespace Modules\Inspeccion\Filament\Resources\EstadoObservacions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EstadoObservacionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('orden')
            ->columns([
                TextColumn::make('nombre')
                    ->label(__('inspeccion.catalogos.campos.nombre'))
                    ->searchable(),
                TextColumn::make('codigo')
                    ->label(__('inspeccion.catalogos.campos.codigo'))
                    ->searchable(),
                IconColumn::make('es_terminal')
                    ->label(__('inspeccion.catalogos.campos.es_terminal'))
                    ->boolean(),
                TextColumn::make('orden')
                    ->label(__('inspeccion.catalogos.campos.orden'))
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
