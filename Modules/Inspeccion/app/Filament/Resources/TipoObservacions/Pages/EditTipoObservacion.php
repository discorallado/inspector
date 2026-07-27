<?php

namespace Modules\Inspeccion\Filament\Resources\TipoObservacions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\TipoObservacions\TipoObservacionResource;

class EditTipoObservacion extends EditRecord
{
    protected static string $resource = TipoObservacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
