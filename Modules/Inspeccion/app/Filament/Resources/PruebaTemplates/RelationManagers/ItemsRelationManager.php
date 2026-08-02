<?php

namespace Modules\Inspeccion\Filament\Resources\PruebaTemplates\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item')
            // Calificado porque tanto la tabla pivote (prueba_template_items)
            // como la relacionada (prueba_item_libraries) tienen columna
            // `orden` — sin calificar, el ORDER BY queda ambiguo para MariaDB
            // en cuanto la relación hace join (PruebaTemplate::items() ya
            // ordena por el pivote). Filament recorta el prefijo solo al
            // persistir el reorder contra el pivote, así que calificar acá
            // no rompe el guardado.
            ->reorderable('prueba_template_items.orden')
            ->defaultSort('prueba_template_items.orden')
            ->columns([
                TextColumn::make('categoria')
                    ->label(__('inspeccion.prueba.campos.categoria')),
                TextColumn::make('item')
                    ->label(__('inspeccion.prueba.campos.item'))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('referencia_normativa')
                    ->label(__('inspeccion.prueba.campos.referencia_normativa')),
            ])
            ->filters([])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectSearchColumns(['item', 'categoria'])
                    ->schema(fn (AttachAction $action) => [
                        $action->getRecordSelect(),
                        TextInput::make('orden')
                            ->label(__('inspeccion.catalogos.campos.orden'))
                            ->numeric()
                            ->default(0),
                    ]),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
