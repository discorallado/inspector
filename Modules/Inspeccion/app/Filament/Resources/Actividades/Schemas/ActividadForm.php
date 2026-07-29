<?php

namespace Modules\Inspeccion\Filament\Resources\Actividades\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ActividadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')
                ->label(__('inspeccion.actividad.campos.nombre'))
                ->required()
                ->columnSpanFull(),
            TextInput::make('descripcion')
                ->label(__('inspeccion.actividad.campos.descripcion'))
                ->columnSpanFull(),
            TextInput::make('orden')
                ->label(__('inspeccion.actividad.campos.orden'))
                ->numeric(),
            DatePicker::make('start_date')
                ->label(__('inspeccion.actividad.campos.start_date')),
            DatePicker::make('end_date')
                ->label(__('inspeccion.actividad.campos.end_date')),
        ]);
    }
}
