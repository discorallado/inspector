<?php

namespace Modules\Inspeccion\Filament\Resources\Observacions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Modules\Inspeccion\Models\TipoObservacion;

class ObservacionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('visita_inspeccion_id')
                ->label(__('inspeccion.observacion.campos.visita_inspeccion'))
                ->relationship('visitaInspeccion', 'fecha')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('tablero_id')
                ->label(__('inspeccion.observacion.campos.tablero'))
                ->relationship('tablero', 'tag')
                ->searchable()
                ->preload(),
            Select::make('tablero_hito_id')
                ->label(__('inspeccion.observacion.campos.hito_legado'))
                ->relationship('hitoLegado', 'nombre')
                ->searchable()
                ->preload(),
            Select::make('especialidad_id')
                ->label(__('inspeccion.observacion.campos.especialidad'))
                ->relationship('especialidad', 'nombre')
                ->required(),
            Select::make('tipo_observacion_id')
                ->label(__('inspeccion.observacion.campos.tipo_observacion'))
                ->relationship('tipoObservacion', 'nombre')
                ->live()
                ->required(),
            Select::make('severidad_id')
                ->label(__('inspeccion.observacion.campos.severidad'))
                ->relationship('severidad', 'nombre')
                ->visible(fn (Get $get) => TipoObservacion::find($get('tipo_observacion_id'))?->requiere_severidad)
                ->required(fn (Get $get) => TipoObservacion::find($get('tipo_observacion_id'))?->requiere_severidad),
            Textarea::make('descripcion')
                ->label(__('inspeccion.observacion.campos.descripcion'))
                ->required()
                ->columnSpanFull(),
            TextInput::make('responsable')
                ->label(__('inspeccion.observacion.campos.responsable')),
            DatePicker::make('fecha_compromiso')
                ->label(__('inspeccion.observacion.campos.fecha_compromiso')),
        ]);
    }
}
