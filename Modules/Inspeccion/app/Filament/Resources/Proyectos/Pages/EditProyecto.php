<?php

namespace Modules\Inspeccion\Filament\Resources\Proyectos\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Inspeccion\Filament\Resources\Proyectos\ProyectoResource;
use Modules\Inspeccion\Filament\Support\AccionesBorradoFisico;

class EditProyecto extends EditRecord
{
    protected static string $resource = ProyectoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesBorradoFisico::eliminar(),
        ];
    }
}
