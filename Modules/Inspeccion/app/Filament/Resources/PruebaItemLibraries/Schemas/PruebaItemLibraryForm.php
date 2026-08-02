<?php

namespace Modules\Inspeccion\Filament\Resources\PruebaItemLibraries\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PruebaItemLibraryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('categoria')
                ->label(__('inspeccion.prueba.campos.categoria'))
                ->required(),
            Textarea::make('item')
                ->label(__('inspeccion.prueba.campos.item'))
                ->required()
                ->columnSpanFull(),
            TextInput::make('referencia_normativa')
                ->label(__('inspeccion.prueba.campos.referencia_normativa')),
            TextInput::make('orden')
                ->label(__('inspeccion.catalogos.campos.orden'))
                ->required()
                ->numeric()
                ->default(0),
            Toggle::make('activo')
                ->label(__('inspeccion.catalogos.campos.activo'))
                ->default(true)
                ->required(),
        ]);
    }
}
