<?php

namespace Modules\Inspeccion\Filament\Resources\ResultadoChecklists\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ResultadoChecklistForm
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
        ]);
    }
}
