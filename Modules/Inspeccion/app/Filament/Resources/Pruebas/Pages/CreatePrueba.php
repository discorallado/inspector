<?php

namespace Modules\Inspeccion\Filament\Resources\Pruebas\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Inspeccion\Filament\Resources\Pruebas\PruebaResource;
use Modules\Inspeccion\Models\Prueba;
use Modules\Inspeccion\Models\PruebaTemplate;

class CreatePrueba extends CreateRecord
{
    protected static string $resource = PruebaResource::class;

    /**
     * Usa el snapshot de la plantilla en vez de un create() plano, para que
     * el histórico de la prueba no cambie si el catálogo se edita después.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $template = PruebaTemplate::query()->findOrFail($data['prueba_template_id']);

        return Prueba::crearDesdeTemplate($data, $template);
    }
}
