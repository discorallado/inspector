<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistTemplates\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ChecklistTemplateForm
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
