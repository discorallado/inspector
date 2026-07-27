<?php

namespace Modules\Inspeccion\Filament\Resources\Especialidads\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\Especialidads\EspecialidadResource;

class EditEspecialidad extends EditRecord
{
    protected static string $resource = EspecialidadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
