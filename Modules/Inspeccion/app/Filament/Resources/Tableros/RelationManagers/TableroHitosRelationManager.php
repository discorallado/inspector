<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Inspeccion\Models\EstadoAvance;
use Modules\Inspeccion\Models\TableroHito;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

class TableroHitosRelationManager extends RelationManager
{
    protected static string $relationship = 'tableroHitos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('grupo_hito_id')
                ->label(__('inspeccion.tablero_hito.campos.grupo_hito'))
                ->relationship('grupoHito', 'nombre')
                ->required(),
            Select::make('estado_avance_id')
                ->label(__('inspeccion.tablero_hito.campos.estado_avance'))
                ->options(fn (?TableroHito $record) => self::opcionesEstadoAvance($record))
                ->required(),
            TextInput::make('item')
                ->label(__('inspeccion.tablero_hito.campos.item'))
                ->required(),
            TextInput::make('nombre')
                ->label(__('inspeccion.tablero_hito.campos.nombre'))
                ->required(),
            TextInput::make('peso')
                ->label(__('inspeccion.tablero_hito.campos.peso'))
                ->required()
                ->numeric()
                ->minValue(0.01),
            DatePicker::make('plan_inicio')
                ->label(__('inspeccion.tablero_hito.campos.plan_inicio')),
            DatePicker::make('plan_fin')
                ->label(__('inspeccion.tablero_hito.campos.plan_fin')),
            DatePicker::make('real_inicio')
                ->label(__('inspeccion.tablero_hito.campos.real_inicio')),
            DatePicker::make('real_fin')
                ->label(__('inspeccion.tablero_hito.campos.real_fin')),
            TextInput::make('responsable')
                ->label(__('inspeccion.tablero_hito.campos.responsable')),
            Textarea::make('observaciones')
                ->label(__('inspeccion.tablero_hito.campos.observaciones'))
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item')
            ->defaultSort('item')
            ->columns([
                TextColumn::make('item')
                    ->label(__('inspeccion.tablero_hito.campos.item')),
                TextColumn::make('grupoHito.nombre')
                    ->label(__('inspeccion.tablero_hito.campos.grupo_hito')),
                TextColumn::make('nombre')
                    ->label(__('inspeccion.tablero_hito.campos.nombre'))
                    ->searchable(),
                TextColumn::make('estadoAvance.nombre')
                    ->label(__('inspeccion.tablero_hito.campos.estado_avance'))
                    ->badge(),
                TextColumn::make('peso')
                    ->label(__('inspeccion.tablero_hito.campos.peso'))
                    ->numeric(),
                TextColumn::make('plan_fin')
                    ->label(__('inspeccion.tablero_hito.campos.plan_fin'))
                    ->date(),
                TextColumn::make('responsable')
                    ->label(__('inspeccion.tablero_hito.campos.responsable')),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Solo ofrece como opciones los estados alcanzables desde el estado
     * actual del hito (o los de arranque si es un hito nuevo), según la
     * tabla transiciones_estado_permitidas.
     */
    private static function opcionesEstadoAvance(?TableroHito $record): array
    {
        $guard = app(TransicionEstadoGuard::class);

        $idsPermitidos = $guard
            ->transicionesValidasDesde(TransicionEstadoPermitida::TIPO_ESTADO_AVANCE, $record?->estado_avance_id)
            ->push($record?->estado_avance_id)
            ->filter()
            ->unique();

        return EstadoAvance::query()->whereIn('id', $idsPermitidos)->orderBy('orden')->pluck('nombre', 'id')->all();
    }
}
