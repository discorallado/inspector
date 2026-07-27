<?php

namespace Modules\Inspeccion\Filament\Resources\ResultadoChecklists\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResultadoChecklistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('orden')
            ->columns([
                TextColumn::make('nombre')
                    ->label(__('inspeccion.catalogos.campos.nombre'))
                    ->searchable(),
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
