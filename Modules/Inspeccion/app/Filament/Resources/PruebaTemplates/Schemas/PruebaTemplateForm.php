<?php

namespace Modules\Inspeccion\Filament\Resources\PruebaTemplates\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PruebaTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')
                ->label(__('inspeccion.catalogos.campos.nombre'))
                ->required(),
        ]);
    }
}
