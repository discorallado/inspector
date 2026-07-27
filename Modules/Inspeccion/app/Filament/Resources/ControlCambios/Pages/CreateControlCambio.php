<?php

namespace Modules\Inspeccion\Filament\Resources\ControlCambios\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Inspeccion\Filament\Resources\ControlCambios\ControlCambioResource;
use Modules\Inspeccion\Models\EstadoCambio;

class CreateControlCambio extends CreateRecord
{
    protected static string $resource = ControlCambioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['estado_cambio_id'] = EstadoCambio::query()->where('codigo', 'propuesto')->value('id');

        return $data;
    }
}
