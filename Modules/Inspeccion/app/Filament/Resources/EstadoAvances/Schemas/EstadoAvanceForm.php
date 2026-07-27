<?php

namespace Modules\Inspeccion\Filament\Resources\EstadoAvances\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EstadoAvanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')
                ->label(__('inspeccion.catalogos.campos.nombre'))
                ->required(),
            TextInput::make('codigo')
                ->label(__('inspeccion.catalogos.campos.codigo'))
                ->helperText('Identificador estable usado por las transiciones de estado permitidas.')
                ->required(),
            TextInput::make('valor')
                ->label(__('inspeccion.catalogos.campos.valor'))
                ->helperText('Fracción de avance que aporta este estado (0, 0.5 o 1).')
                ->numeric()
                ->step(0.01)
                ->minValue(0)
                ->maxValue(1)
                ->required(),
            Toggle::make('excluye_calculo')
                ->label(__('inspeccion.catalogos.campos.excluye_calculo'))
                ->default(false)
                ->required(),
            TextInput::make('orden')
                ->label(__('inspeccion.catalogos.campos.orden'))
                ->numeric()
                ->default(0)
                ->required(),
        ]);
    }
}
