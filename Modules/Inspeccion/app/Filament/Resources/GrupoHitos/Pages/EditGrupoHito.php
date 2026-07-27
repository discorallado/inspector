<?php

namespace Modules\Inspeccion\Filament\Resources\GrupoHitos\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\GrupoHitos\GrupoHitoResource;

class EditGrupoHito extends EditRecord
{
    protected static string $resource = GrupoHitoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
