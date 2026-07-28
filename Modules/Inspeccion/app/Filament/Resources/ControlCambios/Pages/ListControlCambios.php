<?php

namespace Modules\Inspeccion\Filament\Resources\ControlCambios\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Inspeccion\Filament\Resources\ControlCambios\ControlCambioResource;

class ListControlCambios extends ListRecords
{
    protected static string $resource = ControlCambioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label(__('inspeccion.control_cambio.acciones.ver_kanban'))
                ->icon(Heroicon::OutlinedViewColumns)
                ->color('gray')
                ->url(fn (): string => ControlCambioResource::getUrl('board')),
            CreateAction::make(),
        ];
    }
}
