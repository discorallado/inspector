<?php

namespace Modules\Inspeccion\Filament\Resources\ResultadoChecklists\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\ResultadoChecklists\ResultadoChecklistResource;

class EditResultadoChecklist extends EditRecord
{
    protected static string $resource = ResultadoChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
