<?php

namespace Modules\Inspeccion\Filament\Resources\Severidads\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\Severidads\SeveridadResource;

class EditSeveridad extends EditRecord
{
    protected static string $resource = SeveridadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
