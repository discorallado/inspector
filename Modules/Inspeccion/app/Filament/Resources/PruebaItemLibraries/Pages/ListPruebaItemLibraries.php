<?php

namespace Modules\Inspeccion\Filament\Resources\PruebaItemLibraries\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\PruebaItemLibraries\PruebaItemLibraryResource;

class ListPruebaItemLibraries extends ListRecords
{
    protected static string $resource = PruebaItemLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
