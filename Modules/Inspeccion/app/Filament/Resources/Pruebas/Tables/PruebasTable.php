<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Support\AccionesBorradoLogico;

class ChecklistEjecucionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visitaInspeccion.fecha')
                    ->label(__('inspeccion.visita_inspeccion.campos.fecha'))
                    ->date(),
                TextColumn::make('tablero.tag')
                    ->label(__('inspeccion.observacion.campos.tablero')),
                TextColumn::make('checklistTemplate.nombre')
                    ->label(__('inspeccion.checklist.template.singular')),
                TextColumn::make('items_count')
                    ->label(__('inspeccion.checklist.campos.item'))
                    ->counts('items'),
            ])
            ->filters(AccionesBorradoLogico::filtros())
            ->recordActions([
                AccionesBorradoLogico::editar(),
                ...AccionesBorradoLogico::registro(),
            ])
            ->toolbarActions([
                AccionesBorradoLogico::acciones(),
            ]);
    }
}
