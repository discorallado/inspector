<?php

namespace Modules\Inspeccion\Filament\Resources\GrupoHitoLegados\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\GrupoHitoLegados\GrupoHitoLegadoResource;

class EditGrupoHitoLegado extends EditRecord
{
    protected static string $resource = GrupoHitoLegadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
