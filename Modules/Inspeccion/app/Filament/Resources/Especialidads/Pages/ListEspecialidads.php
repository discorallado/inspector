<?php

namespace Modules\Inspeccion\Filament\Resources\Especialidads\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\Especialidads\EspecialidadResource;

class ListEspecialidads extends ListRecords
{
    protected static string $resource = EspecialidadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
