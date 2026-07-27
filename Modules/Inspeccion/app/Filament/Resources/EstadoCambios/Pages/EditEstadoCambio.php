<?php

namespace Modules\Inspeccion\Filament\Resources\EstadoCambios\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\EstadoCambios\EstadoCambioResource;

class EditEstadoCambio extends EditRecord
{
    protected static string $resource = EstadoCambioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
