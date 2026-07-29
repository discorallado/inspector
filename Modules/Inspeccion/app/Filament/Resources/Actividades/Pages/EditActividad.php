<?php

namespace Modules\Inspeccion\Filament\Resources\Actividades\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\Actividades\ActividadResource;

class EditActividad extends EditRecord
{
    protected static string $resource = ActividadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
