<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Solo lectura desde acá en adelante (hallazgo de /revisor, checkpoint
 * pre-PR6): HitoLegado se depreca a favor de Actividad/Tarea (ADR
 * 0009/0011) y este relation manager se reemplaza por
 * ActividadesRelationManager en PR6. No es solo el campo `item` (usado
 * como clave de matcheo del comando de migración, ya resuelto aparte con
 * `tareas.tablero_hito_id`) — cualquier campo editable acá (peso,
 * estado_avance_id, fechas) podía quedar sobrescrito en silencio si
 * `inspeccion:migrar-hitos-a-tareas` se vuelve a correr después de que
 * alguien ya trabajó directamente sobre Tarea: el comando es una
 * migración de una sola vía, no una sincronización continua. Congelar
 * `HitoLegado` es la forma más simple de garantizar que eso nunca pase.
 * Sigue siendo referencia histórica visible hasta el cleanup de PR9.
 */
class HitosLegadosRelationManager extends RelationManager
{
    protected static string $relationship = 'hitosLegados';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item')
            ->defaultSort('item')
            ->columns([
                TextColumn::make('item')
                    ->label(__('inspeccion.hito_legado.campos.item')),
                TextColumn::make('grupoHitoLegado.nombre')
                    ->label(__('inspeccion.hito_legado.campos.grupo_hito')),
                TextColumn::make('nombre')
                    ->label(__('inspeccion.hito_legado.campos.nombre'))
                    ->searchable(),
                TextColumn::make('estadoAvance.nombre')
                    ->label(__('inspeccion.hito_legado.campos.estado_avance'))
                    ->badge(),
                TextColumn::make('peso')
                    ->label(__('inspeccion.hito_legado.campos.peso'))
                    ->numeric(),
                TextColumn::make('plan_inicio')
                    ->label(__('inspeccion.hito_legado.campos.plan_inicio'))
                    ->date(),
                TextColumn::make('plan_fin')
                    ->label(__('inspeccion.hito_legado.campos.plan_fin'))
                    ->date(),
                TextColumn::make('real_inicio')
                    ->label(__('inspeccion.hito_legado.campos.real_inicio'))
                    ->date(),
                TextColumn::make('real_fin')
                    ->label(__('inspeccion.hito_legado.campos.real_fin'))
                    ->date(),
                TextColumn::make('responsable')
                    ->label(__('inspeccion.hito_legado.campos.responsable')),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public function canCreate(): bool
    {
        return false;
    }

    public function canEdit($record): bool
    {
        return false;
    }

    public function canDelete($record): bool
    {
        return false;
    }

    public function canDeleteAny(): bool
    {
        return false;
    }
}
