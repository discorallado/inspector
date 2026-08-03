<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ActividadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('inspeccion.actividad.secciones.datos'))
                ->description(__('inspeccion.actividad.secciones.datos_ayuda'))
                ->icon(Heroicon::OutlinedRectangleGroup)
                ->schema([
                    TextInput::make('nombre')
                        ->label(__('inspeccion.actividad.campos.nombre'))
                        ->required(),
                    TextInput::make('descripcion')
                        ->label(__('inspeccion.actividad.campos.descripcion')),
                ]),
            Section::make(__('inspeccion.actividad.secciones.ponderacion'))
                ->description(__('inspeccion.actividad.secciones.ponderacion_ayuda'))
                ->icon(Heroicon::OutlinedScale)
                ->columns(2)
                ->schema([
                    TextInput::make('orden')
                        ->label(__('inspeccion.actividad.campos.orden'))
                        ->helperText(__('inspeccion.actividad.campos.orden_ayuda'))
                        ->numeric(),
                    TextInput::make('peso')
                        ->label(__('inspeccion.actividad.campos.peso'))
                        ->helperText(__('inspeccion.actividad.campos.peso_ayuda'))
                        ->numeric()
                        ->minValue(0),
                ]),
            Section::make(__('inspeccion.actividad.secciones.fechas'))
                ->description(__('inspeccion.actividad.secciones.fechas_ayuda'))
                ->icon(Heroicon::OutlinedCalendarDays)
                ->columns(2)
                ->schema([
                    DatePicker::make('start_date')
                        ->label(__('inspeccion.actividad.campos.start_date')),
                    DatePicker::make('end_date')
                        ->label(__('inspeccion.actividad.campos.end_date')),
                ]),
        ]);
    }
}
