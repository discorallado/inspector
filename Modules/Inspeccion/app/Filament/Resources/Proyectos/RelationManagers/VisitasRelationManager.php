<?php

namespace Modules\Inspeccion\Filament\Resources\Proyectos\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\VisitaInspeccionResource;
use Modules\Inspeccion\Filament\Support\AccionesBorradoLogico;
use Modules\Inspeccion\Models\VisitaInspeccion;

/**
 * Punto de entrada de VisitaInspeccion desde ADR de reordenamiento de
 * sidebar: antes tenía ítem propio en el cluster Inspección de Calidad,
 * ahora se llega a ella desde su Proyecto. $relatedResource navega a la
 * página de edición real (con su ObservacionesRelationManager anidada),
 * en vez de un modal — mismo patrón que PruebasRelationManager.
 */
class VisitasRelationManager extends RelationManager
{
    protected static string $relationship = 'visitasInspeccion';

    protected static ?string $relatedResource = VisitaInspeccionResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
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
                // Acotado a los tableros de ESTE proyecto vía
                // modifyQueryUsing (no ->options() suelto): options() sin
                // más solo cambia lo que se MUESTRA en el <select> — un
                // wire:call directo igual podía adjuntar un tablero de
                // otro proyecto, sin validación server-side (encontrado
                // escribiendo el test de este relation manager).
                // modifyQueryUsing acota la query real que Filament usa
                // tanto para poblar opciones como para guardar/validar.
                ->relationship(
                    'tableros',
                    'tag',
                    modifyQueryUsing: fn ($query) => $query->whereBelongsTo($this->getOwnerRecord(), 'proyecto'),
                )
                ->multiple()
                ->searchable()
                ->preload(),
            Textarea::make('observaciones_generales')
                ->label(__('inspeccion.visita_inspeccion.campos.observaciones_generales'))
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('fecha')
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('fecha')
                    ->label(__('inspeccion.visita_inspeccion.campos.fecha'))
                    ->date()
                    ->sortable(),
                TextColumn::make('inspector.name')
                    ->label(__('inspeccion.visita_inspeccion.campos.inspector'))
                    ->searchable(),
                TextColumn::make('tableros.tag')
                    ->label(__('inspeccion.visita_inspeccion.campos.tableros'))
                    ->badge(),
                TextColumn::make('estado_general')
                    ->label(__('inspeccion.visita_inspeccion.campos.estado_general'))
                    ->state(fn (VisitaInspeccion $record) => __('inspeccion.visita_inspeccion.estado_general.'.$record->estadoGeneral()))
                    ->badge(),
            ])
            ->filters(AccionesBorradoLogico::filtros())
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                AccionesBorradoLogico::editar(),
                ...AccionesBorradoLogico::registro(),
            ])
            ->toolbarActions([
                AccionesBorradoLogico::acciones(),
            ]);
    }
}
