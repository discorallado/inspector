<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TableroForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('proyecto_id')
                ->label(__('inspeccion.tablero.campos.proyecto'))
                ->relationship('proyecto', 'nombre')
                ->searchable()
                ->preload()
                ->live()
                ->required()
                ->createOptionForm([
                    TextInput::make('nombre')
                        ->label(__('inspeccion.proyecto.campos.nombre'))
                        ->required(),
                ]),
            TextInput::make('tag')
                ->label(__('inspeccion.tablero.campos.tag'))
                ->required()
                ->unique(
                    modifyRuleUsing: fn (Builder $rule, Get $get) => $rule->where('proyecto_id', $get('proyecto_id')),
                    ignoreRecord: true,
                ),
            TextInput::make('nombre')
                ->label(__('inspeccion.tablero.campos.nombre'))
                ->required(),
            TextInput::make('fabricante')
                ->label(__('inspeccion.tablero.campos.fabricante')),
            TextInput::make('oc_contrato')
                ->label(__('inspeccion.tablero.campos.oc_contrato')),
        ]);
    }
}
