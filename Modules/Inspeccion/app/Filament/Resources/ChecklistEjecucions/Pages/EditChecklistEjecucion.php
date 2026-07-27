<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\ChecklistEjecucionResource;

class EditChecklistEjecucion extends EditRecord
{
    protected static string $resource = ChecklistEjecucionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
