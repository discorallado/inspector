<?php

namespace Modules\Inspeccion\Filament\Resources\ControlCambios\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ControlCambioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tablero_id')
                ->label(__('inspeccion.control_cambio.campos.tablero'))
                ->relationship('tablero', 'tag')
                ->searchable()
                ->preload()
                ->required(),
            Textarea::make('descripcion')
                ->label(__('inspeccion.control_cambio.campos.descripcion'))
                ->required()
                ->columnSpanFull(),
            TextInput::make('responsable')
                ->label(__('inspeccion.control_cambio.campos.responsable')),
            DatePicker::make('fecha')
                ->label(__('inspeccion.control_cambio.campos.fecha'))
                ->default(now())
                ->required(),
        ]);
    }
}
