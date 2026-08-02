<?php

namespace Modules\Inspeccion\Filament\Resources\Pruebas\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\Pruebas\PruebaResource;

class ListPruebas extends ListRecords
{
    protected static string $resource = PruebaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
