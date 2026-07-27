<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;

class ListTableros extends ListRecords
{
    protected static string $resource = TableroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
