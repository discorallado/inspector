<?php

namespace Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\TransicionEstadoPermitidaResource;

class ListTransicionEstadoPermitidas extends ListRecords
{
    protected static string $resource = TransicionEstadoPermitidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
