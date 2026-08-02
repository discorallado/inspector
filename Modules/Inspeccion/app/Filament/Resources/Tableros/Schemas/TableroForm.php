<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;
use Modules\Inspeccion\Models\Tablero;

class TableroForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Section::make(__('inspeccion.tablero.secciones.datos'))
                    ->schema([
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
                                modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('proyecto_id', $get('proyecto_id')),
                                ignoreRecord: true,
                            ),
                        TextInput::make('nombre')
                            ->label(__('inspeccion.tablero.campos.nombre'))
                            ->required(),
                        TextInput::make('fabricante')
                            ->label(__('inspeccion.tablero.campos.fabricante')),
                        TextInput::make('oc_contrato')
                            ->label(__('inspeccion.tablero.campos.oc_contrato')),
                    ]),
                // Solo lectura: avance_global/avance_calculado_at los
                // escribe CalculadorAvanceTablero (vía TareaObserver), no
                // un usuario a mano — TextEntry en vez de TextInput deja
                // eso visualmente inequívoco. Oculto al crear: un Tablero
                // nuevo todavía no tiene avance que mostrar.
                Section::make(__('inspeccion.tablero.secciones.avance'))
                    ->schema([
                        TextEntry::make('avance_global')
                            ->label(__('inspeccion.tablero.campos.avance_global'))
                            ->suffix('%')
                            ->placeholder('—'),
                        TextEntry::make('avance_calculado_at')
                            ->label(__('inspeccion.tablero.campos.avance_calculado_at'))
                            ->dateTime()
                            ->placeholder('—'),
                    ])
                    ->visible(fn (?Tablero $record) => $record !== null),
            ]),
        ]);
    }
}
