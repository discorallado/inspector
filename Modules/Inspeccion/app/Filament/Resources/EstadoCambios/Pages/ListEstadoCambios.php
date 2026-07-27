<?php

namespace Modules\Inspeccion\Filament\Resources\EstadoCambios\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\EstadoCambios\EstadoCambioResource;

class ListEstadoCambios extends ListRecords
{
    protected static string $resource = EstadoCambioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
