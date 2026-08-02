<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ChecklistEjecucionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('visita_inspeccion_id')
                ->label(__('inspeccion.visita_inspeccion.singular'))
                ->relationship('visitaInspeccion', 'fecha')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('tablero_id')
                ->label(__('inspeccion.observacion.campos.tablero'))
                ->relationship('tablero', 'tag')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('checklist_template_id')
                ->label(__('inspeccion.checklist.template.singular'))
                ->relationship('checklistTemplate', 'nombre')
                ->required(),
        ]);
    }
}
