<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Inspeccion\Enums\TaskPriority;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Tarea;

class TareaForm
{
    /**
     * actividad_id viaja como campo oculto (no una columna FK resuelta por
     * relationship de Filament) para que este schema no dependa de un
     * RelationManager anidado bajo Actividad — quien lo invoca (el árbol de
     * ActividadesRelationManager) lo rellena vía fillForm() antes de abrir
     * el modal, tanto para crear como para editar.
     *
     * $excludeTareaId: al editar, para que la Tarea no pueda elegirse a sí
     * misma como predecesora — en creación es null porque la Tarea todavía
     * no tiene id.
     */
    public static function configure(Schema $schema, ?int $excludeTareaId = null): Schema
    {
        return $schema->components([
            Section::make(__('inspeccion.tarea.secciones.datos'))
                ->description(__('inspeccion.tarea.secciones.datos_ayuda'))
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->schema([
                    Hidden::make('actividad_id')->required(),
                    // code ya no se escribe a mano: se recalcula solo al
                    // crear/insertar/reordenar (ActividadesRelationManager),
                    // acá es puramente informativo. No existe todavía al
                    // crear -> solo se muestra editando.
                    TextInput::make('code')
                        ->label(__('inspeccion.tarea.campos.code'))
                        ->helperText(__('inspeccion.tarea.campos.code_ayuda'))
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?Tarea $record) => $record !== null),
                    TextInput::make('nombre')
                        ->label(__('inspeccion.tarea.campos.nombre'))
                        ->required(),
                    TextInput::make('descripcion')
                        ->label(__('inspeccion.tarea.campos.descripcion')),
                ]),
            Section::make(__('inspeccion.tarea.secciones.estado'))
                ->description(__('inspeccion.tarea.secciones.estado_ayuda'))
                ->icon(Heroicon::OutlinedFlag)
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label(__('inspeccion.tarea.campos.status'))
                        ->options(TaskStatus::class)
                        ->required(),
                    Select::make('priority')
                        ->label(__('inspeccion.tarea.campos.priority'))
                        ->options(TaskPriority::class)
                        ->required(),
                    TextInput::make('peso')
                        ->label(__('inspeccion.tarea.campos.peso'))
                        ->helperText(__('inspeccion.tarea.campos.peso_ayuda'))
                        ->numeric(),
                    Toggle::make('excluye_calculo')
                        ->label(__('inspeccion.tarea.campos.excluye_calculo'))
                        ->helperText(__('inspeccion.tarea.campos.excluye_calculo_ayuda')),
                ]),
            Section::make(__('inspeccion.tarea.secciones.fechas'))
                ->description(__('inspeccion.tarea.secciones.fechas_ayuda'))
                ->icon(Heroicon::OutlinedCalendarDays)
                ->columns(2)
                ->schema([
                    DatePicker::make('start_date')->label(__('inspeccion.tarea.campos.start_date')),
                    DatePicker::make('due_date')->label(__('inspeccion.tarea.campos.due_date')),
                    DatePicker::make('real_inicio')->label(__('inspeccion.tarea.campos.real_inicio')),
                    DatePicker::make('real_fin')->label(__('inspeccion.tarea.campos.real_fin')),
                ]),
            Section::make(__('inspeccion.tarea.secciones.dependencias'))
                ->description(__('inspeccion.tarea.secciones.dependencias_ayuda'))
                ->icon(Heroicon::OutlinedLink)
                ->schema([
                    Select::make('predecessors')
                        ->label(__('inspeccion.tarea.campos.predecesoras'))
                        ->helperText(__('inspeccion.tarea.arbol.predecesoras_ayuda'))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(function (Get $get) use ($excludeTareaId) {
                            $actividad = Actividad::find($get('actividad_id'));

                            if (! $actividad) {
                                return [];
                            }

                            return Tarea::query()
                                ->whereHas('actividad', fn ($query) => $query->where('tablero_id', $actividad->tablero_id))
                                ->when($excludeTareaId, fn ($query) => $query->whereKeyNot($excludeTareaId))
                                ->with('actividad')
                                ->orderBy('orden')
                                ->get()
                                ->mapWithKeys(fn (Tarea $tarea) => [
                                    $tarea->id => ($tarea->actividad?->nombre ? $tarea->actividad->nombre.' — ' : '').$tarea->nombre,
                                ])
                                ->all();
                        }),
                ]),
        ]);
    }
}
