<?php

namespace Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\VisitaInspeccionResource;

class EditVisitaInspeccion extends EditRecord
{
    protected static string $resource = VisitaInspeccionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
