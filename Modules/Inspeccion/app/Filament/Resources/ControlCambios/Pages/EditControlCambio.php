<?php

namespace Modules\Inspeccion\Filament\Resources\ControlCambios\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\ControlCambios\ControlCambioResource;

class EditControlCambio extends EditRecord
{
    protected static string $resource = ControlCambioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
