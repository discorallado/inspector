<?php

namespace Modules\Inspeccion\Filament\Resources\EstadoObservacions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\EstadoObservacions\EstadoObservacionResource;

class EditEstadoObservacion extends EditRecord
{
    protected static string $resource = EstadoObservacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
