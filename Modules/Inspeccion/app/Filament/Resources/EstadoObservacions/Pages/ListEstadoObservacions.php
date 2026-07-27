<?php

namespace Modules\Inspeccion\Filament\Resources\EstadoObservacions\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\EstadoObservacions\EstadoObservacionResource;

class ListEstadoObservacions extends ListRecords
{
    protected static string $resource = EstadoObservacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
