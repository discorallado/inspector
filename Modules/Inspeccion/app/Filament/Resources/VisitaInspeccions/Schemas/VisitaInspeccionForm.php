<?php

namespace Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VisitaInspeccionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('proyecto_id')
                ->label(__('inspeccion.visita_inspeccion.campos.proyecto'))
                ->relationship('proyecto', 'nombre')
                ->searchable()
                ->preload()
                ->required()
                ->createOptionForm([
                    TextInput::make('nombre')
                        ->label(__('inspeccion.proyecto.campos.nombre'))
                        ->required(),
                ]),
            Select::make('inspector_id')
                ->label(__('inspeccion.visita_inspeccion.campos.inspector'))
                ->relationship('inspector', 'name')
                ->searchable()
                ->preload()
                ->required(),
            DatePicker::make('fecha')
                ->label(__('inspeccion.visita_inspeccion.campos.fecha'))
                ->default(now())
                ->required(),
            Select::make('tableros')
                ->label(__('inspeccion.visita_inspeccion.campos.tableros'))
                ->relationship('tableros', 'tag')
                ->multiple()
                ->searchable()
                ->preload(),
            Textarea::make('observaciones_generales')
                ->label(__('inspeccion.visita_inspeccion.campos.observaciones_generales'))
                ->columnSpanFull(),
        ]);
    }
}
