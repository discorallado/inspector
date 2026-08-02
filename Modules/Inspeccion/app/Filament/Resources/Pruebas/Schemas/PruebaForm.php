<?php

namespace Modules\Inspeccion\Filament\Resources\Pruebas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class PruebaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tablero_id')
                ->label(__('inspeccion.observacion.campos.tablero'))
                ->relationship('tablero', 'tag')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('visita_inspeccion_id')
                ->label(__('inspeccion.visita_inspeccion.singular'))
                ->relationship('visitaInspeccion', 'fecha')
                ->searchable()
                ->preload(),
            // Solo al crear: el snapshot de ítems se toma una vez (ver
            // Prueba::crearDesdeTemplate()) — cambiar la plantilla después
            // dejaría los ítems ya creados inconsistentes con ella.
            Select::make('prueba_template_id')
                ->label(__('inspeccion.prueba.template.singular'))
                ->relationship('pruebaTemplate', 'nombre')
                ->required()
                ->visible(fn (string $operation): bool => $operation === 'create'),
        ]);
    }
}
