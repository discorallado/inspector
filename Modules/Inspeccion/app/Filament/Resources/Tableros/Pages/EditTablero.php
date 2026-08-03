<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;
use Modules\Inspeccion\Filament\Support\AccionesBorradoFisico;

class EditTablero extends EditRecord
{
    protected static string $resource = TableroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label(__('inspeccion.tarea.kanban.title'))
                ->icon(Heroicon::OutlinedViewColumns)
                ->url(fn (): string => TableroResource::getUrl('kanban', ['record' => $this->record])),
            Action::make('gantt')
                ->label(__('inspeccion.tarea.gantt.title'))
                ->icon(Heroicon::OutlinedChartBar)
                ->url(fn (): string => TableroResource::getUrl('gantt', ['record' => $this->record])),
            Action::make('actividadesResumen')
                ->label(__('inspeccion.actividad.resumen.title'))
                ->icon(Heroicon::OutlinedTableCells)
                ->url(fn (): string => TableroResource::getUrl('actividades-resumen', ['record' => $this->record])),
            AccionesBorradoFisico::eliminar(),
        ];
    }
}
