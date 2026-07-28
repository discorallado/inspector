<?php

namespace Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Modules\Inspeccion\Models\EstadoAvance;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;

class TransicionEstadoPermitidaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tipo_catalogo')
                ->label('Catálogo')
                ->options([
                    TransicionEstadoPermitida::TIPO_ESTADO_AVANCE => __('inspeccion.catalogos.estado_avance'),
                    TransicionEstadoPermitida::TIPO_ESTADO_OBSERVACION => __('inspeccion.catalogos.estado_observacion'),
                    TransicionEstadoPermitida::TIPO_ESTADO_CAMBIO => __('inspeccion.catalogos.estado_cambio'),
                ])
                ->live()
                ->required(),
            Select::make('estado_origen_id')
                ->label('Estado origen')
                ->helperText('Vacío = estado inicial permitido al crear el registro.')
                ->options(fn (Get $get) => self::opciones($get('tipo_catalogo')))
                ->disabled(fn (Get $get) => blank($get('tipo_catalogo')))
                ->searchable(),
            Select::make('estado_destino_id')
                ->label('Estado destino')
                ->options(fn (Get $get) => self::opciones($get('tipo_catalogo')))
                ->disabled(fn (Get $get) => blank($get('tipo_catalogo')))
                ->searchable()
                ->required(),
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    private static function opciones(?string $tipoCatalogo): Collection
    {
        return match ($tipoCatalogo) {
            TransicionEstadoPermitida::TIPO_ESTADO_AVANCE => EstadoAvance::query()->pluck('nombre', 'id'),
            TransicionEstadoPermitida::TIPO_ESTADO_OBSERVACION => EstadoObservacion::query()->pluck('nombre', 'id'),
            TransicionEstadoPermitida::TIPO_ESTADO_CAMBIO => EstadoCambio::query()->pluck('nombre', 'id'),
            default => collect(),
        };
    }
}
