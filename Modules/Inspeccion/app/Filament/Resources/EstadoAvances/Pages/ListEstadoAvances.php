<?php

namespace Modules\Inspeccion\Filament\Resources\EstadoAvances\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\EstadoAvances\EstadoAvanceResource;

class ListEstadoAvances extends ListRecords
{
    protected static string $resource = EstadoAvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
