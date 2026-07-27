<?php

namespace Modules\Inspeccion\Filament\Resources\Severidads\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\Severidads\SeveridadResource;

class ListSeveridads extends ListRecords
{
    protected static string $resource = SeveridadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
