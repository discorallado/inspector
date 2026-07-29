<?php

namespace Modules\Inspeccion\Filament\Resources\Actividades\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Inspeccion\Filament\Resources\Actividades\ActividadResource;

/**
 * Sin CreateAction: Actividad solo se crea desde ActividadesRelationManager
 * de TableroResource (necesita tablero_id, que ese contexto ya conoce).
 */
class ListActividades extends ListRecords
{
    protected static string $resource = ActividadResource::class;
}
