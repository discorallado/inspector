<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TablerosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tag')
                    ->label(__('inspeccion.tablero.campos.tag'))
                    ->searchable(),
                TextColumn::make('proyecto.nombre')
                    ->label(__('inspeccion.tablero.campos.proyecto'))
                    ->searchable(),
                TextColumn::make('nombre')
                    ->label(__('inspeccion.tablero.campos.nombre'))
                    ->searchable(),
                TextColumn::make('fabricante')
                    ->label(__('inspeccion.tablero.campos.fabricante'))
                    ->searchable(),
                TextColumn::make('avance_global')
                    ->label(__('inspeccion.tablero.campos.avance_global'))
                    ->suffix('%')
                    ->placeholder('—')
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
