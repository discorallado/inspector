<?php

namespace Modules\Inspeccion\Filament\Resources\Observacions\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Inspeccion\Filament\Resources\Observacions\ObservacionResource;
use Modules\Inspeccion\Models\EstadoObservacion;

class CreateObservacion extends CreateRecord
{
    protected static string $resource = ObservacionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['estado_observacion_id'] = EstadoObservacion::query()->where('codigo', 'pendiente')->value('id');

        return $data;
    }
}
