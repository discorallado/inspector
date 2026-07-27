<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;

class EditTablero extends EditRecord
{
    protected static string $resource = TableroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
