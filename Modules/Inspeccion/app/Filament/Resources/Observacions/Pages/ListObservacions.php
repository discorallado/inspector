<?php

namespace Modules\Inspeccion\Filament\Resources\Observacions\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Inspeccion\Filament\Resources\Observacions\ObservacionResource;

class ListObservacions extends ListRecords
{
    protected static string $resource = ObservacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label(__('inspeccion.observacion.acciones.ver_kanban'))
                ->icon(Heroicon::OutlinedViewColumns)
                ->color('gray')
                ->url(fn (): string => ObservacionResource::getUrl('board')),
            CreateAction::make(),
        ];
    }
}
