<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;
use Modules\Inspeccion\Models\Actividad;

/**
 * Port de axon (ProjectResource\Pages\ViewActivity, ver ADR de
 * comentarios): vista de detalle de una Actividad específica, con sus
 * Tareas y los comentarios de cada una, más los comentarios de la
 * Actividad misma. Se llega acá desde el árbol ("Ver detalle" por fila)
 * o desde el contador de comentarios del Kanban (con ?focus=).
 */
class ActividadDetalle extends Page
{
    use InteractsWithRecord;

    protected static string $resource = TableroResource::class;

    protected string $view = 'inspeccion::filament.resources.tableros.pages.actividad-detalle';

    public Actividad $actividad;

    public ?int $focusTareaId = null;

    /**
     * El segundo parámetro de ruta NO puede llamarse `actividad` (aunque
     * `$this->actividad` sí): un route param cuyo nombre matchea un
     * modelo Eloquent existente dispara el binding implícito de Laravel
     * *incluso* estando tipado `int|string` acá — Filament resuelve y
     * serializa el modelo completo antes de que este método lo reciba,
     * llega como el JSON del registro entero en vez del id. Reproducido:
     * ver el commit de este archivo.
     */
    public function mount(int|string $record, int|string $actividadId): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorize('view', $this->record);

        $this->actividad = Actividad::query()
            ->where('id', $actividadId)
            ->where('tablero_id', $this->record->id)
            ->with(['tareas' => fn ($query) => $query->orderBy('orden')])
            ->firstOrFail();

        $this->focusTareaId = request()->integer('focus') ?: null;
    }

    public function getTitle(): string
    {
        return $this->actividad->nombre;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')
                ->label(__('inspeccion.actividad.detalle.volver'))
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(fn (): string => TableroResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
