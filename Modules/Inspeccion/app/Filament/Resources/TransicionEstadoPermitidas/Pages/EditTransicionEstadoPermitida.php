<?php

namespace Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\TransicionEstadoPermitidaResource;

class EditTransicionEstadoPermitida extends EditRecord
{
    protected static string $resource = TransicionEstadoPermitidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
