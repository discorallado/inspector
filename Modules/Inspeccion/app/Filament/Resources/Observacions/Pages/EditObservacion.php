<?php

namespace Modules\Inspeccion\Filament\Resources\Observacions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\Observacions\ObservacionResource;

class EditObservacion extends EditRecord
{
    protected static string $resource = ObservacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
