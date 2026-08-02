<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\ChecklistEjecucionResource;
use Modules\Inspeccion\Models\ChecklistEjecucion;
use Modules\Inspeccion\Models\ChecklistTemplate;

class CreateChecklistEjecucion extends CreateRecord
{
    protected static string $resource = ChecklistEjecucionResource::class;

    /**
     * Usa el snapshot de la plantilla en vez de un create() plano, para que
     * el histórico de la ejecución no cambie si el catálogo se edita después.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $template = ChecklistTemplate::query()->findOrFail($data['checklist_template_id']);

        return ChecklistEjecucion::crearDesdeTemplate($data, $template);
    }
}
