<?php

namespace Modules\Inspeccion\Filament\Resources\Pruebas\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\Pruebas\PruebaResource;

class EditPrueba extends EditRecord
{
    protected static string $resource = PruebaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
