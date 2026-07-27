<?php

namespace Modules\Inspeccion\Filament\Resources\TipoObservacions\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\TipoObservacions\TipoObservacionResource;

class ListTipoObservacions extends ListRecords
{
    protected static string $resource = TipoObservacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
