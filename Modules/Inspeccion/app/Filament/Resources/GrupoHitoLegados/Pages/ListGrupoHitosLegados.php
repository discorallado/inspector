<?php

namespace Modules\Inspeccion\Filament\Resources\GrupoHitoLegados\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\GrupoHitoLegados\GrupoHitoLegadoResource;

class ListGrupoHitosLegados extends ListRecords
{
    protected static string $resource = GrupoHitoLegadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
