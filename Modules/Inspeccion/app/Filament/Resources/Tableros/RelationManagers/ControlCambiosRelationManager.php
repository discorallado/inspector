<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Support\AccionesBorradoLogico;
use Modules\Inspeccion\Filament\Support\ControlCambioActions;
use Modules\Inspeccion\Models\EstadoCambio;

class ControlCambiosRelationManager extends RelationManager
{
    protected static string $relationship = 'controlCambios';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->columns([
                TextColumn::make('descripcion')
                    ->label(__('inspeccion.control_cambio.campos.descripcion'))
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('estadoCambio.nombre')
                    ->label(__('inspeccion.control_cambio.campos.estado_cambio'))
                    ->badge(),
                TextColumn::make('responsable')
                    ->label(__('inspeccion.control_cambio.campos.responsable')),
                TextColumn::make('fecha')
                    ->label(__('inspeccion.control_cambio.campos.fecha'))
                    ->date(),
            ])
            ->filters(AccionesBorradoLogico::filtros())
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['estado_cambio_id'] = EstadoCambio::query()->where('codigo', 'propuesto')->value('id');

                        return $data;
                    }),
            ])
            ->recordActions([
                ...ControlCambioActions::todas(),
                AccionesBorradoLogico::editar(),
                ...AccionesBorradoLogico::registro(),
            ])
            ->toolbarActions([
                AccionesBorradoLogico::acciones(),
            ]);
    }
}
