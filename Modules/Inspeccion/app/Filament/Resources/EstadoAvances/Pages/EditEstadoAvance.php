<?php

namespace Modules\Inspeccion\Filament\Resources\EstadoAvances\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\EstadoAvances\EstadoAvanceResource;

class EditEstadoAvance extends EditRecord
{
    protected static string $resource = EstadoAvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
