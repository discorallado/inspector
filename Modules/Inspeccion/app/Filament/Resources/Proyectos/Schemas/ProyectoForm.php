<?php

namespace Modules\Inspeccion\Filament\Resources\Proyectos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProyectoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')
                ->label(__('inspeccion.proyecto.campos.nombre'))
                ->required(),
        ]);
    }
}
