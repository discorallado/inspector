<?php

namespace Modules\Inspeccion\Filament\Resources\Severidads\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SeveridadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')
                ->label(__('inspeccion.catalogos.campos.nombre'))
                ->required(),
            TextInput::make('codigo')
                ->label(__('inspeccion.catalogos.campos.codigo'))
                ->helperText('Identificador estable (ej. "critica") usado por reglas de negocio como el estado general de una visita.')
                ->required(),
            TextInput::make('orden')
                ->label(__('inspeccion.catalogos.campos.orden'))
                ->required()
                ->numeric()
                ->default(0),
        ]);
    }
}
