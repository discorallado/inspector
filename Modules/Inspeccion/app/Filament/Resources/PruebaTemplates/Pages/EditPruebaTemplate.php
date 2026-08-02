<?php

namespace Modules\Inspeccion\Filament\Resources\PruebaTemplates\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\PruebaTemplates\PruebaTemplateResource;

class EditPruebaTemplate extends EditRecord
{
    protected static string $resource = PruebaTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
