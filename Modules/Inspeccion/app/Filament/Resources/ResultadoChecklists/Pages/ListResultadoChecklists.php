<?php

namespace Modules\Inspeccion\Filament\Resources\ResultadoChecklists\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\ResultadoChecklists\ResultadoChecklistResource;

class ListResultadoChecklists extends ListRecords
{
    protected static string $resource = ResultadoChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
