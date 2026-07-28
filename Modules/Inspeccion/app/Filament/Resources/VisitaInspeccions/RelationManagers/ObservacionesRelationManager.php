<?php

namespace Modules\Inspeccion\Filament\Resources\VisitaInspeccions\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Support\AccionesBorradoLogico;
use Modules\Inspeccion\Filament\Support\ObservacionActions;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\TipoObservacion;

class ObservacionesRelationManager extends RelationManager
{
    protected static string $relationship = 'observaciones';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tablero_id')
                ->label(__('inspeccion.observacion.campos.tablero'))
                ->relationship('tablero', 'tag')
                ->searchable()
                ->preload(),
            Select::make('tablero_hito_id')
                ->label(__('inspeccion.observacion.campos.tablero_hito'))
                ->relationship('tableroHito', 'nombre')
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->columns([
                TextColumn::make('tablero.tag')
                    ->label(__('inspeccion.observacion.campos.tablero'))
                    ->placeholder('—'),
                TextColumn::make('especialidad.nombre')
                    ->label(__('inspeccion.observacion.campos.especialidad')),
                TextColumn::make('tipoObservacion.nombre')
                    ->label(__('inspeccion.observacion.campos.tipo_observacion'))
                    ->badge(),
                TextColumn::make('severidad.nombre')
                    ->label(__('inspeccion.observacion.campos.severidad'))
                    ->placeholder('—'),
                TextColumn::make('descripcion')
                    ->label(__('inspeccion.observacion.campos.descripcion'))
                    ->limit(60),
                TextColumn::make('estadoObservacion.nombre')
                    ->label(__('inspeccion.observacion.campos.estado_observacion'))
                    ->badge(),
                TextColumn::make('fecha_compromiso')
                    ->label(__('inspeccion.observacion.campos.fecha_compromiso'))
                    ->date()
                    ->placeholder('—'),
            ])
            ->filters(AccionesBorradoLogico::filtros())
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['estado_observacion_id'] = EstadoObservacion::query()->where('codigo', 'pendiente')->value('id');

                        return $data;
                    }),
            ])
            ->recordActions([
                ...ObservacionActions::todas(),
                AccionesBorradoLogico::editar(),
                ...AccionesBorradoLogico::registro(),
            ])
            ->toolbarActions([
                AccionesBorradoLogico::acciones(),
            ]);
    }
}
