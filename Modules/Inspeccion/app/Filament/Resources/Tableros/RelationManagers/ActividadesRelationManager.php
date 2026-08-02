<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inspeccion\Filament\Resources\Actividades\ActividadResource;
use Modules\Inspeccion\Filament\Support\AccionesBorradoLogico;
use Modules\Inspeccion\Models\Actividad;

/**
 * Reemplaza a HitosLegadosRelationManager como vista principal de avance
 * (ADR 0009/0012 — HitoLegado queda de solo lectura, deprecado). Alcance
 * acotado a un RelationManager plano + drill-down por acción "Ver tareas"
 * hacia ActividadResource (no un accordion custom estilo axon): Tablero
 * ->tareas() es un HasManyThrough sin soporte de create(), por lo que
 * el CRUD de Tarea vive en ActividadResource\RelationManagers\TareasRelationManager,
 * donde Actividad->tareas() sí es un HasMany real. La UX de accordion
 * completa queda abierta como follow-up si se necesita más adelante.
 */
class ActividadesRelationManager extends RelationManager
{
    protected static string $relationship = 'actividades';

    public function form(Schema $schema): Schema
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->defaultSort('orden')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('tareas'))
            ->columns([
                TextColumn::make('nombre')
                    ->label(__('inspeccion.actividad.campos.nombre'))
                    ->searchable(),
                TextColumn::make('orden')
                    ->label(__('inspeccion.actividad.campos.orden')),
                TextColumn::make('tareas_count')
                    ->label(__('inspeccion.actividad.campos.cantidad_tareas'))
                    ->counts('tareas'),
                TextColumn::make('avance')
                    ->label(__('inspeccion.actividad.campos.avance'))
                    ->state(fn (Actividad $record) => $record->avance())
                    ->suffix('%')
                    ->placeholder('—'),
                TextColumn::make('start_date')
                    ->label(__('inspeccion.actividad.campos.start_date'))
                    ->date(),
                TextColumn::make('end_date')
                    ->label(__('inspeccion.actividad.campos.end_date'))
                    ->date(),
            ])
            ->filters(AccionesBorradoLogico::filtros())
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('verTareas')
                    ->label(__('inspeccion.actividad.plural').' → '.__('inspeccion.tarea.plural'))
                    ->icon(Heroicon::OutlinedListBullet)
                    ->url(fn (Actividad $record) => ActividadResource::getUrl('edit', ['record' => $record])),
                AccionesBorradoLogico::editar(),
                ...AccionesBorradoLogico::registro(),
            ])
            ->toolbarActions([
                AccionesBorradoLogico::acciones(),
            ]);
    }
}
