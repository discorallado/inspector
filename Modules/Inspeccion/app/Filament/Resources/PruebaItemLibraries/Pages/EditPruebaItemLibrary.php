<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistItemLibraries\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\ChecklistItemLibraries\ChecklistItemLibraryResource;

class EditChecklistItemLibrary extends EditRecord
{
    protected static string $resource = ChecklistItemLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
