<?php

namespace Modules\Inspeccion\Filament\Resources\GrupoHitos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GrupoHitoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')
                ->label(__('inspeccion.catalogos.campos.nombre'))
                ->required(),
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
