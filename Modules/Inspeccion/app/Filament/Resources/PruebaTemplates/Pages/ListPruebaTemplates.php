<?php

namespace Modules\Inspeccion\Filament\Resources\PruebaTemplates\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\PruebaTemplates\PruebaTemplateResource;

class ListPruebaTemplates extends ListRecords
{
    protected static string $resource = PruebaTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
