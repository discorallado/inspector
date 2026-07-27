<?php

namespace Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
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
                ->required(),
            TextInput::make('estado_origen_id')
                ->label('ID estado origen')
                ->helperText('Vacío = estado inicial permitido al crear el registro.')
                ->numeric(),
            TextInput::make('estado_destino_id')
                ->label('ID estado destino')
                ->helperText('ID de la fila del catálogo seleccionado arriba (ver su listado para el ID).')
                ->required()
                ->numeric(),
        ]);
    }
}
