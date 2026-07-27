<?php

namespace Modules\Inspeccion\Filament\Resources\GrupoHitos\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\GrupoHitos\GrupoHitoResource;

class ListGrupoHitos extends ListRecords
{
    protected static string $resource = GrupoHitoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
