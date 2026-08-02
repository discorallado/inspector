<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChecklistTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label(__('inspeccion.catalogos.campos.nombre'))
                    ->searchable(),
                TextColumn::make('items_count')
                    ->label(__('inspeccion.checklist.item_library.plural'))
                    ->counts('items'),
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
