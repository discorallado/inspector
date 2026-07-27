<?php

namespace Modules\Inspeccion\Filament\Resources\VisitaInspeccions\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\ChecklistEjecucionResource;
use Modules\Inspeccion\Models\ChecklistEjecucion;
use Modules\Inspeccion\Models\ChecklistTemplate;

class ChecklistEjecucionesRelationManager extends RelationManager
{
    protected static string $relationship = 'checklistEjecuciones';

    protected static ?string $relatedResource = ChecklistEjecucionResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tablero_id')
                ->label(__('inspeccion.observacion.campos.tablero'))
                ->options(fn () => $this->getOwnerRecord()->tableros()->pluck('tag', 'tableros.id'))
                ->required(),
            Select::make('checklist_template_id')
                ->label(__('inspeccion.checklist.template.singular'))
                ->relationship('checklistTemplate', 'nombre')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('tablero.tag')
                    ->label(__('inspeccion.observacion.campos.tablero')),
                TextColumn::make('checklistTemplate.nombre')
                    ->label(__('inspeccion.checklist.template.singular')),
                TextColumn::make('items_count')
                    ->label(__('inspeccion.checklist.campos.item'))
                    ->counts('items'),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data) {
                        $template = ChecklistTemplate::query()->findOrFail($data['checklist_template_id']);

                        return ChecklistEjecucion::crearDesdeTemplate([
                            'visita_inspeccion_id' => $this->getOwnerRecord()->id,
                            'tablero_id' => $data['tablero_id'],
                        ], $template);
                    }),
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
}
