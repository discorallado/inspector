<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Resources\Pruebas\PruebaResource;
use Modules\Inspeccion\Filament\Support\AccionesBorradoLogico;
use Modules\Inspeccion\Models\Prueba;
use Modules\Inspeccion\Models\PruebaTemplate;

/**
 * Punto de entrada de Prueba (ADR de rename Checklist->Prueba): antes se
 * llegaba desde VisitaInspeccion, ahora desde acá — visita_inspeccion_id
 * es opcional (Select sin required, acotado a las visitas que ya cubren
 * este tablero). $relatedResource: la edición navega a PruebaResource
 * (tiene su propia ItemsRelationManager anidada, no entra en un modal).
 */
class PruebasRelationManager extends RelationManager
{
    protected static string $relationship = 'pruebas';

    protected static ?string $relatedResource = PruebaResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('visita_inspeccion_id')
                ->label(__('inspeccion.visita_inspeccion.singular'))
                ->options(fn () => $this->getOwnerRecord()->visitasInspeccion()->pluck('fecha', 'visitas_inspeccion.id'))
                ->searchable(),
            Select::make('prueba_template_id')
                ->label(__('inspeccion.prueba.template.singular'))
                ->relationship('pruebaTemplate', 'nombre')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('visitaInspeccion.fecha')
                    ->label(__('inspeccion.visita_inspeccion.campos.fecha'))
                    ->date()
                    ->placeholder('—'),
                TextColumn::make('pruebaTemplate.nombre')
                    ->label(__('inspeccion.prueba.template.singular')),
                TextColumn::make('items_count')
                    ->label(__('inspeccion.prueba.campos.item'))
                    ->counts('items'),
            ])
            ->filters(AccionesBorradoLogico::filtros())
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data) {
                        $template = PruebaTemplate::query()->findOrFail($data['prueba_template_id']);

                        return Prueba::crearDesdeTemplate([
                            'tablero_id' => $this->getOwnerRecord()->id,
                            'visita_inspeccion_id' => $data['visita_inspeccion_id'] ?? null,
                        ], $template);
                    }),
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
