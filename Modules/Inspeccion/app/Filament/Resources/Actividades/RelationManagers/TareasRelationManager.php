<?php

namespace Modules\Inspeccion\Filament\Resources\Actividades\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Modules\Inspeccion\Enums\TaskPriority;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Filament\Support\AccionesBorradoLogico;

class TareasRelationManager extends RelationManager
{
    protected static string $relationship = 'tareas';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label(__('inspeccion.tarea.campos.code'))
                ->required()
                ->unique(
                    modifyRuleUsing: fn (Unique $rule) => $rule->where('actividad_id', $this->getOwnerRecord()->id),
                    ignoreRecord: true,
                ),
            TextInput::make('nombre')
                ->label(__('inspeccion.tarea.campos.nombre'))
                ->required()
                ->columnSpanFull(),
            TextInput::make('descripcion')
                ->label(__('inspeccion.tarea.campos.descripcion'))
                ->columnSpanFull(),
            Select::make('status')
                ->label(__('inspeccion.tarea.campos.status'))
                ->options(TaskStatus::class)
                ->required(),
            Select::make('priority')
                ->label(__('inspeccion.tarea.campos.priority'))
                ->options(TaskPriority::class)
                ->required(),
            TextInput::make('peso')
                ->label(__('inspeccion.tarea.campos.peso'))
                ->numeric(),
            Toggle::make('excluye_calculo')
                ->label(__('inspeccion.tarea.campos.excluye_calculo')),
            DatePicker::make('start_date')
                ->label(__('inspeccion.tarea.campos.start_date')),
            DatePicker::make('due_date')
                ->label(__('inspeccion.tarea.campos.due_date')),
            DatePicker::make('real_inicio')
                ->label(__('inspeccion.tarea.campos.real_inicio')),
            DatePicker::make('real_fin')
                ->label(__('inspeccion.tarea.campos.real_fin')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->defaultSort('orden')
            ->columns([
                TextColumn::make('code')
                    ->label(__('inspeccion.tarea.campos.code'))
                    ->searchable(),
                TextColumn::make('nombre')
                    ->label(__('inspeccion.tarea.campos.nombre'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('inspeccion.tarea.campos.status'))
                    ->badge(),
                TextColumn::make('priority')
                    ->label(__('inspeccion.tarea.campos.priority'))
                    ->badge(),
                TextColumn::make('peso')
                    ->label(__('inspeccion.tarea.campos.peso'))
                    ->numeric(),
                ToggleColumn::make('excluye_calculo')
                    ->label(__('inspeccion.tarea.campos.excluye_calculo')),
                TextColumn::make('due_date')
                    ->label(__('inspeccion.tarea.campos.due_date'))
                    ->date(),
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
