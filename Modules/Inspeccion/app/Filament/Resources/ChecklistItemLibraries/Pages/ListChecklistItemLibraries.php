<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistItemLibraries\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\ChecklistItemLibraries\ChecklistItemLibraryResource;

class ListChecklistItemLibraries extends ListRecords
{
    protected static string $resource = ChecklistItemLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
