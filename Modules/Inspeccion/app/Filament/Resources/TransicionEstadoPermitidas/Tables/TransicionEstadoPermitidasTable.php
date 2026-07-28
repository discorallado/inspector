<?php

namespace Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Inspeccion\Models\EstadoAvance;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;

class TransicionEstadoPermitidasTable
{
    public static function configure(Table $table): Table
    {
        // Se cargan los 3 catálogos una sola vez por render de tabla (no por
        // fila/columna) para evitar N+1 al resolver el nombre de cada estado.
        $nombresPorCatalogo = [
            TransicionEstadoPermitida::TIPO_ESTADO_AVANCE => EstadoAvance::query()->pluck('nombre', 'id'),
            TransicionEstadoPermitida::TIPO_ESTADO_OBSERVACION => EstadoObservacion::query()->pluck('nombre', 'id'),
            TransicionEstadoPermitida::TIPO_ESTADO_CAMBIO => EstadoCambio::query()->pluck('nombre', 'id'),
        ];

        $nombreEstado = fn (string $tipoCatalogo, ?int $estadoId): ?string => $estadoId === null
            ? null
            : ($nombresPorCatalogo[$tipoCatalogo][$estadoId] ?? null);

        return $table
            ->columns([
                TextColumn::make('tipo_catalogo')
                    ->label('Catálogo')
                    ->searchable(),
                TextColumn::make('estado_origen_id')
                    ->label('Estado origen')
                    ->state(fn (TransicionEstadoPermitida $record) => $nombreEstado($record->tipo_catalogo, $record->estado_origen_id))
                    ->placeholder('— (inicial)'),
                TextColumn::make('estado_destino_id')
                    ->label('Estado destino')
                    ->state(fn (TransicionEstadoPermitida $record) => $nombreEstado($record->tipo_catalogo, $record->estado_destino_id)),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
