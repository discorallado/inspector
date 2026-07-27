<?php

namespace Modules\Inspeccion\Filament\Resources\EstadoObservacions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EstadoObservacionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')
                ->label(__('inspeccion.catalogos.campos.nombre'))
                ->required(),
            TextInput::make('codigo')
                ->label(__('inspeccion.catalogos.campos.codigo'))
                ->helperText('Identificador estable usado por las transiciones de estado permitidas.')
                ->required(),
            Toggle::make('es_terminal')
                ->label(__('inspeccion.catalogos.campos.es_terminal'))
                ->default(false)
                ->required(),
            TextInput::make('orden')
                ->label(__('inspeccion.catalogos.campos.orden'))
                ->required()
                ->numeric()
                ->default(0),
        ]);
    }
}
