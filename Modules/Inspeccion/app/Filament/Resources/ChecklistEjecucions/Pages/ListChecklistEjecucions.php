<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\ChecklistEjecucionResource;

class ListChecklistEjecucions extends ListRecords
{
    protected static string $resource = ChecklistEjecucionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
