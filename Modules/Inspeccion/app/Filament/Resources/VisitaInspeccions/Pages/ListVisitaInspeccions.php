<?php

namespace Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\VisitaInspeccionResource;

class ListVisitaInspeccions extends ListRecords
{
    protected static string $resource = VisitaInspeccionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
