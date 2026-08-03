<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Pages;

use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;
use Modules\Inspeccion\Models\Tarea;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

/**
 * Port del pedido del usuario ("página de actividades con tabla de tareas
 * agrupadas... peso de cada actividad... select de estado... avance % en
 * tiempo real"). A diferencia del árbol (ActividadesRelationManager, ADR
 * 0019), acá SÍ calza el pipeline nativo de Filament\Tables\Table — no
 * hace falta un componente custom: agrupado = groups(), select inline =
 * SelectColumn, tiempo real = poll(). Ver ADR de peso ponderado por
 * Actividad.
 *
 * Deliberadamente sin CRUD completo (crear/eliminar Tarea, editar todos
 * los campos): eso ya vive en el árbol embebido de EditTablero. Esta
 * página es una vista de lectura + edición rápida (estado, pesos), no un
 * segundo lugar para el mismo CRUD — reusar EditAction acá directo habría
 * ignorado en silencio la sincronización de predecesoras (TareaForm tiene
 * un campo virtual `predecessors` que EditAction no sabe sincronizar sin
 * el fillForm()/action() custom que ya tiene ActividadesRelationManager).
 */
class TableroActividadesResumen extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = TableroResource::class;

    protected string $view = 'inspeccion::filament.resources.tableros.pages.tablero-actividades-resumen';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorize('view', $this->record);
    }

    public function getTitle(): string
    {
        return __('inspeccion.actividad.resumen.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Tarea::query()
                    ->whereHas('actividad', fn ($query) => $query->where('tablero_id', $this->record->id))
                    ->with('actividad')
            )
            ->groups([
                Group::make('actividad.nombre')
                    ->label(__('inspeccion.actividad.singular')),
            ])
            ->defaultGroup('actividad.nombre')
            ->defaultSort('orden')
            ->poll('10s')
            ->columns([
                TextColumn::make('code')
                    ->label(__('inspeccion.tarea.campos.code'))
                    ->searchable(),
                TextColumn::make('nombre')
                    ->label(__('inspeccion.tarea.campos.nombre'))
                    ->searchable(),
                SelectColumn::make('status')
                    ->label(__('inspeccion.tarea.campos.status'))
                    ->options(fn (Tarea $record) => static::opcionesEstadoDestino($record))
                    ->disabled(fn (Tarea $record) => ! Gate::allows('update', $record)),
                TextColumn::make('avance')
                    ->label(__('inspeccion.actividad.resumen.avance_tarea'))
                    ->state(fn (Tarea $record) => $record->status->valor() * 100)
                    ->suffix('%'),
                TextColumn::make('peso')
                    ->label(__('inspeccion.actividad.resumen.peso_tarea'))
                    ->numeric()
                    ->summarize(Sum::make()->label(__('inspeccion.actividad.resumen.peso_tarea_total'))),
                TextInputColumn::make('actividad.peso')
                    ->label(__('inspeccion.actividad.resumen.peso_actividad'))
                    ->type('number')
                    ->state(fn (Tarea $record) => $record->actividad->peso)
                    ->disabled(fn (Tarea $record) => ! Gate::allows('update', $record->actividad))
                    ->updateStateUsing(function (Tarea $record, $state) {
                        $record->actividad->update(['peso' => $state]);

                        return $state;
                    }),
            ]);
    }

    /**
     * Mismo patrón que ControlCambiosTable::opcionesEstadoDestino(): las
     * opciones del SelectColumn se restringen a transiciones realmente
     * válidas desde el estado actual — así un salto inválido ni siquiera
     * aparece como opción, en vez de aceptarse y fallar server-side.
     *
     * @return array<string, string>
     */
    private static function opcionesEstadoDestino(Tarea $tarea): array
    {
        $codigosAlcanzables = app(TransicionEstadoGuard::class)
            ->transicionesValidasDesdePorCodigo(TransicionEstadoPermitida::TIPO_TAREA_STATUS, $tarea->status->value)
            ->push($tarea->status->value)
            ->unique();

        return collect(TaskStatus::cases())
            ->filter(fn (TaskStatus $status) => $codigosAlcanzables->contains($status->value))
            ->mapWithKeys(fn (TaskStatus $status) => [$status->value => $status->getLabel()])
            ->all();
    }
}
