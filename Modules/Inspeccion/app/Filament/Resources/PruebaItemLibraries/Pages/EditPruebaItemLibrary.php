<?php

namespace Modules\Inspeccion\Filament\Resources\PruebaItemLibraries\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\PruebaItemLibraries\PruebaItemLibraryResource;

class EditPruebaItemLibrary extends EditRecord
{
    protected static string $resource = PruebaItemLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
