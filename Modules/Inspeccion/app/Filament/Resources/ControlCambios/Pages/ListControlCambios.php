<?php

namespace Modules\Inspeccion\Filament\Resources\ControlCambios\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\ControlCambios\ControlCambioResource;

class ListControlCambios extends ListRecords
{
    protected static string $resource = ControlCambioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
