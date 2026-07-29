<?php

namespace Modules\Inspeccion\Filament\Resources\Observacions\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\Observacions\ObservacionResource;

class ListObservacions extends ListRecords
{
    protected static string $resource = ObservacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
