<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistItemLibraries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChecklistItemLibrariesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('orden')
            ->columns([
                TextColumn::make('categoria')
                    ->label(__('inspeccion.checklist.campos.categoria'))
                    ->searchable(),
                TextColumn::make('item')
                    ->label(__('inspeccion.checklist.campos.item'))
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('referencia_normativa')
                    ->label(__('inspeccion.checklist.campos.referencia_normativa'))
                    ->searchable(),
                IconColumn::make('activo')
                    ->label(__('inspeccion.catalogos.campos.activo'))
                    ->boolean(),
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
